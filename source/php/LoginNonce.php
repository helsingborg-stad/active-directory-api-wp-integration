<?php

namespace adApiWpIntegration;

class LoginNonce
{
    /**
     * Hook nonce validation
     * @return void
     */
    public function __construct()
    {
        //Define AD_NONCE_VALIDATION to false to disable validation
        if(defined('AD_NONCE_VALIDATION') && AD_NONCE_VALIDATION === false) {
            return false;
        }

        add_action('login_form', array($this, 'renderNonce'), 15);
        add_action('wp_authenticate', array($this, 'validateNonce'), 15); // Ad login priority = 20
        add_filter('litespeed_esi_nonces', array($this, 'addEsiNonce'));
    }

    /**
     * Add nonce field to litespeed nonce esi handler
     * @return array
     */
    public function addEsiNonce($nonces) {
        if(is_array($nonces)) {
            $nonces[] = "_ad_nonce"; 
        }
        return $nonces; 
    }

    /**
     * Render the nonce field
     * @return void
     */
    public function renderNonce()
    {
        wp_nonce_field(
            "validate_active_directory_nonce", 
            "_ad_nonce"
        );
    }

    /**
     * Check the nonce before running login procedure, must use a hook lower than 20 (ad hijack).
     * @return void / bool
     */
    public function validateNonce($username = "")
    {
        if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
            if (isset($_POST['_ad_nonce'])) {
                if(wp_verify_nonce($_POST['_ad_nonce'], 'validate_active_directory_nonce')) {
                    return true;
                }
            }
            wp_die(__("Could not verify this logins origin. <a href='/wp-login.php'>Please try again.</a>", 'adintegration'));
        }
    }
}
