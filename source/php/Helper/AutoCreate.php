<?php

namespace adApiWpIntegration\Helper;

class AutoCreate
{
    public static function autoCreateUser($userName, $passWord)
    {
        try {
            $insertUser = wp_insert_user(
                array(
                    'user_login' => $userName,
                    'user_pass' => $passWord,
                    'role' =>  $this->defaultRole = defined('AD_AUTOCREATE_ROLE') && get_role(AD_AUTOCREATE_ROLE) ? AD_AUTOCREATE_ROLE : "subscriber"
                )
            );
        } catch (\Exception $e) {
            error_log("Error: Could not create a new user using bulk data (ad-api-integration).");
        }
    }
}
