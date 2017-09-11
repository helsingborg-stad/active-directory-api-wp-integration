<?php

namespace adApiWpIntegration;

class Bulk
{

    private $optionKey = "ad_integration_index";
    private $curl;
    private $db;

    /**
     * Prevents password for being reset
     * @return void
     */
    public function __construct()
    {
        //Globals
        global $wpdb;

        //Init
        $this->curl = new Helper\Curl();
        $this->db = $wpdb;

        //Actions & crons
        //add_action('init', array($this, 'updateIndex'));
        //
        //var_dump($this->diffUserAccounts());
        //
        //var_dump($this->reassignToUserId());
    }

    public function updateIndex() : bool
    {

        //Authentication
        $data = array(
            'username' => AD_BULK_IMPORT_USER,
            'password' => AD_BULK_IMPORT_PASSWORD
        );

        //Fetch index
        $index = $this->curl->request('POST', rtrim(AD_INTEGRATION_URL, "/") . '/user/index', $data, 'json', array('Content-Type: application/json'));

        if (!empty($index)) {
            $index = json_decode($index);

            if (is_multisite()) {
                return update_site_option($this->optionKey, $index);
            } else {
                return update_option($this->optionKey, $index, false);
            }
        }

        return false;
    }

    /**
     * Return all local accountnames
     * @return array
     */

    public function getLocalAccounts()
    {
        return $this->db->get_col("SELECT user_login FROM " . $this->db->users);
    }

    /**
     * Returns all accountnames registered in the ad index option
     * @return array
     */

    public function getAdAccounts()
    {
        if (is_multisite()) {
            return get_site_option($this->optionKey);
        } else {
            return get_option($this->optionKey);
        }

        return false;
    }


    /**
     * Get all usernames as an array that dosent exist in either enviroment
     * @param  $missingAccountsLocally Set to "true" to get accounts that exists in ad but not in WordPress. False value reverses the check.
     * @return array
     */

    public function diffUserAccounts($getMissingAccountsLocally = true)
    {
        $ad = $this->getAdAccounts();
        $local = $this->getLocalAccounts();

        if ($getMissingAccountsLocally === true) {
            return array_diff($ad, $local);
        } else {
            return array_diff($local, $ad);
        }
    }

    /**
     * Creates a single user if it not exists.
     * @param string $userName A string with a username that corresponds to the ad username.
     * @param string $userEmail A string with a email adress that corresponds to the ad email adress.
     * @return boolean
     */

    public function createAccount($userName, $userEmail)
    {
        if (username_exists($userName) && email_exists($userEmail)) {
            return wp_create_user($userName, wp_generate_password(), $userEmail);
        }
        return false;
    }

    /**
     * Get an id if the user that should be reassigned all content
     * @return bool, integer
     */

    public function reassignToUserId()
    {
        //If there is a configurated username for reassignment, use it.
        if (defined('AD_BULK_IMPORT_REASSIGN_USERNAME') && $userId = username_exists(AD_BULK_IMPORT_REASSIGN_USERNAME)) {
            if ($userId) {
                return $userId;
            }
        }

        // Above wasen't defined. Get first user id.
        $user = $this->db->get_col("SELECT ID FROM " . $this->db->users . " LIMIT 1");
        if (is_array($user) && !empty($user)) {
            return (int) array_pop($user);
        }

        return false;
    }

    /**
     * Delete and reassign content to user id defined by reassignToUserId function.
     * @param  $userName the username that should be deleted.
     * @return array
     */

    public function deleteAccount($userName)
    {
        if ($userId = username_exists($userToDelete)) {
            if ($reassign = $this->reassignToUserId()) {
                wp_delete_user($userId, $reassign);
            }
        }
    }
}
