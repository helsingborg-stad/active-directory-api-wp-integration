<?php

namespace adApiWpIntegration;

use adApiWpIntegration\Config\ConfigInterface;
use adApiWpIntegration\Services\LoginService;
use WpService\Contracts\AddFilter;

/**
 * Refactored Application class following SOLID principles.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * application initialization and setup. It implements the Open/Closed Principle
 * by depending on interfaces rather than concrete implementations. The Dependency
 * Inversion Principle is followed by injecting dependencies rather than creating them.
 */
class AppRefactored
{
    public function __construct(
        private LoginService $loginService,
        private ConfigInterface $config,
        private AddFilter $addFilter
    ) {
        $this->initializeApplication();
    }

    /**
     * Initialize the application.
     * 
     * This method sets up the core application features including
     * email notifications configuration.
     */
    private function initializeApplication(): void
    {
        if (!$this->isConfigurationValid()) {
            return;
        }

        $this->setupEmailNotifications();
    }

    /**
     * Setup email notification filters.
     * 
     * Since all account handling is automatic, we don't want to send any emails.
     * This follows the Single Responsibility Principle by being focused only
     * on email configuration.
     */
    private function setupEmailNotifications(): void
    {
        $this->addFilter->addFilter('send_password_change_email', '__return_false');
        $this->addFilter->addFilter('send_email_change_email', '__return_false');
    }

    /**
     * Validate that the required configuration is present.
     * 
     * This method ensures the application has the minimum required configuration
     * to function properly.
     */
    private function isConfigurationValid(): bool
    {
        $url = $this->config->getAdIntegrationUrl();
        
        if (!$url) {
            return false;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return true;
    }
}