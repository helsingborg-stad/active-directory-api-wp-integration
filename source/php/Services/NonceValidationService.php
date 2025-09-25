<?php

namespace adApiWpIntegration\Services;

use adApiWpIntegration\Contracts\InputHandlerInterface;
use adApiWpIntegration\Config\ConfigInterface;
use WpService\Contracts\AddAction;
use WpService\Contracts\AddFilter;

/**
 * Nonce validation service implementation.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * nonce validation logic. It implements the Dependency Inversion Principle
 * by depending on abstractions rather than concrete implementations.
 */
class NonceValidationService
{
    private const NONCE_ACTION = 'validate_active_directory_nonce';
    private const NONCE_FIELD = '_ad_nonce';

    public function __construct(
        private InputHandlerInterface $inputHandler,
        private ConfigInterface $config,
        private AddAction $addAction,
        private AddFilter $addFilter
    ) {
        $this->initializeNonceValidation();
    }

    /**
     * Initialize nonce validation if enabled.
     */
    private function initializeNonceValidation(): void
    {
        if (!$this->config->isNonceValidationEnabled()) {
            return;
        }

        $this->addAction->addAction('login_form', [$this, 'renderNonce'], 15);
        $this->addAction->addAction('wp_authenticate', [$this, 'validateNonce'], 15);
        $this->addFilter->addFilter('litespeed_esi_nonces', [$this, 'addEsiNonce']);
    }

    /**
     * Render the nonce field in the login form.
     */
    public function renderNonce(): void
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
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

        if (wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return true;
        }

        wp_die(__("Could not verify this logins origin. <a href='/wp-login.php'>Please try again.</a>", 'adintegration'));
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