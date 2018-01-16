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
        wp_nonce_field($this->createNonceKey(), '_ad_nonce', true, true);
    }

    /**
     * Check the nonce before running login procedure, must use a hook lower than 20 (ad hijack).
     * @return void / bool
     */
    public function validateNonce($username)
    {
        if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
            if (isset($_POST['_ad_nonce']) && wp_verify_nonce($_POST['_ad_nonce'], $this->createNonceKey())) {
                return true;
            }
            wp_die(__("Could not verify that you are a person.", 'adintegration'));
        }
    }

    /**
     * Creates a date specific nonce
     * @return string
     */
    public function createNonceKey()
    {
        return md5("adnoncekey" . date("Y-m-d"));
    }
}
