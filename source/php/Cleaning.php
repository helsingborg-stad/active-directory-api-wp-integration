<?php

namespace adApiWpIntegration;

/**
 * Cleaing functionality
 * Due to some dump cahing behaviour, wordpress creates scappy data. Clean that away.
 **/

class Cleaning
{
    private $db;


    public function __construct()
    {

        //Check if is runable
        if ($this->cleaningEnabled() === false) {
            return;
        }

        //Globals
        global $wpdb;

        //Init
        $this->db = $wpdb;

        //Init crons
        $this->initCronJobs();

        //Hook cron
        add_action('ad_integration_cleaning_duplicate_users', array($this, 'removeDuplicateUsers'));
        add_action('ad_integration_cleaning_orphan_meta', array($this, 'removeOphanUserMeta'));
        add_action('ad_integration_cleaning_capabilities', array($this, 'removeEmptyCapabilities'));
    }

    public function initCronJobs()
    {

        //Create duplciate users cron
        add_action('init', function () {
            if (!wp_next_scheduled('ad_integration_cleaning_duplicate_users')) {
                wp_schedule_event((strtotime("midnight") + (60*60*4)), 'daily', 'ad_integration_cleaning_duplicate_users');
            }
        });

        //Create orphan meta cron
        add_action('init', function () {
            if (!wp_next_scheduled('ad_integration_cleaning_orphan_meta')) {
                wp_schedule_event((strtotime("midnight") + (60*60*4)), 'daily', 'ad_integration_cleaning_orphan_meta');
            }
        });

        //Create empty cap cron
        add_action('init', function () {
            if (!wp_next_scheduled('ad_integration_cleaning_capabilities')) {
                wp_schedule_event((strtotime("midnight") + (60*60*4)), 'daily', 'ad_integration_cleaning_capabilities');
            }
        });
    }


    /**
     * Deletes duplicate users
     * @return void
     */

    public function removeDuplicateUsers()
    {
        $this->db->qyery("DELETE FROM " . $this->db->users . "
        WHERE id NOT IN (
            SELECT *
            FROM (
                SELECT MIN(id)
                FROM " . $this->db->users . "
                GROUP BY user_login
            ) temp
        )");
    }

    /**
     * Deletes Orphaned meta
     * @return void
     */
    public function removeOphanUserMeta()
    {
        $this->db->qyery("DELETE FROM " . $this->db->usermeta . "
        WHERE NOT EXISTS (
          SELECT * FROM " . $this->db->users . "
            WHERE " . $this->db->usermeta . ".user_id = " . $this->db->users . ".ID
        )");
    }

    /**
     * Deletes empty capabilities
     * @return void
     */
    public function removeEmptyCapabilities()
    {
        $this->db->qyery("DELETE FROM " . $this->db->usermeta . " WHERE meta_key LIKE '%" . $this->db->base_prefix . "_%capabilities%' AND meta_value = 'a:0:{}'");
    }

    /**
     * Check if cleaning should be done now and then
     * @return bool
     */
    private function cleaningEnabled()
    {
        if (!(defined('AD_CLEANING') || (defined('AD_CLEANING') && AD_CLEANING !== true))) {
            return false;
        }
        return true;
    }
}
