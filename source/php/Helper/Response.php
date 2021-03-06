<?php

namespace adApiWpIntegration\Helper;

class Response
{
    /**
     * Check if the response from the server may be a error response
     * @return bool
     */
    public static function isJsonError($response)
    {

        if(is_wp_error($response)) {
            return true; 
        }

        if ($decoded = json_decode($response)) {
            $response = (array) $decoded;
        }

        if (isset($response['error'])) {
            return true;
        }
        
        return false;
    }
}
