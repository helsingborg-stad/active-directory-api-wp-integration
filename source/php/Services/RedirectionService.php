<?php

namespace adApiWpIntegration\Services;

use adApiWpIntegration\Contracts\RedirectionHandlerInterface;
use adApiWpIntegration\Contracts\InputHandlerInterface;
use WpService\WpService;

/**
 * Redirection service implementation.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * redirection logic. It implements the Dependency Inversion Principle
 * by depending on interfaces rather than concrete implementations.
 */
class RedirectionService implements RedirectionHandlerInterface
{
    public function __construct(
        private WpService $wpService,
        private InputHandlerInterface $inputHandler
    ) {
    }

    /**
     * Handle redirect after successful login.
     */
    public function handleLoginRedirect(int $userId, string $referer = ''): void
    {
        $userData = $this->wpService->getUserdata($userId);
        
        if (!$userData) {
            return;
        }

        $this->sendNoCacheHeaders();

        // Handle subscriber redirect
        if (in_array('subscriber', (array) $userData->roles)) {
            $this->handleSubscriberRedirect($referer);
            return;
        }

        // Handle default redirect
        $this->handleDefaultRedirect();
    }

    /**
     * Send no-cache headers.
     */
    public function sendNoCacheHeaders(): void
    {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    /**
     * Append query string to URL.
     */
    public function appendQueryString(string $url, string $parameter, string $value): string
    {
        $url = $this->wpService->escUrlRaw($url);
        $parameter = $this->wpService->sanitizeKey($parameter);
        $value = rawurlencode($value);

        if (strpos($url, '?') !== false) {
            $url .= '&' . $parameter . '=' . $value;
        } else {
            $url .= '?' . $parameter . '=' . $value;
        }

        return $url;
    }

    /**
     * Handle subscriber redirect.
     */
    private function handleSubscriberRedirect(string $referer): void
    {
        $referer = $referer ?: $this->inputHandler->get('_wp_http_referer') ?: '/';

        if ($this->wpService->isMultisite()) {
            $url = $this->appendQueryString(
                $this->wpService->networkHomeUrl($referer),
                'login',
                'true'
            );
        } else {
            $url = $this->appendQueryString(
                $this->wpService->homeUrl($referer),
                'login',
                'true'
            );
        }

        $redirectUrl = $this->wpService->applyFilters(
            'adApiWpIntegration/login/subscriberRedirect',
            $url
        );

        $this->wpService->wpRedirect($redirectUrl);
        exit;
    }

    /**
     * Handle default redirect.
     */
    private function handleDefaultRedirect(): void
    {
        $url = $this->appendQueryString(
            $this->wpService->adminUrl("?auth=active-directory"),
            'login',
            'true'
        );

        $redirectUrl = $this->wpService->applyFilters(
            'adApiWpIntegration/login/defaultRedirect',
            $url
        );

        $this->wpService->wpRedirect($redirectUrl);
        exit;
    }
}