<?php

namespace adApiWpIntegration\Services;

use adApiWpIntegration\Config\ConfigInterface;
use WpService\WpService;

/**
 * Password management service implementation.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * password management logic for AD users. It implements the Dependency
 * Inversion Principle by depending on abstractions rather than concrete implementations.
 */
class PasswordManagementService
{
    public function __construct(
        private ConfigInterface $config,
        private WpService $wpService
    ) {
        $this->initializePasswordManagement();
    }

    /**
     * Initialize password management filters.
     */
    private function initializePasswordManagement(): void
    {
        $this->wpService->addFilter('allow_password_reset', [$this, 'denyPasswordReset'], 10, 2);
    }

    /**
     * Deny password reset for AD users.
     * 
     * This method prevents AD users from resetting their passwords through
     * WordPress since their passwords are managed by Active Directory.
     */
    public function denyPasswordReset(bool $allow, int $userId): bool
    {
        if (!$this->config->isRandomPasswordEnabled()) {
            return $allow;
        }

        $userDomain = $this->config->getUserDomain();
        if (!$userDomain) {
            $userDomain = $this->getNetworkDomain();
        }

        if (!$userDomain) {
            return $allow;
        }

        $user = $this->wpService->getUserBy('id', $userId);
        if (!$user || !isset($user->user_email)) {
            return $allow;
        }

        // Check if user's email belongs to the AD domain
        if (substr($user->user_email, -strlen($userDomain)) === $userDomain) {
            return false;
        }

        return $allow;
    }

    /**
     * Extract domain from network URL.
     * 
     * This method extracts the base domain from the network site URL
     * for use as the default AD user domain.
     */
    private function getNetworkDomain(): ?string
    {
        $url = parse_url(trim($this->wpService->networkSiteUrl(), "/"));
        
        if (empty($url['host'])) {
            return null;
        }

        $parts = explode('.', $url['host']);
        
        // Determine if we need 2 or 3 parts (e.g., example.com vs example.co.uk)
        $slice = (strlen(reset(array_slice($parts, -2, 1))) == 2) && (count($parts) > 2) ? 3 : 2;
        
        return implode('.', array_slice($parts, (0 - $slice), $slice));
    }
}