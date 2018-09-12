<?php

namespace adApiWpIntegration;

class Database
{

    private $db;

    public function __construct()
    {
        //Get wpdb instance
        add_action('init', array($this, 'fetchDatabaseInstance'), 1);

        //Normalization of user table
        add_action('admin_init', array($this, 'forceUniqueUserNames'));
    }

    /**
     * Fetches database instance to be avabile to this class.
     * @return void
     */
    public function fetchDatabaseInstance()
    {
        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * Force user account to have a unique username
     * Why this is not default is unknown at the moment.
     * @return void
     */
    public function forceUniqueUserNames()
    {
        //Check for not unique index in user table
        $userLoginIndex = $this->db->get_row("SHOW INDEX FROM " . $this->db->users . " WHERE Key_name = 'user_login_key' AND Non_unique = 1");

        if (is_object($userLoginIndex) && !empty($userLoginIndex)) {

            //Remove old index
            $this->db->query("ALTER TABLE " . $this->db->users . " DROP INDEX user_login_key");

            //Add new index
            $this->db->query("ALTER TABLE " . $this->db->users . " ADD UNIQUE user_login_key (user_login);");
        }
    }
}
