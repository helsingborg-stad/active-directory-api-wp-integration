<?php

namespace adApiWpIntegration\Helper;

class Response
{
    /**
     * Format Windows File Time to unix timestamp.
     * @return string (unix timestamp)
     */
    public static function isJsonError($response)
    {
        if ($decoded = json_decode($response)) {
            $response = $decoded;
        }

        if (isset($response['error'])) {
            return true;
        }
        return false;
    }
}
