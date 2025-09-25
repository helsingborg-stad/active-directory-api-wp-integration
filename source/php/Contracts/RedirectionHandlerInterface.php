<?php

namespace adApiWpIntegration\Contracts;

/**
 * Interface for redirection handling services.
 * 
 * This interface follows the Interface Segregation Principle by defining
 * only redirection-related methods.
 */
interface RedirectionHandlerInterface
{
    /**
     * Handle redirect after successful login.
     */
    public function handleLoginRedirect(int $userId, string $referer = ''): void;

    /**
     * Send no-cache headers.
     */
    public function sendNoCacheHeaders(): void;

    /**
     * Append query string to URL.
     */
    public function appendQueryString(string $url, string $parameter, string $value): string;
}