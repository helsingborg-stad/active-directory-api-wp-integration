<?php

namespace adApiWpIntegration\Contracts;

/**
 * Interface for authentication services.
 * 
 * This interface follows the Interface Segregation Principle by defining
 * only authentication-related methods.
 */
interface AuthenticatorInterface
{
    /**
     * Authenticate a user with username and password.
     */
    public function authenticate(string $username, string $password): bool;

    /**
     * Get user data from authentication source.
     */
    public function getUserData(string $username, string $password): ?object;

    /**
     * Sign on a user to WordPress.
     */
    public function signOn(array $credentials): void;
}