<?php

namespace adApiWpIntegration;

class App
{

    private $curl;

    public function __construct()
    {
        $this->curl = new Helper\Curl();

        $result = $this->fetchUser("-", "-");

        if ($this->validateLogin($result)) {
            $this->updateUserProfile($result);
        }
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
            $result = $this->curl->request('POST', 'https://intranat.helsingborg.se/ad-api/user/current', $data, 'json', array('Content-Type: application/json'));

            //Return result
            return json_decode($result)[0];
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

    public function updateUserProfile($data)
    {

        if ($data->displayname) {
            $parsed = $this->parseDisplayName($data->displayname);
            var_dump($parsed);
        }
    }

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

    private function validateField($string)
    {
        if (!empty($string)) {
            return true;
        }
        return false;
    }
}
/*

[{"sn":"Thulin","title":"Systemutvecklare","postalcode":"25289","physicaldeliveryofficename":"R\u00e5dhuset","telephonenumber":"042103919","displayname":"Thulin Sebastian - SLF","memberof":"CN=A-FR-StratsysUsersSync,OU=Filter Groups,OU=Managed Groups,OU=Rights,OU=Groups,DC=hbgadm,DC=hbgstad,DC=se","department":"Kommunikation - Webbenhet","company":"Stadsledningsf\u00f6rvaltningen","streetaddress":"Drottninggatan 2","lastlogon":"131360499586656379","primarygroupid":"513","samaccountname":"seno1000","mail":"Sebastian.Thulin@helsingborg.se","mobile":"0723949388","extensionattribute3":"SLF Webbenhet","dn":"C"}]*/
