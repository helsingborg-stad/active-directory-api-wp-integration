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
        if (!AD_VALIDATE_NONCE) {
            return false;
        }

        wp_nonce_field($this->createNonceKey(), '_ad_nonce', true, true);
    }

    /**
     * Check the nonce before running login procedure, must use a hook lower than 20 (ad hijack).
     * @return void / bool
     */
    public function validateNonce($username)
    {
        if (empty($username)) {
            return false;
        }

        if (!AD_VALIDATE_NONCE) {
            return false;
        }

        if (!isset($_POST['_ad_nonce'])) {
            return false;
        }

        if (wp_verify_nonce($_POST['_ad_nonce'], $this->createNonceKey())) {
            return true;
        }

        wp_die(__("Could not verify that you are a person.", 'adintegration'));
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
