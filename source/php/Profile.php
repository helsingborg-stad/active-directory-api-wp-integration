<?php

namespace adApiWpIntegration;

class Profile
{

    public $format = null;

    public function __construct()
    {
        if(is_null($this->format)) {
            $this->format = new Helper\Format();
        }
    }

    /**
     * Update user profile details
     * @return void
     */
    public function update($data, $user_id, $updatePassword = true)
    {

        //Basic definition with only an id
        $fields = array('ID' => $user_id);

        //Update name
        if ($data->displayname && AD_UPDATE_NAME) {
            $name = $this->format::parseDisplayName($data->displayname);
            $fields['first_name'] = $name['firstname'];
            $fields['last_name'] = $name['lastname'];
        }

        //Update email
        if (isset($data->mail) && is_email($data->mail) && AD_UPDATE_EMAIL) {
            $fields['user_email'] = strtolower($data->mail);
        }

        //Update fields
        if(count($fields) != 1) {
            wp_update_user($fields);
        }

        //Update password
        if($updatePassword === true) {
            if (!AD_RANDOM_PASSWORD && AD_SAVE_PASSWORD && isset($_POST['pwd']) && !empty($_POST['pwd'])) {
                wp_set_password($_POST['pwd'], $user_id);
            } elseif (AD_RANDOM_PASSWORD) {
                wp_set_password(wp_generate_password(), $user_id);
            }
        }

        //Update meta
        if (AD_UPDATE_META && (is_object($data)||is_array($data))) {
            foreach ((array) $data as $meta_key => $meta_value) {
                update_user_meta($user_id, AD_META_PREFIX . apply_filters('adApiWpIntegration/profile/metaKey', $meta_key), $meta_value);
            }
        }

        //Last updated by ad timestamp
        update_user_meta($user_id, AD_META_PREFIX . apply_filters('adApiWpIntegration/profile/metaKey', "last_sync"), date("Y-m-d H:i:s"));

    }
}
