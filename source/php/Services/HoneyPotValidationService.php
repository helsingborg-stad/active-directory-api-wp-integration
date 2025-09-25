<?php

namespace adApiWpIntegration\Services;

use adApiWpIntegration\Contracts\InputHandlerInterface;
use adApiWpIntegration\Config\ConfigInterface;
use WpService\WpService;

/**
 * Honey pot validation service implementation.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * honey pot validation logic. It implements the Dependency Inversion Principle
 * by depending on abstractions rather than concrete implementations.
 */
class HoneyPotValidationService
{
    private const FIELD_MIN_TIME = 457; // milliseconds
    private string $fieldName;

    public function __construct(
        private InputHandlerInterface $inputHandler,
        private ConfigInterface $config,
        private WpService $wpService
    ) {
        $this->fieldName = '_ad_hp_' . substr(md5(AUTH_KEY), 5, 10);
        $this->initializeHoneyPotValidation();
    }

    /**
     * Initialize honey pot validation if enabled.
     */
    private function initializeHoneyPotValidation(): void
    {
        if (!$this->config->isHoneyPotValidationEnabled()) {
            return;
        }

        $this->wpService->addAction('login_form', [$this, 'renderHoneyPot'], 15);
        $this->wpService->addAction('wp_authenticate', [$this, 'validateHoneyPot'], 15);
    }

    /**
     * Render the honey pot field in the login form.
     */
    public function renderHoneyPot(): void
    {
        echo $this->getHoneyPotStyles();
        echo $this->getHoneyPotField();
        echo $this->getHoneyPotScript();
    }

    /**
     * Validate the honey pot before processing login.
     * 
     * This method must run before the main authentication (priority < 20).
     */
    public function validateHoneyPot(string $username = ''): bool
    {
        $honeyPotValue = $this->inputHandler->post($this->fieldName);
        
        if ($honeyPotValue === null) {
            return true; // No honey pot value, continue
        }

        if ($honeyPotValue == self::FIELD_MIN_TIME) {
            return true;
        }

        $this->wpService->wpDie($this->wpService->__("Could not verify that you are not a bot. <a href='/wp-login.php'>Please try again.</a>", 'adintegration'));
    }

    /**
     * Get the CSS styles for hiding the honey pot field.
     */
    private function getHoneyPotStyles(): string
    {
        return '<style>.fake-hide {width: 1px; height: 1px; opacity: 0.0001; position: absolute; overflow: hidden;}</style>';
    }

    /**
     * Get the honey pot field HTML.
     */
    private function getHoneyPotField(): string
    {
        return sprintf(
            '<div class="fake-hide">
                <input 
                    class="ad-login-field" 
                    type="text" 
                    name="%s"
                    autocomplete="off" 
                    tabIndex="-1"
                    aria-hidden="true"
                />
            </div>',
            $this->wpService->escAttr($this->fieldName)
        );
    }

    /**
     * Get the JavaScript for managing the honey pot field.
     */
    private function getHoneyPotScript(): string
    {
        return sprintf(
            '<script type="text/javascript">
                ["load"].forEach(function(event) {
                    window.addEventListener(event, function() {
                        [].forEach.call(document.querySelectorAll(".ad-login-field"), function(item) {
                            setTimeout(function() {
                                item.value = "%d"; 
                            }.bind(item), %d); 
                        });
                    });
                });
            </script>',
            self::FIELD_MIN_TIME,
            self::FIELD_MIN_TIME
        );
    }
}