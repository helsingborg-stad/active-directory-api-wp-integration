<?php

namespace adApiWpIntegration\Services;

use adApiWpIntegration\Contracts\AuthenticatorInterface;
use adApiWpIntegration\Contracts\HttpClientInterface;
use adApiWpIntegration\Config\ConfigInterface;
use adApiWpIntegration\Helper\Response;
use WpService\WpService;

/**
 * Authentication service implementation.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * authentication logic. It implements the Dependency Inversion Principle
 * by depending on interfaces rather than concrete implementations.
 */
class AuthenticationService implements AuthenticatorInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ConfigInterface $config,
        private WpService $wpService
    ) {
    }

    /**
     * Authenticate a user with username and password.
     */
    public function authenticate(string $username, string $password): bool
    {
        $userData = $this->getUserData($username, $password);
        
        if (!$userData) {
            return false;
        }

        return $this->validateUserData($userData, $username);
    }

    /**
     * Get user data from authentication source.
     */
    public function getUserData(string $username, string $password): ?object
    {
        if (empty($username) || empty($password)) {
            return null;
        }

        $url = $this->config->getAdIntegrationUrl();
        if (!$url) {
            return null;
        }

        // Escape password for Active Directory
        $unescapedPassword = stripslashes($password);
        $adEscapedPassword = preg_replace('/(["\/\\\])/', '\\\\$1', $unescapedPassword);

        $data = [
            'username' => $username,
            'password' => $adEscapedPassword
        ];

        $result = $this->httpClient->request(
            'POST',
            rtrim($url, '/') . '/user/current',
            $data,
            'json',
            ['Content-Type: application/json']
        );

        if ($this->wpService->isWpError($result)) {
            return null;
        }

        if (Response::isJsonError($result)) {
            return null;
        }

        $decoded = json_decode($result);
        
        if (is_array($decoded)) {
            $decoded = array_pop($decoded);
        }

        return $decoded;
    }

    /**
     * Sign on a user to WordPress.
     */
    public function signOn(array $credentials): void
    {
        $secureCookie = $this->wpService->applyFilters(
            'secure_signon_cookie',
            $this->wpService->isSsl(),
            $credentials
        );

        global $auth_secure_cookie;
        $auth_secure_cookie = $secureCookie;

        $this->wpService->addFilter('authenticate', 'wp_authenticate_cookie', 30, 3);

        $user = $this->wpService->getUserBy('login', $credentials['user_login']);
        
        if ($user) {
            $this->wpService->wpSetAuthCookie($user->ID, $credentials['remember'], $secureCookie);
            $this->wpService->doAction('wp_login', $credentials['user_login'], $user);
        }
    }

    /**
     * Validate user data from authentication source.
     */
    private function validateUserData(?object $data, string $username): bool
    {
        if (!is_object($data)) {
            return false;
        }

        if (isset($data->error)) {
            return false;
        }

        if (isset($data->samaccountname) && 
            strtolower($data->samaccountname) === strtolower($username)) {
            return true;
        }

        return false;
    }
}