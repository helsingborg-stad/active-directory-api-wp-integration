<?php

namespace adApiWpIntegration;

class NewBlog
{

    private $defaultRole;

    /**
     * Prevents password for being reset
     * @return void
     */
    public function __construct()
    {
        //Set default role
        $this->defaultRole = defined('AD_BULK_IMPORT_ROLE') ? AD_BULK_IMPORT_ROLE : "subscriber";

        add_action('wpmu_new_blog', array($this, 'addUsersToNewBlog'), 10, 6);
        add_action('ad_integration_add_users_to_site', array($this, 'addUsersToNewBlogCron'), 10, 1);
    }

     /**
     * Schedule a cron for new blog propagate.
     * @return void
     */
    public function addUsersToNewBlog()
    {
        if (!defined('AD_BULK_IMPORT_PROPAGATE') || (defined('AD_BULK_IMPORT_PROPAGATE') && !AD_BULK_IMPORT_PROPAGATE)) {
            return;
        }

        if (!wp_next_scheduled('ad_integration_add_users_to_site')) {
            wp_schedule_single_event(time() + 5, 'ad_integration_add_users_to_site', array($blogId));
        }
    }

    /**
     * Propagate users to new blog
     * @return void
     */

    public function addUsersToNewBlogCron($blogId)
    {
        set_time_limit(60*60*60);

        global $wpdb;
        $users = $wpdb->get_results("SELECT ID FROM $wpdb->users");

        foreach ($users as $user) {
            $this->addDefaultRole($user->ID, $blogId);
        }
    }

    /**
     * Adds the specified userid to a specified or all blogs
     * @param integer $userId User id to add
     * @param integer $blogId Specific blog_id (leave null for all)
     */
    public function addDefaultRole($userId, $blogId = null)
    {

        //Get role (superadmin should always be administrator)
        if (is_super_admin($userId)) {
            $role = "administrator";
        } else {
            $role = $this->defaultRole;
        }

        //Check that user id is a valid int
        if (!is_numeric($userId)) {
            return false;
        }

        // Single
        if (is_numeric($blogId)) {
            if (is_user_member_of_blog($userId, $blogId) === true) {
                return false;
            }
            add_user_to_blog($blogId, $userId, $role);
            return true;
        }

        // Multiple
        if (is_null($blogId)) {
            foreach (get_sites() as $site) {
                if (is_user_member_of_blog($userId, $site->blog_id) === true) {
                    continue;
                }
                add_user_to_blog($site->blog_id, $userId, $role);
            }
        }

        return true;
    }
}
