<?php

namespace adApiWpIntegration;

class Profile
{

    /**
     * Update user profile details
     * @return void
     */
    public function update($data, $user_id)
    {
        //Update name
        if ($data->displayname && AD_UPDATE_NAME) {
            $name = $this->parseDisplayName($data->displayname);

            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => ucfirst($name['firstname']),
                'last_name' => ucfirst($name['lastname'])
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
        if (AD_SAVE_PASSWORD) {
            wp_set_password($_POST['pwd'], $user_id);
        } else {
            wp_set_password(wp_generate_password(), $user_id);
        }
    }

    /**
     * Parse recived data
     * @return array
     */
    private function parseDisplayName($string, $response = array())
    {
        $string = explode(" - ", $string);

        if (is_array($string)) {
            $response['department']     = $string[1];
            $string                     = $string[0];
        }

        $string = explode(" ", $string);

        if (is_array($string)) {
            $response['firstname']  = $string[1];
            $response['lastname']   = $string[0];
        } else {
            $response['firstname']  = $string;
            $response['lastname']   = "";
        }

        return $response;
    }
}
