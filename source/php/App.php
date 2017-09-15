<?php

namespace adApiWpIntegration;

class App
{
    private $curl;
    private $username;
    private $password;
    private $userId;

    /**
     * Init plugin with construct, only if constant is set and valid
     * @return void
     */
    public function __construct()
    {

        //Do not run if undefined
        if (!defined('AD_INTEGRATION_URL')) {
            return false;
        }

        //Do not run if not an url
        if (filter_var(AD_INTEGRATION_URL, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        //Disable emails to be sent
        $this->disabledNotificationEmails();

        //Default settings
        add_action('init', array($this, 'defaultSettings'), 10);

        //Init
        add_action('wp_authenticate', array($this, 'hijackLogin'), 20);
    }

    /**
     * Since all account handling is automatic, we dosen't want to send any email.
     * @return void
     */

    public function disabledNotificationEmails()
    {
        add_filter('send_password_change_email', '__return_false');
        add_filter('send_email_change_email', '__return_false');
    }

    /**
     * Default to basic settings if constants is undefined.
     * @return void
     */

    public function defaultSettings()
    {

        //Update the users first and last name
        if (!defined('AD_UPDATE_NAME')) {
            define('AD_UPDATE_NAME', true);
        }

        //Update the users email
        if (!defined('AD_UPDATE_EMAIL')) {
            define('AD_UPDATE_EMAIL', true);
        }

        //Update the users meta data
        if (!defined('AD_UPDATE_META')) {
            define('AD_UPDATE_META', true);
        }

        //Update the users meta data
        if (!defined('AD_META_PREFIX')) {
            define('AD_META_PREFIX', "ad_");
        }

        //Save the password entered by the user (this decreases the security)
        if (!defined('AD_SAVE_PASSWORD')) {
            define('AD_SAVE_PASSWORD', false);
        }

        // Create a random passowrd
        if (!defined('AD_RANDOM_PASSWORD')) {
            define('AD_RANDOM_PASSWORD', true);
        }

        // Bulk import default role
        if (!defined('AD_BULK_IMPORT_ROLE')) {
            define('AD_BULK_IMPORT_ROLE', "subscriber");
        }

        //Propagate role
        if (!defined('AD_BULK_IMPORT_PROPAGATE')) {
            define('AD_BULK_IMPORT_PROPAGATE', true);
        }
    }

    /**
     * Init login process.
     * @return void
     */
    public function hijackLogin($username)
    {

        //Translate email login to username
        if (is_email($username)) {
            $username = $this->emailToUsername($username);
        }

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
            if ($this->validateLogin($result, $this->username) && $result !== false) {

                //Update user profile
                $this->profile->update($result, $this->userId);

                //Signon
                $this->signOn();

                //Redirect to admin panel / frontpage
                if (in_array('subscriber', (array) $user->roles)) {
                    if (is_multisite()) {
                        wp_redirect(network_home_url('/'));
                        exit;
                    }
                    wp_redirect(home_url('/'));
                    exit;
                }

                wp_redirect(apply_filters('adApiWpIntegration/login/defaultRedirect', admin_url("?auth=active-directory")));
                exit;
            }
        }
    }


    /**
     * Get information from the api-service
     * @return object / false
     */
    private function fetchUser($username, $password)
    {
        if (!empty($username) && !empty($password) && is_numeric($this->userId)) {

            //Create login post data
            $data = array(
                'username' => $username,
                'password' => $password
            );

            //Json handler
            $response = new Helper\Response();

            //Make Curl
            $result = $this->curl->request('POST', rtrim(AD_INTEGRATION_URL, "/") . '/user/current', $data, 'json', array('Content-Type: application/json'));

            //Is json error
            if ($response::isJsonError($result)) {
                return false;
            }

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

    /**
     * Validate that this is a true callback
     * @return bool / null
     */
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

    /**
     * Get username with email or slug (username)
     * @return int/null
     */
    private function getUserID($usernameOrEmail)
    {
        $user = get_user_by(is_email($usernameOrEmail) ? 'email' : 'login', $usernameOrEmail);

        if (is_object($user) && isset($user->ID)) {
            return $user->ID;
        }

        return null;
    }

    /**
     * Authenticate user
     * @return void
     */
    private function signOn()
    {
        wp_set_auth_cookie($this->userId, true);
    }

    /**
     * Translate email to username
     * @return void
     */
    private function emailToUsername($email = null)
    {
        if ($user = get_user_by('email', $email)) {
            if (isset($user->user_login)) {
                return $user->user_login;
            }
        }

        return null;
    }
}
