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
    public static function parseDisplayName($string, $data)
    {
        $response = array();

        if (isset($data->sn) && !empty($data->sn)) {
            $response['firstname'] = trim(str_replace($data->sn, "", substr($string, 0, mb_strpos($string, " - "))));
            $response['lastname'] = $data->sn;
        } elseif (isset($data->mail) && mb_strpos($data->mail, ".")) {
            $response['firstname'] = trim(substr($data->mail, 0, mb_strpos($data->mail, ".")));
            $response['lastname'] = trim(substr($data->mail, mb_strpos($data->mail, ".")+1, mb_strpos($data->mail, "@")-mb_strpos($data->mail, ".")-1));
        } else {
            $tempData = array();
            $tempData = trim(substr($string, 0, mb_strpos($string, " - ")));

            if (!empty($tempData)) {

                $tempData = explode(" ", $tempData);

                if (is_array($tempData) && count($tempData)) {
                    $response['lastname'] = $tempData[0];
                    unset($tempData[0]);
                    $response['firstname'] = implode(" ", $tempData);
                }
            }
        }

        //Uppercase first letters
        $response['firstname'] = ucfirst($response['firstname']);
        $response['lastname'] = ucfirst($response['lastname']);

        return $response;
    }
}
