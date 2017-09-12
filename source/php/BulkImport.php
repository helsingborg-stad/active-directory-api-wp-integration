<?php

namespace adApiWpIntegration;

/**
 * Bulk importing
 * Class for bulk import if users. This class only handles the syncronisation of user accounts.
 * This does not updates the respective users profile. Refeer to the Profile Class to review the procedure of account detail updates.
 * The cron may have to run multiple times when first creating users. After the init period, bulk importing should run smoothly.
 **/

class BulkImport
{

    private $index;
    private $defaultRole;
    private $curl;
    private $response;
    private $db;
    private $sites;

    private $profile;

    /**
     * Prevents password for being reset
     * @return void
     */
    public function __construct()
    {
        //Do not run if not all requirements are set.
        if ($this->bulkEnabled() === false) {
            return;
        }

        //Globals
        global $wpdb;

        //Init
        $this->curl = new Helper\Curl();
        $this->response = new Helper\Response();
        $this->db = $wpdb;
        $this->defaultRole = defined('AD_BULK_IMPORT_ROLE') ? AD_BULK_IMPORT_ROLE : "subscriber";

        //Create cronjob
        add_action('init', function () {
            if (!wp_next_scheduled('ad_integration_bulk_import')) {
                wp_schedule_event((strtotime("midnight") + (60*60*3)), 'daily', 'ad_integration_bulk_import');
            }
        });

        //Set sites if is multiste
        add_action('init', function () {
            if (is_multisite()) {
                $this->sites = get_sites();
            } else {
                $this->sites = null;
            }
        });

        //Hook cron
        add_action('ad_integration_bulk_import', array($this, 'cron'));
        add_action('ad_integration_bulk_update_profiles', array($this, 'updateProfiles'));

        //Manually test functionality
        add_action('admin_init', function () {
            if (isset($_GET['adbulkimport'])) {
                define('DOING_CRON', true);
                $this->cron();
                exit;
            }
        }, 1);
    }

    /**
     * Cron function, run this class
     * @return bool
     */

    public function cron()
    {

        //Increase memory and runtime
        ini_set('memory_limit', "512M");
        ini_set('max_execution_time', 60*60*60);

        //Include required resources
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        // Step 1: Get index
        $this->index = $this->getIndex();

        //Step 2: Create diffs
        $createAccounts = $this->diffUserAccounts(true);
        $deleteAccounts = $this->diffUserAccounts(false);

        //Step 3: Delete these accounts
        if (is_array($deleteAccounts) && !empty($deleteAccounts)) {
            foreach ((array) $deleteAccounts as $accountName) {
                $this->deleteAccount($accountName);
            }
        }

        //Step 4: Create these accounts
        if (is_array($createAccounts) && !empty($createAccounts)) {
            foreach ((array) $createAccounts as $accountName) {
                $this->createAccount($accountName);
            }
        }

        //Step 5: Schedule profile updates
        $this->scheduleUpdateProfiles();
    }

    /**
     * Check if all details that are neeeded to run this function is defined.
     * @return bool
     */

    private function bulkEnabled()
    {
        //Check if bulk should be done
        if (!(defined('AD_BULK_IMPORT') || AD_BULK_IMPORT !== true)) {
            return false;
        }

        //Check if has master account details
        if (!defined('AD_BULK_IMPORT_USER')||!defined('AD_BULK_IMPORT_PASSWORD')) {
            return false;
        }

        return true;
    }

    /**
     * Connect to the ad-api and fetch all users avabile
     * @return array
     */
    public function getIndex()
    {
        //Use cached response if less than 10 minutes has went (prevents abuse)
        if ($cached = wp_cache_get('active_directory_index', 'activeDirectory')) {
            return $cached;
        }

        //Authentication
        $data = array(
            'username' => AD_BULK_IMPORT_USER,
            'password' => AD_BULK_IMPORT_PASSWORD
        );

        //Fetch index
        $index = $this->curl->request('POST', rtrim(AD_INTEGRATION_URL, "/") . '/user/index', $data, 'json', array('Content-Type: application/json'));

        //Validate json response
        if ($this->response::isJsonError($index)) {
            return false;
        }

        //Cache response for some minutes
        wp_cache_add('active_directory_index', json_decode($index), 'activeDirectory', 60*60*10);

        //Return
        return json_decode($index);
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
     * Returns all accountnames registered in the ad index
     * @return array
     */

    public function getAdAccounts()
    {
        return $this->index;
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
            return array_diff((array) $ad, (array) $local);
        } else {
            return array_diff((array) $local, (array) $ad);
        }
    }

    /**
     * Creates a single user if it not exists.
     * @param string $userName A string with a username that corresponds to the ad username.
     * @param string $userEmail A string with a email adress that corresponds to the ad email adress.
     * @return boolean / user id
     */

    public function createAccount($userName)
    {
        if (!$userId = username_exists($userName)) {
            $userId =  wp_create_user($userName, wp_generate_password(), $this->createFakeEmail($userName));

            if ($userId) {
                $this->setUserRole($userId);
            }
        }

        $this->setUserRole($userId);

        return false;
    }

    /**
     * Creates a fake, temporary email adress. We do not have any real details about the account here.
     * @return string A fake randomly generated email.
     */

    public function createFakeEmail($userName)
    {
        if (defined('AD_USER_DOMAIN')) {
            return "temp." . base_convert($userName . time(), 10, 32) . "@" . AD_USER_DOMAIN;
        } else {
            return "temp." . base_convert($userName . time(), 10, 32) . "@" . base_convert($userName . time(), 10, 32) . ".dev";
        }
    }

    /**
     * Update role, if account is newly created. If is multiste, assign to all sites.
     * @param int $userId The user id to assign default role to.
     * @return bool
     */

    private function setUserRole($userId)
    {
        if (is_multisite()) {
            if (defined('AD_BULK_IMPORT_PROPAGATE') && AD_BULK_IMPORT_PROPAGATE === true) {
                foreach ($this->sites as $site) {
                    switch_to_blog($site->blog_id);
                    if (isset(get_userdata($userId)->roles) && !empty(get_userdata($userId)->roles)) {
                        continue;
                    }
                    add_user_to_blog(get_current_blog_id(), $userId, $this->defaultRole);
                    restore_current_blog();
                }
            } else {
                if (isset(get_userdata($userId)->roles) && !empty(get_userdata($userId)->roles)) {
                    return;
                }
                add_user_to_blog(get_current_blog_id(), $userId, $this->defaultRole);
            }
        } else {
            if (isset(get_userdata($userId)->roles) && !empty(get_userdata($userId)->roles)) {
                return false;
            }
            wp_update_user(array('ID' => $user_id, 'role' => $this->defaultRole));
        }
    }

    /**
     * Delete and reassign content to user id defined by reassignToUserId function.
     * @param  $userName the username that should be deleted.
     * @return array
     */

    public function deleteAccount($userToDelete)
    {
        if (is_multisite()) {
            foreach ($this->sites as $site) {
                switch_to_blog($site->blog_id);

                if ($userId = username_exists($userToDelete)) {
                    if ($reassign = $this->reassignToUserId()) {
                        wp_delete_user($userId, $reassign);
                    }
                }

                restore_current_blog();
            }
        } else {
            if ($userId = username_exists($userToDelete)) {
                if ($reassign = $this->reassignToUserId()) {
                    wp_delete_user($userId, $reassign);
                }
            }
        }
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
     * Creates a schedule of 100 update profiles in bulk
     * @return void
     */

    public function scheduleUpdateProfiles($cron = true)
    {
        $userAccounts = $this->getLocalAccounts();

        if (is_array($userAccounts) &!empty($userAccounts)) {
            $userAccounts = array_chunk($userAccounts, 250);
            foreach ((array) $userAccounts as $index => $userChunk) {
                //Schedule chunks with 60 seconds apart (minimum cron job trigger).
                if ($cron === true) {
                    wp_schedule_single_event(time() + ($index*60), 'ad_integration_bulk_update_profiles', array('userNames' => $userChunk));
                } else {
                    $this->updateProfiles($userChunk);
                }
            }
        }
    }

    /**
     * Update user profiles (bulk trigger)
     * @return void
     */

    public function updateProfiles($userNames)
    {

        if (!is_array($userNames)) {
            return;
        }

        if (!is_object($this->profile)) {
            $this->profile = new Profile();
        }

        //Include required resources
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        //Authentication
        $data = array(
            'username' => AD_BULK_IMPORT_USER,
            'password' => AD_BULK_IMPORT_PASSWORD
        );

        //Fetch index
        $userDataArray = $this->curl->request('POST', rtrim(AD_INTEGRATION_URL, "/") . '/user/get/' . implode("/", $userNames) ."/", $data, 'json', array('Content-Type: application/json'));

        //Validate json response
        if ($this->response::isJsonError($index)) {
            return false;
        }

        //Decode
        $userDataArray = json_decode($userDataArray);

        //Update fetched users
        if (is_array($userDataArray) && !empty($userDataArray)) {
            foreach ($userDataArray as $user) {
                if (isset($user->samaccountname) && $userId = username_exists($user->samaccountname)) {
                    $this->profile->update($user, $userId);
                }
            }
        }
    }
}
