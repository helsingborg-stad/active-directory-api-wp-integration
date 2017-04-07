<?php

/**
 * Plugin Name:       Active Directory Integration
 * Plugin URI:        (#plugin_url#)
 * Description:       Integration with the simple active directory api service
 * Version:           1.0.0
 * Author:            Sebastian Thulin
 * Author URI:        (#plugin_author_url#)
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       adintegration
 * Domain Path:       /languages
 */

 // Protect agains direct file access
if (! defined('WPINC')) {
    die;
}

define('ADAPIWPINTEGRATION_PATH', plugin_dir_path(__FILE__));
define('ADAPIWPINTEGRATION_URL', plugins_url('', __FILE__));
define('ADAPIWPINTEGRATION_TEMPLATE_PATH', ADAPIWPINTEGRATION_PATH . 'templates/');

load_plugin_textdomain('adintegration', false, plugin_basename(dirname(__FILE__)) . '/languages');

require_once ADAPIWPINTEGRATION_PATH . 'source/php/Vendor/Psr4ClassLoader.php';
require_once ADAPIWPINTEGRATION_PATH . 'Public.php';

// Instantiate and register the autoloader
$loader = new adApiWpIntegration\Vendor\Psr4ClassLoader();
$loader->addPrefix('adApiWpIntegration', ADAPIWPINTEGRATION_PATH);
$loader->addPrefix('adApiWpIntegration', ADAPIWPINTEGRATION_PATH . 'source/php/');
$loader->register();

// Start application
new adApiWpIntegration\App();
