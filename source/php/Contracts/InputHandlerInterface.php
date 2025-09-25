<?php

namespace adApiWpIntegration\Contracts;

/**
 * Interface for input handling services.
 * 
 * This interface follows the Interface Segregation Principle by defining
 * only input handling-related methods.
 */
interface InputHandlerInterface
{
    /**
     * Get POST data by key.
     */
    public function post(string $key): ?string;

    /**
     * Get GET data by key.
     */
    public function get(string $key): ?string;
}