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
}
