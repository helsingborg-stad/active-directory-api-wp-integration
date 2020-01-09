<?php

namespace adApiWpIntegration\Helper;

class AutoCreate
{
    private $defaultRole;

    public static function autoCreateUser($username, $password)
    {
        $defaultRole = defined('AD_AUTOCREATE_ROLE') && get_role(AD_AUTOCREATE_ROLE) ? AD_AUTOCREATE_ROLE : "subscriber";
        try {
            $insertUser = wp_insert_user(
                array(
                    'user_login' => $username,
                    'user_pass' => $password,
                    'role' =>  $defaultRole
                )
            );

          if(is_numeric($insertUser) && is_multisite()) {
              add_user_to_blog(get_current_blog_id(), $insertUser, $defaultRole); 
          }

        } catch (\Exception $e) {
            error_log("Error: Could not create a new user using bulk data (ad-api-integration).");
        }
    }
}
