<?php

namespace adApiWpIntegration\Helper;

class AutoCreate
{

    private $defaultRole;

    public function __construct() {
        $this->defaultRole = defined('AD_AUTOCREATE_ROLE') && get_role(AD_AUTOCREATE_ROLE) ? AD_AUTOCREATE_ROLE : "subscriber";
    }

    public static function autoCreateUser($userName, $passWord)
    {
        try {
            $insertUser = wp_insert_user(
                array(
                    'user_login' => $userName,
                    'user_pass' => $passWord,
                    'role' =>  $this->defaultRole
                )
            );
        } catch (\Exception $e) {
            error_log("Error: Could not create a new user using bulk data (ad-api-integration).");
        }
    }
}
