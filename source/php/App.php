<?php

namespace adApiWpIntegration;

class App
{

    private $curl;

    public function __construct()
    {
        $this->curl = new Helper\Curl();


    }

    public function fetchUser($username, $password)
    {
        if (!empty($username) && !empty($password) && !username_exists($username)) {

            //Create login post data
            $data = array(
                'username' => $username,
                'password' => $password
            );

            //Make Curl
            $result = $this->curl->request('POST', 'https://intranat.helsingborg.se/ad-api/user/current', $data);

            var_dump($result);

            //Return result
            return $result;

        }

        return false;
    }

    public function validateLogin($data)
    {
        if ($data) {
            return true;
        }

        return false;
    }

    public function validateLocalUsername()
    {
    }
}
