<?php

namespace adApiWpIntegration\Helper;

class Format
{
    /**
     * Format Windows File Time to unix timestamp.
     * @return string (unix timestamp)
     */
    public static function windowsFileTimeToUnix($time)
    {
        $magicnum = '116444735995904000';

        $time = bcsub($time, $magicnum);
        $time = bcdiv($time, '10000000', 0);

        return apply_filters('AdApi/Format/WindowsTime', $time);
    }

    /**
     * Parse display name to extract user firstname & lastname
     * @return array
     */
    public static function parseDisplayName($string, $response = array())
    {
        $string = explode(" - ", $string);

        if (is_array($string)) {
            $response['department']     = $string[1];
            $string                     = trim($string[0]);
        }

        $string = explode(" ", $string);

        if (is_array($string)) {

            //Get first name, and then remove it.
            $response['firstname']  = $string[count($string)-1];
            unset($string[count($string)-1]);

            //Check if there is one or more last name(s) and use them.
            if (!empty($string)) {
                $response['lastname']   = implode(" ", $string);
            } else {
                $response['lastname']   = "";
            }

        } else {
            $response['firstname']  = $string;
            $response['lastname']   = "";
        }

        //Uppercase first letter of each word
        $response['firstname'] = ucfirst($response['firstname']);
        $response['lastname'] = ucfirst($response['lastname']);

        return $response;
    }
}
