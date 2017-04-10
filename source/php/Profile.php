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
        if ($data->displayname) {
            $name = $this->parseDisplayName($data->displayname);

            $t = wp_update_user(array(
                'ID' => $user_id,
                'first_name' => ucfirst($name['firstname']),
                'last_name' => ucfirst($name['lastname'])
            ));
        }

        //Update email
        if (is_email($data->mail) && !email_exists($data->mail)) {
            wp_update_user(array(
                'ID' => $user_id,
                'user_email' => strtolower($data->mail)
            ));
        }

        //Auto generate new password (keeping wp secure)
        wp_set_password(wp_generate_password(), $user_id);
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
