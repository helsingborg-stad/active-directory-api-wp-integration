<?php

namespace adApiWpIntegration\Container;

use adApiWpIntegration\Config\Config;
use adApiWpIntegration\Config\ConfigInterface;
use adApiWpIntegration\Services\AuthenticationService;
use adApiWpIntegration\Services\UserManagementService;
use adApiWpIntegration\Services\RedirectionService;
use adApiWpIntegration\Services\LoginService;
use adApiWpIntegration\Contracts\AuthenticatorInterface;
use adApiWpIntegration\Contracts\UserManagerInterface;
use adApiWpIntegration\Contracts\RedirectionHandlerInterface;
use adApiWpIntegration\Contracts\HttpClientInterface;
use adApiWpIntegration\Contracts\InputHandlerInterface;
use adApiWpIntegration\Helper\Curl;
use adApiWpIntegration\Input;
use adApiWpIntegration\AppRefactored;
use WpService\WpService;

/**
 * Service container for dependency injection.
 * 
 * This class follows the Dependency Inversion Principle by providing a
 * centralized way to manage dependencies. It implements the Single
 * Responsibility Principle by handling only service instantiation and
 * dependency resolution.
 */
class ServiceContainer
{
    private array $services = [];
    private array $singletons = [];

    public function __construct(private WpService $wpService)
    {
        $this->registerServices();
    }

    /**
     * Register all services with their implementations.
     * 
     * This method follows the Open/Closed Principle by allowing new services
     * to be registered without modifying existing code.
     */
    private function registerServices(): void
    {
        // Register core dependencies
        $this->registerSingleton(WpService::class, function () {
            return $this->wpService;
        });

        $this->registerSingleton(InputHandlerInterface::class, function () {
            return new Input();
        });

        $this->registerSingleton(HttpClientInterface::class, function () {
            return new Curl();
        });

        $this->registerSingleton(ConfigInterface::class, function () {
            return new Config($this->get(WpService::class));
        });

        // Register service implementations
        $this->registerSingleton(AuthenticatorInterface::class, function () {
            return new AuthenticationService(
                $this->get(HttpClientInterface::class),
                $this->get(ConfigInterface::class),
                $this->get(WpService::class),
                $this->get(WpService::class)
            );
        });

        $this->registerSingleton(UserManagerInterface::class, function () {
            return new UserManagementService(
                $this->get(ConfigInterface::class),
                $this->get(InputHandlerInterface::class)
            );
        });

        $this->registerSingleton(RedirectionHandlerInterface::class, function () {
            return new RedirectionService(
                $this->get(WpService::class),
                $this->get(InputHandlerInterface::class)
            );
        });

        $this->registerSingleton(LoginService::class, function () {
            return new LoginService(
                $this->get(AuthenticatorInterface::class),
                $this->get(UserManagerInterface::class),
                $this->get(RedirectionHandlerInterface::class),
                $this->get(InputHandlerInterface::class),
                $this->get(ConfigInterface::class),
                $this->get(WpService::class)
            );
        });

        // Register security services
        $this->registerSingleton(\adApiWpIntegration\Services\NonceValidationService::class, function () {
            return new \adApiWpIntegration\Services\NonceValidationService(
                $this->get(InputHandlerInterface::class),
                $this->get(ConfigInterface::class),
                $this->get(WpService::class),
                $this->get(WpService::class)
            );
        });

        $this->registerSingleton(\adApiWpIntegration\Services\HoneyPotValidationService::class, function () {
            return new \adApiWpIntegration\Services\HoneyPotValidationService(
                $this->get(InputHandlerInterface::class),
                $this->get(ConfigInterface::class),
                $this->get(WpService::class)
            );
        });

        $this->registerSingleton(\adApiWpIntegration\Services\PasswordManagementService::class, function () {
            return new \adApiWpIntegration\Services\PasswordManagementService(
                $this->get(ConfigInterface::class),
                $this->get(WpService::class)
            );
        });

        $this->registerSingleton(AppRefactored::class, function () {
            return new AppRefactored(
                $this->get(LoginService::class),
                $this->get(ConfigInterface::class),
                $this->get(WpService::class)
            );
        });
    }

    /**
     * Register a service with a factory function.
     */
    public function register(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    /**
     * Register a singleton service with a factory function.
     */
    public function registerSingleton(string $id, callable $factory): void
    {
        $this->register($id, function () use ($id, $factory) {
            if (!isset($this->singletons[$id])) {
                $this->singletons[$id] = $factory();
            }
            return $this->singletons[$id];
        });
    }

    /**
     * Get a service by its identifier.
     */
    public function get(string $id): mixed
    {
        if (!isset($this->services[$id])) {
            throw new \InvalidArgumentException("Service '{$id}' not found.");
        }

        return $this->services[$id]();
    }

    /**
     * Check if a service is registered.
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}