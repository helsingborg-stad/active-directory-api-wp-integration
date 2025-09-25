<?php

/**
 * Plugin Name:       Active Directory Integration
 * Plugin URI:        https://github.com/helsingborg-stad/active-directory-api-wp-integration
 * Description:       Integration with the simple active directory api service
 * Version: 3.1.3
 * Author:            Sebastian Thulin
 * Author URI:        https://github.com/sebastianthulin
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       adintegration
 * Domain Path:       /languages
 */

// Protect against direct file access
if (! defined('WPINC')) {
    die;
}

// Load translations
load_plugin_textdomain('adintegration', false, plugin_basename(dirname(__FILE__)) . '/languages');

// Define constants
define('ADAPIWPINTEGRATION_PATH', plugin_dir_path(__FILE__));

// Autoload from plugin
if (file_exists(ADAPIWPINTEGRATION_PATH . 'vendor/autoload.php')) {
    require_once ADAPIWPINTEGRATION_PATH . 'vendor/autoload.php';
}

use adApiWpIntegration\Container\ServiceContainer;
use adApiWpIntegration\AppRefactored;
use WpService\Implementations\NativeWpService;

/**
 * Bootstrap the plugin using SOLID principles.
 * 
 * This bootstrap follows the Dependency Inversion Principle by using a
 * service container to manage dependencies. It implements the Single
 * Responsibility Principle by separating concerns into focused services.
 */

// Create core dependencies
$wpService = new NativeWpService();

// Initialize service container with dependency injection
$container = new ServiceContainer($wpService);

// Bootstrap the refactored application
$app = $container->get(AppRefactored::class);

// Initialize security services using the refactored SOLID architecture
$nonceValidation = $container->get(\adApiWpIntegration\Services\NonceValidationService::class);
$honeyPotValidation = $container->get(\adApiWpIntegration\Services\HoneyPotValidationService::class);
$passwordManagement = $container->get(\adApiWpIntegration\Services\PasswordManagementService::class);

// Initialize remaining legacy classes (to be refactored in future iterations)
// These maintain backward compatibility while we incrementally refactor
$input = $container->get(adApiWpIntegration\Contracts\InputHandlerInterface::class);

new adApiWpIntegration\Database(); // Database normalization - TODO: Refactor to follow SOLID
new adApiWpIntegration\Admin(); // Admin panel management - TODO: Refactor to follow SOLID
new adApiWpIntegration\BulkImport($input); // Bulk import functionality - TODO: Refactor to follow SOLID
new adApiWpIntegration\NewBlog(); // New blog propagation - TODO: Refactor to follow SOLID
new adApiWpIntegration\Cleaning($input); // Cleaning actions - TODO: Refactor to follow SOLID
