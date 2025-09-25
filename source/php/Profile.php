<?php

namespace adApiWpIntegration;

use adApiWpIntegration\Input;

class Profile
{
    public $format = null;

    public function __construct(private Input $input)
    {
        if (is_null($this->format)) {
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
        if (AD_UPDATE_NAME && isset($data->displayname) && !empty($data->displayname)) {
            $name = $this->format::parseDisplayName($data->displayname, $data);

            $fields['first_name'] = $name['firstname'];
            $fields['last_name'] = $name['lastname'];
            $fields['display_name'] = $name['firstname'] . " " . $name['lastname'];
        }

        //Update email
        if (isset($data->mail) && is_email($data->mail) && AD_UPDATE_EMAIL) {
            $fields['user_email'] = strtolower($data->mail);
        }

        //Update companyname (only in municipio theme)
        if (isset($data->company) && !empty($data->company)) {
           //TODO: Use helper from Municipio to set company name
        }

        //Update password
        if ($updatePassword === true) {
            // Get user object
            $user = get_user_by('ID', $user_id);
            $submitPassword = $this->input->post('pwd');

            if ($user && !AD_RANDOM_PASSWORD && AD_SAVE_PASSWORD && $submitPassword && !empty($submitPassword)) {
                // Update pw if saved pw and input does not match
                if (!wp_check_password($submitPassword, $user->data->user_pass, $user->ID)) {
                    $fields['user_pass'] = $submitPassword;
                }
            } elseif (AD_RANDOM_PASSWORD) {
                $passUpdated = get_user_meta($user_id, 'pass_updated', true);
                if ($passUpdated == false || strtotime($passUpdated) < strtotime('now - 3 month')) {
                    $fields['user_pass'] = wp_generate_password();
                    update_user_meta($user_id, 'pass_updated', current_time('mysql'));
                }
            }
        }

        //Update fields
        if (count($fields) != 1) {
            wp_update_user($fields);
        }

        //Update meta
        if (AD_UPDATE_META && (is_object($data) || is_array($data))) {
            foreach ((array)$data as $meta_key => $meta_value) {
                if (!in_array($meta_key, apply_filters('adApiWpIntegration/profile/disabledMetaKey', array("sn", "samaccountname", "mail", "userprincipalname")))) {
                    update_user_meta($user_id, AD_META_PREFIX . apply_filters('adApiWpIntegration/profile/metaKey', $meta_key), $meta_value);
                }
            }
        }

        //Last updated by ad timestamp
        update_user_meta($user_id, AD_META_PREFIX . apply_filters('adApiWpIntegration/profile/metaKey', "last_sync"), date("Y-m-d H:i:s"));
    }
}
