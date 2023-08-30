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

// Autoload from plugin
if (file_exists(ADAPIWPINTEGRATION_PATH . 'vendor/autoload.php')) {
    require_once ADAPIWPINTEGRATION_PATH . 'vendor/autoload.php';
}

//Run plugin
new adApiWpIntegration\Database(); // Database normalization
new adApiWpIntegration\App(); //Init
new adApiWpIntegration\LoginNonce(); // Nonce sec
new adApiWpIntegration\LoginHoneyPot(); // Nonce sec
new adApiWpIntegration\Password(); //Do not allow ad-users to change their passwords
new adApiWpIntegration\Admin(); // Sends admin panel errors & information
new adApiWpIntegration\BulkImport(); // Import user accounts in bulk
new adApiWpIntegration\NewBlog(); // Propagate users if new blog is created
new adApiWpIntegration\Cleaning(); // Cleaning actions
