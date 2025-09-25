<?php

namespace adApiWpIntegration\Services;

use adApiWpIntegration\Contracts\AuthenticatorInterface;
use adApiWpIntegration\Contracts\UserManagerInterface;
use adApiWpIntegration\Contracts\RedirectionHandlerInterface;
use adApiWpIntegration\Contracts\InputHandlerInterface;
use adApiWpIntegration\Config\ConfigInterface;
use adApiWpIntegration\Contracts\HookableInterface;
use WpService\WpService;

/**
 * Login service orchestrator.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * the login orchestration. It implements the Open/Closed Principle by
 * depending on interfaces, allowing for easy extension without modification.
 * It follows the Dependency Inversion Principle by depending on abstractions.
 */
class LoginService implements HookableInterface
{
    public function __construct(
        private AuthenticatorInterface $authenticator,
        private UserManagerInterface $userManager, 
        private RedirectionHandlerInterface $redirectionHandler,
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
     * It should be called after instantiation to register the hooks.
     */
    public function addHooks(): void
    {
        if (!$this->isConfigurationValid()) {
            return;
        }

        $this->wpService->addAction('wp_authenticate', [$this, 'handleLogin'], 20);
    }

    /**
     * Handle the login process.
     * 
     * This method orchestrates the entire login flow by delegating specific
     * responsibilities to their respective service classes.
     */
    public function handleLogin(string $username): void
    {
        $username = $this->sanitizeUsername($username);
        $password = $this->inputHandler->post('pwd');

        if (!$username || !$password) {
            return;
        }

        // Convert email to username if needed
        if ($this->wpService->isEmail($username)) {
            $username = $this->userManager->emailToUsername($username) ?: $username;
        }

        // Check if user exists (when auto-create is disabled)
        if (!$this->config->isAutoCreateUserEnabled()) {
            $userId = $this->userManager->getUserId($username);
            if (!$userId) {
                return;
            }
        }

        // Authenticate with Active Directory
        if (!$this->authenticator->authenticate($username, $password)) {
            return;
        }

        // Get user data from AD
        $userData = $this->authenticator->getUserData($username, $password);
        if (!$userData) {
            return;
        }

        // Auto-create user if enabled
        if ($this->config->isAutoCreateUserEnabled()) {
            $this->userManager->autoCreateUser($username, $password, $userData);
        }

        // Get final user ID
        $userId = $this->userManager->getUserId($username);
        if (!$userId) {
            return;
        }

        // Update user profile
        $this->userManager->updateUserProfile($userData, $userId);

        // Sign user on
        $this->authenticator->signOn([
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $this->inputHandler->get('rememberme') === 'forever'
        ]);

        // Handle redirect
        $referer = $this->inputHandler->get('_wp_http_referer') ?? '';
        $this->redirectionHandler->handleLoginRedirect($userId, $referer);
    }

    /**
     * Validate configuration.
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

    /**
     * Sanitize username input.
     */
    private function sanitizeUsername(string $username): string
    {
        return $this->wpService->sanitizeTextField(trim($username));
    }
}