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
        // App only initializes password settings when the AD endpoint is valid.
        if (!defined('AD_INTEGRATION_URL') || filter_var(AD_INTEGRATION_URL, FILTER_VALIDATE_URL) === false) {
            return;
        }

        add_filter('allow_password_reset', [$this, 'defaultSettings'], 5, 2);
        add_filter('allow_password_reset', [$this, 'denyPasswordReset'], 10, 2);
    }

    /**
     * Default to basic settings if constants is undefined.
     * @return bool|\WP_Error
     */
    public function defaultSettings($allow)
    {
        if (!defined('AD_USER_DOMAIN')) {
            define('AD_USER_DOMAIN', $this->getNetworkUrl());
        }

        return $allow;
    }

    /**
     * Prevents password for being reset on ad-users
     * @return bool|\WP_Error
     */
    public function denyPasswordReset($allow, $user_id)
    {
        // Preserve restrictions and errors from earlier password reset filters.
        if ($allow !== true) {
            return $allow;
        }

        if (AD_RANDOM_PASSWORD === true) {
            if (substr(get_user_by('id', $user_id)->user_email, -strlen(AD_USER_DOMAIN)) === AD_USER_DOMAIN) {
                return false;
            }
        }

        return $allow;
    }

    /**
     * Get site url
     * @return void
     */
    private function getNetworkUrl()
    {
        $url = @parse_url(trim(network_site_url(), '/'));
        if (empty($url['host'])) {
            return;
        }
        $parts = explode('.', $url['host']);
        $slice = strlen(reset(array_slice($parts, -2, 1))) == 2 && count($parts) > 2 ? 3 : 2;
        return implode('.', array_slice($parts, 0 - $slice, $slice));
    }
}
