<?php

namespace adApiWpIntegration;

class Profile
{

    public $format;

    public function __construct()
    {
        $this->format = new Helper\Format();
    }

    /**
     * Update user profile details
     * @return void
     */
    public function update($data, $user_id)
    {
        //Update name
        if ($data->displayname && AD_UPDATE_NAME) {

            $name = $this->format::parseDisplayName($data->displayname);

            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $name['firstname'],
                'last_name' => $name['lastname']
            ));
        }

        //Update email
        if (is_email($data->mail) && !email_exists($data->mail) && AD_UPDATE_EMAIL) {
            wp_update_user(array(
                'ID' => $user_id,
                'user_email' => strtolower($data->mail)
            ));
        }

        //Auto generate new password (keeping wp secure)
        if (AD_SAVE_PASSWORD && isset($_POST['pwd'])) {
            wp_set_password($_POST['pwd'], $user_id);
        } elseif (AD_RANDOM_PASSWORD) {
            wp_set_password(wp_generate_password(), $user_id);
        }

        //Update meta
    }
}
