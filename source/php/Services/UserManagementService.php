<?php

namespace adApiWpIntegration\Services;

use adApiWpIntegration\Contracts\UserManagerInterface;
use adApiWpIntegration\Config\ConfigInterface;
use adApiWpIntegration\Helper\AutoCreate;
use adApiWpIntegration\Profile;
use adApiWpIntegration\Contracts\InputHandlerInterface;
use WpService\WpService;

/**
 * User management service implementation.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * user management logic. It implements the Dependency Inversion Principle
 * by depending on interfaces rather than concrete implementations.
 */
class UserManagementService implements UserManagerInterface
{
    public function __construct(
        private ConfigInterface $config,
        private InputHandlerInterface $inputHandler,
        private WpService $wpService
    ) {
    }

    /**
     * Get user ID by username or email.
     */
    public function getUserId(string $usernameOrEmail): ?int
    {
        $user = $this->wpService->getUserBy(
            $this->wpService->isEmail($usernameOrEmail) ? 'email' : 'login',
            $usernameOrEmail
        );

        if (is_object($user) && isset($user->ID)) {
            return $user->ID;
        }

        return null;
    }

    /**
     * Create a new user if auto-creation is enabled.
     */
    public function autoCreateUser(string $username, string $password, object $userData): ?int
    {
        if (!$this->config->isAutoCreateUserEnabled()) {
            return null;
        }

        AutoCreate::autoCreateUser($username, $password, $userData);
        
        return $this->getUserId($username);
    }

    /**
     * Update user profile with data from authentication source.
     */
    public function updateUserProfile(object $userData, int $userId): void
    {
        $profile = new Profile($this->inputHandler);
        $profile->update($userData, $userId);
    }

    /**
     * Translate email to username.
     */
    public function emailToUsername(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        if ($user = $this->wpService->getUserBy('email', $email)) {
            if (isset($user->user_login)) {
                return $user->user_login;
            }
        }

        return null;
    }
}