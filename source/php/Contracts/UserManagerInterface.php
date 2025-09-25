<?php

namespace adApiWpIntegration\Contracts;

/**
 * Interface for user management services.
 * 
 * This interface follows the Interface Segregation Principle by defining
 * only user management-related methods.
 */
interface UserManagerInterface
{
    /**
     * Get user ID by username or email.
     */
    public function getUserId(string $usernameOrEmail): ?int;

    /**
     * Create a new user if auto-creation is enabled.
     */
    public function autoCreateUser(string $username, string $password, object $userData): ?int;

    /**
     * Update user profile with data from authentication source.
     */
    public function updateUserProfile(object $userData, int $userId): void;
}