<?php

namespace adApiWpIntegration\Helper;

/**
 * Class Log
 * @package adApiWpIntegration\Helper
 */
class Log
{
    /**
     * LogStackTrace
     * writing error stack to log fle
     * @param $error
     */
    public static function LogStackTrace($error)
    {
        $getNameSpace = preg_split("/\\\\/", __NAMESPACE__);

        if (defined('LOG_STACK_TRACE__BASENAME') &&
            LOG_STACK_TRACE__BASENAME === $getNameSpace[0]) {

            error_log($error->getMessage());
            error_log($error->getTraceAsString());
        }
    }
}