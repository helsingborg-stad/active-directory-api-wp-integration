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
        echo '<input type="hidden" name="_ad_nonce" value="' .$this->generateFakeNonce(). '"/>';
    }

    /**
     * Check the nonce before running login procedure, must use a hook lower than 20 (ad hijack).
     * @return void / bool
     */
    public function validateNonce($username)
    {
        if (isset($_POST) && is_array($_POST) && !empty($_POST)) {

            if (isset($_POST['_ad_nonce'])) {
                if ($_POST['_ad_nonce'] == $this->generateFakeNonce()) {
                    return true;
                }
            }

            wp_die(__("Could not verify this logins origin. <a href='/wp-login.php'>Please try again.</a>", 'adintegration'));
        }
    }

    /**
     * Generate fake nonce
     * @return string
     */
    public function generateFakeNonce()
    {
        return md5(NONCE_KEY."ad".date("Ymd"));
    }
}
