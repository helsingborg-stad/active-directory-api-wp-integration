<?php

namespace adApiWpIntegration\Helper;

class AutoCreate
{
    public function __construct()
    {
    }

    public static function autoCreateUser($userName, $passWord, $userId)
    {
        try {
            $insertUser = wp_insert_user(
              array(
              'user_login' => $userName,
              'user_pass' => $passWord,
              'role' =>  'editor'
            )
          );
            if ($insertUser) {
                $userMeta = get_user_meta($userId);
                update_user_meta($userId, 'name_of_council_or_politician', $userMeta['first_name'][0] . ' ' . $userMeta['last_name'][0]);
                update_user_meta($userId, 'target_group', 'politician');
            }
        } catch (\Exception $e) {
            error_log("Error: Could not create a new user using bulk data (ad-api-integration).");
        }
    }
}
