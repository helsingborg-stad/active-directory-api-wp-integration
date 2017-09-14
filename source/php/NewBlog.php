<?php

namespace adApiWpIntegration;

class NewBlog
{

    /**
     * Prevents password for being reset
     * @return void
     */
    public function __construct()
    {
        add_action('wpmu_new_blog', array($this, 'addUsersToNewBlog'));
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
        // Single
        if ($blogId) {
            if (is_user_member_of_blog($userId, $blogId)) {
                return false;
            }

            add_user_to_blog($blogId, $userId, $this->defaultRole);
            return true;
        }

        // Multiple
        foreach (get_sites() as $site) {
            if (is_user_member_of_blog($userId, $site->blog_id)) {
                continue;
            }

            add_user_to_blog($site->blog_id, $userId, $this->defaultRole);
        }

        return true;
    }
}
