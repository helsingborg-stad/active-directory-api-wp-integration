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

    private $localAccountCache = null;
    private $reassignToUserIdCache = null;

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
        $this->defaultRole = defined('AD_BULK_IMPORT_ROLE') && get_role(AD_BULK_IMPORT_ROLE) ? AD_BULK_IMPORT_ROLE : "subscriber";

        //Create cronjob
        add_action('init', function () {
            if (is_main_site() && !wp_next_scheduled('ad_integration_bulk_import')) {
                wp_schedule_event((strtotime("midnight") + (60 * 60 * 3)), 'daily', 'ad_integration_bulk_import');
            }
        });

        //Set sites if is multiste
        add_action('init', function () {
            $this->initSites();
        });

        //Hook cron
        add_action('ad_integration_bulk_import', array($this, 'cron'));
        add_action('ad_integration_bulk_update_profiles', array($this, 'updateProfiles'));

        /**
         * The following code in constructor is manual tests/actions of functionality.
         */

        //Manually test functionality
        add_action('admin_init', function () {
            if (isset($_GET['adbulkimport'])) {

                //Define as cron
                define('DOING_CRON', true);

                //Increase memory and runtime
                ini_set('memory_limit', "2048M");
                ini_set('max_execution_time', 60 * 60 * 60);

                $this->cron();
                echo "Manually synced the users";
                exit;
            }
        }, 5);

        //Manually test update profiles cron
        add_action('admin_init', function () {
            if (isset($_GET['adbulkprofile'])) {
                define('DOING_CRON', true);

                //Increase memory and runtime
                ini_set('memory_limit', "2048M");
                ini_set('max_execution_time', 60 * 60 * 60);

                $userAccounts = $this->getLocalAccounts();
                if (is_array($userAccounts) && !empty($userAccounts)) {
                    $userAccounts = array_chunk($userAccounts, 200);
                    foreach ((array)$userAccounts as $index => $userChunk) {
                        $this->updateProfiles($userChunk);
                    }
                }
                echo "Manually bulk updated user profiles.";
                exit;
            }
        }, 5);

        //Manually propagate all users
        add_action('admin_init', function () {
            if (isset($_GET['adbulkpropagate']) && AD_BULK_IMPORT_PROPAGATE) {

                //Define as cron
                define('DOING_CRON', true);

                //Increase memory and runtime
                ini_set('memory_limit', "2048M");
                ini_set('max_execution_time', 60 * 60 * 60);

                //Include required resources
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                $sites = get_sites();
                if ($sites && !empty($sites)) {
                    $userAccounts = $this->getLocalAccounts();

                    foreach ($sites as $site) {
                        if (is_array($userAccounts) && !empty($userAccounts)) {
                            foreach ((array)$userAccounts as $userName) {
                                if ($userId = $this->userNameExists($userName)) {
                                    if (!is_user_member_of_blog($userId, $site->blog_id)) {
                                        add_user_to_blog($site->blog_id, $userId, $this->defaultRole);
                                    }
                                }
                            }
                        }
                    }
                }
                echo "Manually propagated the users.";
                exit;
            }
        }, 5);
    }

    /**
     * Init sites
     * @return null|array
     */

    public function initSites()
    {
        if (is_multisite()) {
            return $this->sites = get_sites();
        } else {
            return $this->sites = null;
        }
    }

    /**
     * Cron function, run this class
     * @return bool
     */

    public function cron()
    {

        //Increase memory and runtime
        ini_set('memory_limit', "512M");
        ini_set('max_execution_time', 60 * 60 * 60);

        //Include required resources
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        //Step 1: Create diffs
        $createAccounts = $this->diffUserAccounts(true);
        $deleteAccounts = $this->diffUserAccounts(false);

        //Sanity check, many users to remove?
        $maxDeleteLimit = isset($_GET['maxDeletelimit']) ? (int) $_GET['maxDeletelimit'] : 100;

        if (count($deleteAccounts) > $maxDeleteLimit) {
            if (is_main_site()) {
                if (get_transient('ad_api_too_many_deletions') !== 1) {

                    //Send mail
                    wp_mail(
                        get_option('admin_email'),
                        "Ad-integration plugin",
                        __("To many user deletions in queue (" . count($deleteAccounts) . "/" . $maxDeleteLimit . ") add https://test.dev/wp-admin/?adbulkimport&maxDeleteLimit=100 to your query to allow number of required deletions.",
                            "adintegration")
                    );

                    //Write to log
                    error_log("Ad-integration plugin: To many user deletions in queue (" . count($deleteAccounts) . "/" . $maxDeleteLimit . ") add https://test.dev/wp-admin/?adbulkimport&maxDeleteLimit=100 to your query to allow number of required deletions.");

                    //Prevent this mail for 24 hours
                    set_transient('ad_api_too_many_deletions', 1, 23 * HOUR_IN_SECONDS);
                }
            }
        } else {
            //Step 2: Delete these accounts
            if (is_array($deleteAccounts) && !empty($deleteAccounts)) {
                foreach ((array)$deleteAccounts as $accountName) {
                    $this->deleteAccount($accountName);
                }
            }
        }

        //Step 3: Create these accounts
        if (is_array($createAccounts) && !empty($createAccounts)) {
            foreach ((array)$createAccounts as $accountName) {
                if (!in_array($accountName, $deleteAccounts)) {
                    $this->createAccount($accountName);
                }
            }
        }

        //Step 4: Schedule profile updates
        $this->scheduleUpdateProfiles();
    }

    /**
     * Check if all details that are neeeded to run this function is defined.
     * @return bool
     */

    private function bulkEnabled()
    {
        //Check if bulk should be done
        if (!(defined('AD_BULK_IMPORT') || (defined('AD_BULK_IMPORT') && AD_BULK_IMPORT !== true))) {
            return false;
        }

        //Check if has master account details
        if (!defined('AD_BULK_IMPORT_USER') || !defined('AD_BULK_IMPORT_PASSWORD')) {
            return false;
        }

        return true;
    }

    /**
     * Return all local accountnames
     * @return array
     */

    public function getLocalAccounts()
    {
        if (!is_null($this->localAccountCache)) {
            return $this->localAccountCache;
        }

        return $this->localAccountCache = array_map('strtolower',
            $this->db->get_col("SELECT user_login FROM " . $this->db->users . " ORDER BY RAND()"));
    }

    /**
     * Returns all accountnames registered in the ad index
     * @return array
     */

    public function getAdAccounts()
    {
        //Authentication
        $data = array(
            'username' => AD_BULK_IMPORT_USER,
            'password' => AD_BULK_IMPORT_PASSWORD
        );

        //Fetch index
        $index = $this->curl->request('POST', rtrim(AD_INTEGRATION_URL, "/") . '/user/index', $data, 'json',
            array('Content-Type: application/json'));

        //Validate json response
        if ($this->response::isJsonError($index) || !json_decode($index)) {
            return false;
        }

        //Check that no errors occured
        if (json_last_error() != JSON_ERROR_NONE) {
            error_log("Ad integration: Could not read index due to the fact that the response wasen't a valid json string.");
            exit;
        }

        //Return
        return array_map('strtolower', json_decode($index));
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
            return array_diff((array)$ad, (array)$local);
        } else {
            return array_diff((array)$local, (array)$ad);
        }
    }

    /**
     * Creates a single user if it not exists.
     * @param string $userName A string with a username that corresponds to the ad username.
     * @param string $userEmail A string with a email adress that corresponds to the ad email adress.
     * @trow \Exception
     * @return void
     */

    public function createAccount($userNames)
    {
        if (!is_array($userNames)) {
            $userNames = array($userNames);
        }

        foreach ($userNames as $userName) {
            if (empty($userName)) {
                continue;
            }

            if (!in_array($userName, $this->getLocalAccounts())) {

                //Do a sanity check
                if ($this->userNameExists($userName) === false) {

                    try {
                        $userId = wp_insert_user(
                            array(
                                'user_login' => $userName,
                                'user_pass' => wp_generate_password(),
                                'user_nicename' => $userName,
                                'user_email' => $this->createFakeEmail($userName),
                                'user_registered' => date('Y-m-d H:i:s'),
                                'role' =>  $this->defaultRole
                            )
                        );
                    } catch (\Exception $e) {
                        error_log("Error: Could not create a new user using bulk data (ad-api-integration).");
                    }

                } else {
                    $userId = null;
                }

                if (is_numeric($userId)) {
                    $this->setUserRole($userId);
                }
            }
        }
    }

    /**
     * Creates a fake, temporary email adress. We do not have any real details about the account here.
     * @return string A fake randomly generated email.
     */

    public function createFakeEmail($userName)
    {
        if (defined('AD_USER_DOMAIN')) {
            return $userName . "@" . AD_USER_DOMAIN;
        } else {
            return $userName . "@" . base_convert($userName . time(), 10, 32) . ".dev";
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

            //Init sites if not done
            if (is_null($this->sites)) {
                $this->initSites();
            }

            //Get role (superadmin should always be administrator)
            if (is_super_admin($userId)) {
                $role = "administrator";
            } else {
                $role = $this->defaultRole;
            }

            //Bulk add (or just this site)
            if (defined('AD_BULK_IMPORT_PROPAGATE') && AD_BULK_IMPORT_PROPAGATE === true) {
                foreach ($this->sites as $site) {
                    if (is_user_member_of_blog($userId, $site->blog_id) === true) {
                        continue;
                    }
                    add_user_to_blog($site->blog_id, $userId, $role);
                }
            } elseif (is_user_member_of_blog($userId, get_current_blog_id()) !== true) {
                add_user_to_blog(get_current_blog_id(), $userId, $role);
            }
        } else {
            if (isset(get_userdata($userId)->roles) && !empty(get_userdata($userId)->roles)) {
                return false;
            }
            wp_update_user(array('ID' => $userId, 'role' => $this->defaultRole));
        }
    }

    /**
     * Delete and reassign content to user id defined by reassignToUserId function.
     * @param  $userName the username that should be deleted.
     * @return array
     */

    public function deleteAccount($userToDelete)
    {
        if ($userId = $this->userNameExists($userToDelete)) {
            if (is_multisite()) {
                if ($reassign = $this->reassignToUserId()) {
                    foreach ($this->sites as $site) {
                        remove_user_from_blog($userId, $site->blog_id, $reassign);
                    }
                    $this->db->delete($this->db->users, array('ID' => $userId));
                }
            } else {
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
        if (!is_null($this->reassignToUserIdCache)) {
            return $this->reassignToUserIdCache;
        }

        //If there is a configurated username for reassignment, use it.
        if (defined('AD_BULK_IMPORT_REASSIGN_USERNAME') && $userId = $this->userNameExists(AD_BULK_IMPORT_REASSIGN_USERNAME)) {
            if ($userId) {
                return $userId;
            }
        }

        // Above wasen't defined. Get first user id.
        $user = $this->db->get_col("SELECT ID FROM " . $this->db->users . " LIMIT 1");
        if (is_array($user) && !empty($user)) {
            $userId = (int)array_pop($user);
        }

        // Store to cache
        return $this->reassignToUserIdCache = $userId;

        return false;
    }

    /**
     * Creates a schedule of 200 update profiles in bulk
     * @return void
     */

    public function scheduleUpdateProfiles($cron = true)
    {
        $userAccounts = $this->getLocalAccounts();

        if (is_array($userAccounts) & !empty($userAccounts)) {
            $userAccounts = array_chunk($userAccounts, 200);
            foreach ((array)$userAccounts as $index => $userChunk) {
                //Schedule chunks with 1 second apart (minimum cron job trigger).
                if ($cron === true) {
                    if(!wp_next_scheduled('ad_integration_bulk_update_profiles', array('userNames' => $userChunk))) {
                        wp_schedule_single_event(
                            time() + 10,
                            'ad_integration_bulk_update_profiles',
                            array('userNames' => $userChunk)
                        );
                    }
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

        //Fetch user profiles
        $userDataArray = $this->curl->request('POST',
            rtrim(AD_INTEGRATION_URL, "/") . '/user/get/' . implode("/", $userNames) . "/", $data, 'json',
            array('Content-Type: application/json'));

        //Validate json response
        if ($this->response::isJsonError($userDataArray)) {
            return false;
        }

        //Decode
        $userDataArray = json_decode($userDataArray);

        //Update fetched users
        if (is_array($userDataArray) && !empty($userDataArray)) {
            foreach ($userDataArray as $user) {
                if (isset($user->samaccountname) && $userId = $this->userNameExists($user->samaccountname)) {
                    $this->profile->update($user, $userId, false); //Update profile
                    $this->setUserRole($userId); //Enshure that the user has a role on every site
                }
            }
        }

        //Remove this cron manually (WordPress builtin fails for some reason)
        wp_clear_scheduled_hook('ad_integration_bulk_update_profiles', array('userNames' => $userNames));
    }

    /**
     * Username exists
     * @param $username
     * @return numeric/bool Returns the user id found, otherwise false. Does not use usename_exists due to the creation of WpObject on each call.
     */

    public function userNameExists($username)
    {
        $user = $this->db->get_col(
            $this->db->prepare("SELECT ID FROM " . $this->db->users . " WHERE user_login = %s LIMIT 1", $username)
        );
        if (is_array($user) && !empty($user)) {
            return (int)array_pop($user);
        }
        return false;
    }
}
