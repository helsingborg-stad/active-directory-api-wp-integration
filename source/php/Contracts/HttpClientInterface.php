<?php

namespace adApiWpIntegration\Contracts;

/**
 * Interface for HTTP client services.
 * 
 * This interface follows the Interface Segregation Principle by defining
 * only HTTP client-related methods.
 */
interface HttpClientInterface
{
    /**
     * Make an HTTP request.
     */
    public function request(string $method, string $url, array $data = [], string $format = 'json', array $headers = []): mixed;
}