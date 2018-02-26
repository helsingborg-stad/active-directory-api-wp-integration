<?php

namespace adApiWpIntegration;

class Login
{

    /**
     * Hook nonce validation
     * @return void
     */
    public function __construct()
    {
        add_action('login_form', array($this, 'renderNonce'), 15);
        add_action('wp_authenticate', array($this, 'validateNonce'), 15); // Ad login priority = 20
    }

    /**
     * Render the nonce field
     * @return void
     */
    public function renderNonce()
    {
        wp_nonce_field('active_directory_nonce', '_ad_nonce', false, true);
    }

    /**
     * Check the nonce before running login procedure, must use a hook lower than 20 (ad hijack).
     * @return void / bool
     */
    public function validateNonce($username)
    {
        if (isset($_POST) && is_array($_POST) && !empty($_POST) && isset($_POST['_ad_nonce'])) {
            if (wp_verify_nonce($_POST['_ad_nonce'], 'active_directory_nonce') === 1) {
                return true;
            }
            wp_die(__("Could not verify this logins origin. <a href='/wp-login.php'>Please try again.</a>", 'adintegration'));
        }
    }
}
