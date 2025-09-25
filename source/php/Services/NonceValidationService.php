<?php

namespace adApiWpIntegration\Services;

use adApiWpIntegration\Contracts\InputHandlerInterface;
use adApiWpIntegration\Config\ConfigInterface;
use adApiWpIntegration\Contracts\HookableInterface;
use WpService\WpService;

/**
 * Nonce validation service implementation.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * nonce validation logic. It implements the Dependency Inversion Principle
 * by depending on abstractions rather than concrete implementations.
 */
class NonceValidationService implements HookableInterface
{
    private const NONCE_ACTION = 'validate_active_directory_nonce';
    private const NONCE_FIELD = '_ad_nonce';

    public function __construct(
        private InputHandlerInterface $inputHandler,
        private ConfigInterface $config,
        private WpService $wpService
    ) {
        // Hooks are now registered explicitly via addHooks() method
    }

    /**
     * Add hooks to WordPress.
     * 
     * This method contains all WordPress hook registrations for this service.
     */
    public function addHooks(): void
    {
        if (!$this->config->isNonceValidationEnabled()) {
            return;
        }

        $this->wpService->addAction('login_form', [$this, 'renderNonce'], 15);
        $this->wpService->addAction('wp_authenticate', [$this, 'validateNonce'], 15);
        $this->wpService->addFilter('litespeed_esi_nonces', [$this, 'addEsiNonce']);
    }

    /**
     * Render the nonce field in the login form.
     */
    public function renderNonce(): void
    {
        $this->wpService->wpNonceField(self::NONCE_ACTION, self::NONCE_FIELD);
    }

    /**
     * Validate the nonce before processing login.
     * 
     * This method must run before the main authentication (priority < 20).
     */
    public function validateNonce(string $username = ''): bool
    {
        $nonce = $this->inputHandler->post(self::NONCE_FIELD);
        
        if ($nonce === null) {
            return true; // No nonce provided, allow other authentication methods
        }

        if ($this->wpService->wpVerifyNonce($nonce, self::NONCE_ACTION)) {
            return true;
        }

        $this->wpService->wpDie($this->wpService->__("Could not verify this logins origin. <a href='/wp-login.php'>Please try again.</a>", 'adintegration'));
    }

    /**
     * Add nonce field to LiteSpeed ESI nonce handler.
     * 
     * This ensures compatibility with LiteSpeed Cache plugin.
     */
    public function addEsiNonce(array $nonces): array
    {
        if (is_array($nonces)) {
            $nonces[] = self::NONCE_FIELD;
        }
        
        return $nonces;
    }
}