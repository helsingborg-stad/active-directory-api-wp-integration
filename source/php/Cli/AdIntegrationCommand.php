<?php

namespace adApiWpIntegration\Cli;

use adApiWpIntegration\BulkImport;
use adApiWpIntegration\Cleaning;
use adApiWpIntegration\Input;
use WP_CLI;

/**
 * Manage Active Directory Integration operations.
 */
class AdIntegrationCommand
{
    private $bulkImport;
    private $cleaning;
    private $input;

    public function __construct()
    {
        $this->input = new Input();
        $this->bulkImport = new BulkImport($this->input);
        $this->cleaning = new Cleaning($this->input);
    }

    /**
     * Synchronize WordPress users with Active Directory.
     *
     * Creates new user accounts from AD and removes accounts that no longer exist in AD.
     * Uses configurable deletion limits to prevent accidental mass deletions.
     *
     * ## OPTIONS
     *
     * [--max-delete-limit=<limit>]
     * : Maximum number of users to delete in one run. Default: 1000
     *
     * ## EXAMPLES
     *
     *     wp adintegration sync
     *     wp adintegration sync --max-delete-limit=500
     *
     * @when after_wp_load
     */
    public function sync($args, $assoc_args)
    {
        if (!$this->checkBulkEnabled()) {
            return;
        }

        WP_CLI::log('Starting Active Directory user synchronization...');

        // Set up environment for bulk operation
        if (!defined('DOING_CRON')) {
            define('DOING_CRON', true);
        }
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 3600); // 1 hour

        // Handle max delete limit
        $maxDeleteLimit = isset($assoc_args['max-delete-limit']) ? (int) $assoc_args['max-delete-limit'] : 1000;
        // Note: Setting $_GET is required for compatibility with existing BulkImport::cron() implementation
        $_GET['maxDeletelimit'] = $maxDeleteLimit;
        
        // Refresh input with new GET values
        $this->input = new Input();
        $this->bulkImport = new BulkImport($this->input);

        WP_CLI::log("Max deletion limit set to: {$maxDeleteLimit}");

        // Get accounts to be created and deleted
        $createAccounts = $this->bulkImport->diffUserAccounts(true);
        $deleteAccounts = $this->bulkImport->diffUserAccounts(false);

        WP_CLI::log('Found ' . count($createAccounts) . ' users to create from AD');
        WP_CLI::log('Found ' . count($deleteAccounts) . ' users to delete (not in AD)');

        // Run the synchronization
        try {
            $this->bulkImport->cron();
            WP_CLI::success('User synchronization completed successfully.');
            
            WP_CLI::log('Summary:');
            WP_CLI::log('- Users to create: ' . count($createAccounts));
            WP_CLI::log('- Users to delete: ' . count($deleteAccounts));
            
            if (count($deleteAccounts) > $maxDeleteLimit) {
                WP_CLI::warning('Deletion limit exceeded. ' . count($deleteAccounts) . ' users need deletion but limit is ' . $maxDeleteLimit);
                WP_CLI::log('To allow these deletions, run: wp adintegration sync --max-delete-limit=' . count($deleteAccounts));
            }
        } catch (\Exception $e) {
            WP_CLI::error('Failed to sync users: ' . $e->getMessage());
        }
    }

    /**
     * Update all WordPress user profiles from Active Directory.
     *
     * Updates user metadata (email, name, custom fields) for all WordPress users
     * by fetching the latest data from Active Directory.
     *
     * ## EXAMPLES
     *
     *     wp adintegration update-profiles
     *
     * @when after_wp_load
     */
    public function update_profiles($args, $assoc_args)
    {
        if (!$this->checkBulkEnabled()) {
            return;
        }

        WP_CLI::log('Starting user profile updates from Active Directory...');

        // Set up environment for bulk operation
        if (!defined('DOING_CRON')) {
            define('DOING_CRON', true);
        }
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 3600); // 1 hour

        $userAccounts = $this->bulkImport->getLocalAccounts();
        
        if (!is_array($userAccounts) || empty($userAccounts)) {
            WP_CLI::warning('No user accounts found to update.');
            return;
        }

        $totalUsers = count($userAccounts);
        WP_CLI::log("Found {$totalUsers} users to update");

        // Process in chunks of 200
        $userAccountsChunked = array_chunk($userAccounts, 200);
        $chunkCount = count($userAccountsChunked);
        WP_CLI::log("Processing in {$chunkCount} chunks of 200 users each");

        try {
            $processedCount = 0;
            foreach ($userAccountsChunked as $index => $userChunk) {
                $chunkNumber = $index + 1;
                WP_CLI::log("Processing chunk {$chunkNumber}/{$chunkCount}...");
                
                $this->bulkImport->updateProfiles($userChunk);
                $processedCount += count($userChunk);
                
                WP_CLI::log("Progress: {$processedCount}/{$totalUsers} users processed");
            }
            
            WP_CLI::success("Successfully updated profiles for {$totalUsers} users.");
        } catch (\Exception $e) {
            WP_CLI::error('Failed to update user profiles: ' . $e->getMessage());
        }
    }

    /**
     * Propagate users to all sites in a multisite network.
     *
     * Ensures all WordPress users are members of all sites in the network
     * with the configured default role.
     *
     * ## EXAMPLES
     *
     *     wp adintegration propagate
     *
     * @when after_wp_load
     */
    public function propagate($args, $assoc_args)
    {
        if (!$this->checkBulkEnabled()) {
            return;
        }

        if (!is_multisite()) {
            WP_CLI::error('User propagation is only available in multisite installations.');
            return;
        }

        if (!defined('AD_BULK_IMPORT_PROPAGATE') || AD_BULK_IMPORT_PROPAGATE !== true) {
            WP_CLI::error('User propagation is not enabled. Set AD_BULK_IMPORT_PROPAGATE to true.');
            return;
        }

        WP_CLI::log('Starting user propagation across multisite network...');

        // Set up environment for bulk operation
        if (!defined('DOING_CRON')) {
            define('DOING_CRON', true);
        }
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 3600); // 1 hour

        require_once(ABSPATH . 'wp-admin/includes/user.php');

        $sites = get_sites();
        $userAccounts = $this->bulkImport->getLocalAccounts();
        
        if (!$sites || empty($sites)) {
            WP_CLI::warning('No sites found in the network.');
            return;
        }

        if (!is_array($userAccounts) || empty($userAccounts)) {
            WP_CLI::warning('No user accounts found to propagate.');
            return;
        }

        $defaultRole = defined('AD_BULK_IMPORT_ROLE') && get_role(AD_BULK_IMPORT_ROLE) 
            ? AD_BULK_IMPORT_ROLE 
            : 'subscriber';

        $siteCount = count($sites);
        $userCount = count($userAccounts);
        WP_CLI::log("Propagating {$userCount} users to {$siteCount} sites with role: {$defaultRole}");

        try {
            $addedCount = 0;
            $skippedCount = 0;

            foreach ($sites as $siteIndex => $site) {
                $siteNumber = $siteIndex + 1;
                WP_CLI::log("Processing site {$siteNumber}/{$siteCount}: {$site->domain}{$site->path}");

                foreach ($userAccounts as $userName) {
                    $userId = username_exists($userName);
                    
                    if ($userId) {
                        if (!is_user_member_of_blog($userId, $site->blog_id)) {
                            add_user_to_blog($site->blog_id, $userId, $defaultRole);
                            $addedCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                }
            }

            WP_CLI::success('User propagation completed.');
            WP_CLI::log("Summary:");
            WP_CLI::log("- Users added to sites: {$addedCount}");
            WP_CLI::log("- Users already members: {$skippedCount}");
        } catch (\Exception $e) {
            WP_CLI::error('Failed to propagate users: ' . $e->getMessage());
        }
    }

    /**
     * Remove duplicate user accounts.
     *
     * Cleans up duplicate user entries that may have been created due to
     * caching issues or database inconsistencies.
     *
     * ## EXAMPLES
     *
     *     wp adintegration clean-users
     *
     * @when after_wp_load
     */
    public function clean_users($args, $assoc_args)
    {
        WP_CLI::log('Removing duplicate user accounts...');

        try {
            $this->cleaning->removeDuplicateUsers();
            WP_CLI::success('Duplicate users removed successfully.');
        } catch (\Exception $e) {
            WP_CLI::error('Failed to remove duplicate users: ' . $e->getMessage());
        }
    }

    /**
     * Remove orphaned user metadata.
     *
     * Cleans up user metadata entries that belong to users who no longer exist.
     *
     * ## EXAMPLES
     *
     *     wp adintegration clean-meta
     *
     * @when after_wp_load
     */
    public function clean_meta($args, $assoc_args)
    {
        WP_CLI::log('Removing orphaned user metadata...');

        try {
            // Note: Method name has typo in original Cleaning class (removeOphanUserMeta)
            $this->cleaning->removeOphanUserMeta();
            WP_CLI::success('Orphaned user metadata removed successfully.');
        } catch (\Exception $e) {
            WP_CLI::error('Failed to remove orphaned metadata: ' . $e->getMessage());
        }
    }

    /**
     * Remove empty user capabilities.
     *
     * Cleans up empty capability entries in the user metadata table.
     *
     * ## EXAMPLES
     *
     *     wp adintegration clean-capabilities
     *
     * @when after_wp_load
     */
    public function clean_capabilities($args, $assoc_args)
    {
        WP_CLI::log('Removing empty user capabilities...');

        try {
            $this->cleaning->removeEmptyCapabilities();
            WP_CLI::success('Empty capabilities removed successfully.');
        } catch (\Exception $e) {
            WP_CLI::error('Failed to remove empty capabilities: ' . $e->getMessage());
        }
    }

    /**
     * Manage individual users from Active Directory.
     *
     * Perform CRUD operations on individual users: get user info from AD,
     * update user profile, or delete user from WordPress.
     *
     * ## OPTIONS
     *
     * <action>
     * : Action to perform: get, update, or delete
     *
     * <identifier>
     * : Username or email address to identify the user
     *
     * [--create]
     * : Create user if not exists in WordPress (only with 'update' action)
     *
     * ## EXAMPLES
     *
     *     # Get user information from Active Directory
     *     wp adintegration user get john.doe
     *     wp adintegration user get john.doe@example.com
     *
     *     # Update user profile from Active Directory
     *     wp adintegration user update john.doe
     *     wp adintegration user update john.doe --create
     *
     *     # Delete user from WordPress
     *     wp adintegration user delete john.doe
     *
     * @when after_wp_load
     */
    public function user($args, $assoc_args)
    {
        if (!$this->checkBulkEnabled()) {
            return;
        }

        if (count($args) < 2) {
            WP_CLI::error('Please specify an action (get, update, delete) and a user identifier (username or email).');
            return;
        }

        $action = strtolower($args[0]);
        $identifier = $args[1];

        // Validate action
        if (!in_array($action, ['get', 'update', 'delete'])) {
            WP_CLI::error("Invalid action '{$action}'. Valid actions are: get, update, delete");
            return;
        }

        // Determine if identifier is email or username
        $isEmail = is_email($identifier);
        $username = $isEmail ? $this->getUsernameFromEmail($identifier) : $identifier;

        if (!$username) {
            WP_CLI::error("Could not find username for identifier '{$identifier}'.");
            return;
        }

        // Execute the action
        switch ($action) {
            case 'get':
                $this->getUserFromAD($username);
                break;
            case 'update':
                $createIfNotExists = isset($assoc_args['create']);
                $this->updateUserFromAD($username, $createIfNotExists);
                break;
            case 'delete':
                $this->deleteUserFromWP($username);
                break;
        }
    }

    /**
     * Get user information from Active Directory
     *
     * @param string $username
     */
    private function getUserFromAD($username)
    {
        WP_CLI::log("Fetching user information from Active Directory for: {$username}");

        $userData = $this->fetchUserDataFromAD($username);

        if (!$userData) {
            WP_CLI::error("User '{$username}' not found in Active Directory.");
            return;
        }

        // Check if user exists in WordPress
        $wpUserId = username_exists($username);
        $wpStatus = $wpUserId ? "Exists in WordPress (ID: {$wpUserId})" : "Not in WordPress";

        WP_CLI::success("User found in Active Directory");
        WP_CLI::log("\nUser Details:");
        WP_CLI::log("- Username: " . ($userData->samaccountname ?? 'N/A'));
        WP_CLI::log("- Display Name: " . ($userData->displayname ?? 'N/A'));
        WP_CLI::log("- Email: " . ($userData->mail ?? 'N/A'));
        WP_CLI::log("- Company: " . ($userData->company ?? 'N/A'));
        WP_CLI::log("- WordPress Status: {$wpStatus}");

        if (!$wpUserId) {
            WP_CLI::log("\nTo create this user in WordPress, run:");
            WP_CLI::log("  wp adintegration user update {$username} --create");
            WP_CLI::log("Or sync all users:");
            WP_CLI::log("  wp adintegration sync");
        }
    }

    /**
     * Update user from Active Directory
     *
     * @param string $username
     * @param bool $createIfNotExists
     */
    private function updateUserFromAD($username, $createIfNotExists = false)
    {
        WP_CLI::log("Updating user from Active Directory: {$username}");

        // Check if user exists in WordPress
        $wpUserId = username_exists($username);

        if (!$wpUserId) {
            if (!$createIfNotExists) {
                WP_CLI::error("User '{$username}' does not exist in WordPress. Use --create flag to create the user, or run 'wp adintegration sync' to sync all users.");
                return;
            }

            WP_CLI::log("User does not exist in WordPress. Creating...");
            $this->bulkImport->createAccount([$username]);
            $wpUserId = username_exists($username);

            if (!$wpUserId) {
                WP_CLI::error("Failed to create user '{$username}' in WordPress.");
                return;
            }

            WP_CLI::success("User created in WordPress (ID: {$wpUserId})");
        } else {
            WP_CLI::log("User exists in WordPress (ID: {$wpUserId})");
        }

        // Fetch user data from AD
        $userData = $this->fetchUserDataFromAD($username);

        if (!$userData) {
            WP_CLI::error("User '{$username}' not found in Active Directory. Cannot update profile.");
            return;
        }

        // Update user profile
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        
        $profile = new \adApiWpIntegration\Profile($this->input);
        $profile->update($userData, $wpUserId, false);

        WP_CLI::success("User profile updated successfully from Active Directory");
        WP_CLI::log("\nUpdated Information:");
        WP_CLI::log("- Display Name: " . ($userData->displayname ?? 'N/A'));
        WP_CLI::log("- Email: " . ($userData->mail ?? 'N/A'));
        WP_CLI::log("- Company: " . ($userData->company ?? 'N/A'));
    }

    /**
     * Delete user from WordPress
     *
     * @param string $username
     */
    private function deleteUserFromWP($username)
    {
        WP_CLI::log("Deleting user from WordPress: {$username}");

        $wpUserId = username_exists($username);

        if (!$wpUserId) {
            WP_CLI::error("User '{$username}' does not exist in WordPress. Nothing to delete.");
            return;
        }

        // Confirm before deletion
        WP_CLI::confirm("Are you sure you want to delete user '{$username}' (ID: {$wpUserId})?");

        require_once(ABSPATH . 'wp-admin/includes/user.php');

        $this->bulkImport->deleteAccount($username);

        // Verify deletion
        if (!username_exists($username)) {
            WP_CLI::success("User '{$username}' deleted successfully from WordPress.");
        } else {
            WP_CLI::error("Failed to delete user '{$username}'.");
        }
    }

    /**
     * Fetch user data from Active Directory API
     *
     * @param string $username
     * @return object|false
     */
    private function fetchUserDataFromAD($username)
    {
        $curl = new \adApiWpIntegration\Helper\Curl();
        $response = new \adApiWpIntegration\Helper\Response();

        // Authentication
        $data = array(
            'username' => constant('AD_BULK_IMPORT_USER'),
            'password' => constant('AD_BULK_IMPORT_PASSWORD')
        );

        // Fetch user data
        $userDataJson = $curl->request(
            'POST',
            rtrim(constant('AD_INTEGRATION_URL'), "/") . '/user/get/' . $username . "/",
            $data,
            'json',
            array('Content-Type: application/json')
        );

        // Validate JSON response
        if ($response::isJsonError($userDataJson)) {
            return false;
        }

        $userDataArray = json_decode($userDataJson);

        // The API returns an array with one user
        if (is_array($userDataArray) && !empty($userDataArray)) {
            return $userDataArray[0];
        }

        return false;
    }

    /**
     * Get username from email address
     *
     * @param string $email
     * @return string|false
     */
    private function getUsernameFromEmail($email)
    {
        // First check WordPress database
        $user = get_user_by('email', $email);
        if ($user) {
            return $user->user_login;
        }

        // If not found in WordPress, extract username from email (before @)
        $parts = explode('@', $email);
        return $parts[0];
    }

    /**
     * Check if bulk import is properly configured and enabled.
     *
     * @return bool
     */
    private function checkBulkEnabled()
    {
        if (!defined('AD_BULK_IMPORT') || AD_BULK_IMPORT !== true) {
            WP_CLI::error('Bulk import is not enabled. Set AD_BULK_IMPORT to true in your configuration.');
            return false;
        }

        if (!defined('AD_BULK_IMPORT_USER') || !defined('AD_BULK_IMPORT_PASSWORD')) {
            WP_CLI::error('Bulk import credentials not configured. Set AD_BULK_IMPORT_USER and AD_BULK_IMPORT_PASSWORD.');
            return false;
        }

        if (!defined('AD_INTEGRATION_URL')) {
            WP_CLI::error('AD_INTEGRATION_URL is not defined in your configuration.');
            return false;
        }

        return true;
    }
}
