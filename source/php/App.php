<?php

namespace adApiWpIntegration;

class App
{
    private $curl;
    private $username;
    private $password;
    private $userId;

    public function __construct($username)
    {

        //Do not run if undefined
        if (!defined('AD_INTEGRATION_URL')) {
            return false;
        }

        //Do not run if not an url
        if (filter_var(AD_INTEGRATION_URL, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        //Init
        add_action('wp_authenticate', array($this,'init'));

    }

    public function init($username)
    {

        //Store to class
        $this->username = $username;
        $this->password = isset($_POST['pwd']) ? $_POST['pwd'] : "";
        $this->userId   = $this->getUserID($username);

        if (is_numeric($this->userId) && !empty($this->userId)) {

            //Init required classes
            $this->curl = new Helper\Curl();
            $this->profile = new Profile();

            //Fetch user from api
            $result = $this->fetchUser($this->username, $this->password);

            //Validate signon
            if ($this->validateLogin($result, $this->username)) {

                //Update user profile
                $this->profile->update($result, $this->userId);

                //Signon
                $this->signOn();

                //Redirect to admin panel
                wp_redirect(admin_url("?auth=active-directory"));
                exit;
            }
        }
    }

    private function fetchUser($username, $password)
    {
        if (!empty($username) && !empty($password) && is_numeric($this->userId)) {

            //Create login post data
            $data = array(
                'username' => $username,
                'password' => $password
            );

            //Make Curl
            $result = $this->curl->request('POST', 'https://intranat.helsingborg.se/ad-api/user/current', $data, 'json', array('Content-Type: application/json'));

            //Decode
            $result = json_decode($result);

            //Return result
            if (is_array($result)) {
                $result = array_pop($result);
            }

            return $result;
        }

        return false;
    }

    private function validateLogin($data, $username)
    {
        if (isset($data->error)) {
            return false;
        }

        if (strtolower($data->samaccountname) == strtolower($username)) {
            return true;
        }

        return null;
    }

    private function getUserID($usernameOrEmail)
    {
        $user = get_user_by(is_email($usernameOrEmail) ? 'email' : 'slug', $usernameOrEmail);

        if (is_object($user) && isset($user->ID)) {
            return $user->ID;
        }

        return null;
    }

    private function signOn()
    {
        wp_set_auth_cookie($this->userId, true);
    }
}
