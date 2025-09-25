<?php

namespace adApiWpIntegration\Contracts;

/**
 * Interface for classes that add WordPress hooks.
 * 
 * This interface ensures that all classes that register WordPress hooks
 * follow a consistent pattern and avoid side effects during testing.
 * Hooks should only be registered when explicitly called through addHooks().
 */
interface HookableInterface
{
    /**
     * Add hooks to WordPress.
     * 
     * This method should contain all WordPress hook registrations
     * (actions and filters) for the implementing class.
     */
    public function addHooks(): void;
}