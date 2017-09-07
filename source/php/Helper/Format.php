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
}
