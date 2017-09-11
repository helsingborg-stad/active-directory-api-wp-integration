<?php

/**
 * Plugin Name:       Active Directory Integration
 * Plugin URI:        https://github.com/helsingborg-stad/active-directory-api-wp-integration
 * Description:       Integration with the simple active directory api service
 * Version:           1.0.0
 * Author:            Sebastian Thulin
 * Author URI:        https://github.com/sebastianthulin
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       adintegration
 * Domain Path:       /languages
 */

 // Protect agains direct file access
if (! defined('WPINC')) {
    die;
}

//Translations
load_plugin_textdomain('adintegration', false, plugin_basename(dirname(__FILE__)) . '/languages');

//Constants
define('ADAPIWPINTEGRATION_PATH', plugin_dir_path(__FILE__));

//Autoloader
require_once ADAPIWPINTEGRATION_PATH . 'source/php/Vendor/Psr4ClassLoader.php';

// Instantiate and register the autoloader
$loader = new adApiWpIntegration\Vendor\Psr4ClassLoader();
$loader->addPrefix('adApiWpIntegration', ADAPIWPINTEGRATION_PATH);
$loader->addPrefix('adApiWpIntegration', ADAPIWPINTEGRATION_PATH . 'source/php/');
$loader->register();

//Run plugin
new adApiWpIntegration\App();
new adApiWpIntegration\Password();
new adApiWpIntegration\Admin();
new adApiWpIntegration\Bulk();
