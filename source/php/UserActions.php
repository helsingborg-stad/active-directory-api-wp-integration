<?php

namespace adApiWpIntegration;

use adApiWpIntegration\Helper\Curl;
use adApiWpIntegration\Helper\Response;

/**
 * User actions in WordPress admin user list
 */
class UserActions
{
    private $input;

    public function __construct(Input $input)
    {
        $this->input = $input;

        // Check if bulk import is enabled
        if (!$this->isBulkEnabled()) {
            return;
        }

        // Add row action to user list
        add_filter('user_row_actions', [$this, 'addRowAction'], 10, 2);

        // Handle the update action
        add_action('admin_init', [$this, 'handleUpdateAction']);

        // Add admin notice for results
        add_action('admin_notices', [$this, 'showAdminNotice']);
    }

    /**
     * Add "Update from AD" row action to user list
     *
     * @param array $actions
     * @param WP_User $user
     * @return array
     */
    public function addRowAction($actions, $user)
    {
        // Only show for users who can edit users
        if (!current_user_can('edit_users')) {
            return $actions;
        }

        // Create the update from AD link
        $url = add_query_arg(
            [
                'action' => 'update_from_ad',
                'user_id' => $user->ID,
                '_wpnonce' => wp_create_nonce('update_from_ad_' . $user->ID),
            ],
            admin_url('users.php'),
        );

        $actions['update_from_ad'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($url),
            __('Update from AD', 'adintegration'),
        );

        return $actions;
    }

    /**
     * Handle the update from AD action
     */
    public function handleUpdateAction()
    {
        // Check if this is our action
        if (!isset($_GET['action']) || sanitize_text_field($_GET['action']) !== 'update_from_ad') {
            return;
        }

        // Check if user ID is provided
        if (!isset($_GET['user_id'])) {
            return;
        }

        $user_id = intval($_GET['user_id']);

        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'update_from_ad_' . $user_id)) {
            wp_die(__('Security check failed', 'adintegration'));
        }

        // Check user permissions
        if (!current_user_can('edit_users')) {
            wp_die(__('You do not have permission to perform this action', 'adintegration'));
        }

        // Get the user
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            $this->setTransientMessage('error', __('User not found', 'adintegration'));
            wp_safe_redirect(admin_url('users.php'));
            exit();
        }

        // Get username
        $username = $user->user_login;

        // Fetch user data from AD
        $userData = $this->fetchUserDataFromAD($username);

        if (!$userData) {
            $this->setTransientMessage(
                'error',
                sprintf(
                    __('User "%s" not found in Active Directory', 'adintegration'),
                    esc_html($username),
                ),
            );
            wp_safe_redirect(admin_url('users.php'));
            exit();
        }

        // Update user profile
        try {
            require_once ABSPATH . 'wp-admin/includes/user.php';

            $profile = new Profile($this->input);
            // Update profile from AD data (false = don't update password)
            $profile->update($userData, $user_id, false);

            $this->setTransientMessage(
                'success',
                sprintf(
                    __('User "%s" successfully updated from Active Directory', 'adintegration'),
                    esc_html($user->display_name),
                ),
            );
        } catch (\Exception $e) {
            $this->setTransientMessage(
                'error',
                sprintf(
                    __('Failed to update user "%s": %s', 'adintegration'),
                    esc_html($username),
                    esc_html($e->getMessage()),
                ),
            );
        }

        // Redirect back to users page
        wp_safe_redirect(admin_url('users.php'));
        exit();
    }

    /**
     * Show admin notice after update
     */
    public function showAdminNotice()
    {
        $message = get_transient('ad_integration_user_action_message');
        $type = get_transient('ad_integration_user_action_type');

        if ($message && $type) {
            $class = $type === 'success' ? 'notice-success' : 'notice-error';
            printf(
                '<div class="notice %s is-dismissible"><p>%s</p></div>',
                esc_attr($class),
                wp_kses_post($message),
            );

            // Delete transients
            delete_transient('ad_integration_user_action_message');
            delete_transient('ad_integration_user_action_type');
        }
    }

    /**
     * Set a transient message to display after redirect
     *
     * @param string $type 'success' or 'error'
     * @param string $message
     */
    private function setTransientMessage($type, $message)
    {
        set_transient('ad_integration_user_action_message', $message, 30);
        set_transient('ad_integration_user_action_type', $type, 30);
    }

    /**
     * Fetch user data from Active Directory API
     *
     * @param string $username
     * @return object|false
     */
    private function fetchUserDataFromAD($username)
    {
        $curl = new Curl();

        // Authentication
        $data = [
            'username' => constant('AD_BULK_IMPORT_USER'),
            'password' => constant('AD_BULK_IMPORT_PASSWORD'),
        ];

        // Fetch user data
        $userDataJson = $curl->request(
            'POST',
            rtrim(constant('AD_INTEGRATION_URL'), '/') . '/user/get/' . $username . '/',
            $data,
            'json',
            ['Content-Type: application/json'],
        );

        // Validate JSON response
        if (Response::isJsonError($userDataJson)) {
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
     * Check if bulk import is properly configured and enabled
     *
     * @return bool
     */
    private function isBulkEnabled()
    {
        if (!defined('AD_BULK_IMPORT') || AD_BULK_IMPORT !== true) {
            return false;
        }

        if (!defined('AD_BULK_IMPORT_USER') || !defined('AD_BULK_IMPORT_PASSWORD')) {
            return false;
        }

        if (!defined('AD_INTEGRATION_URL')) {
            return false;
        }

        return true;
    }
}
