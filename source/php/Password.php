<?php

namespace adApiWpIntegration;

class Password
{

    /**
     * Prevents password for being reset
     * @return void
     */
    public function __construct()
    {
        add_filter('allow_password_reset', array($this, 'defaultSettings'), 5, 2);
        add_filter('allow_password_reset', array($this, 'denyPasswordReset'), 10, 2);
    }

     /**
     * Default to basic settings if constants is undefined.
     * @return void
     */
    public function defaultSettings()
    {
        if (!defined('AD_USER_DOMAIN')) {
            define('AD_USER_DOMAIN', $this->getNetworkUrl());
        }
    }

    /**
     * Prevents password for being reset on ad-users
     * @return void
     */
    public function denyPasswordReset($allow, $user_id)
    {
        if (AD_RANDOM_PASSWORD === true) {
            if ((substr(get_user_by('id', $user_id)->user_email, -strlen(AD_USER_DOMAIN)) === AD_USER_DOMAIN)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get site url
     * @return void
     */
    private function getNetworkUrl()
    {
        $url = @parse_url(trim(network_site_url(), "/"));
        if (empty($url['host'])) {
            return;
        }
        $parts = explode('.', $url['host']);
        $slice = (strlen(reset(array_slice($parts, -2, 1))) == 2) && (count($parts) > 2) ? 3 : 2;
        return implode('.', array_slice($parts, (0 - $slice), $slice));
    }
}
