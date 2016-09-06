<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/3
 * Time: 10:04
 */
namespace spf\helper;

class Console {

    protected static $forground_colors = [
        'black'        => "0;30",
        'dark_gray'    => "1;30",
        'blue'         => "0;34",
        'light_blue'   => "1;34",
        'green'        => "0;32",
        //'green' => "32;40",
        'light_green'  => "1;32",
        'cyan'         => "0;36",
        'light_cyan'   => "1;36",
        'red'          => "0;31",
        //		'red' => "31;40",
        'light_red'    => "1;31",
        'purple'       => "0;35",
        'light_purple' => "1;35",
        'brown'        => "0;33",
        'yellow'       => "1;33",
        'light_gray'   => "0;37",
        'white'        => "1;37",
    ];
    protected static $background_colors = [
        'black'      => "40",
        'red'        => "41",
        'green'      => "42",
        'yellow'     => "43",
        'blue'       => "44",
        'magenta'    => "45",
        'cyan'       => "46",
        'light_gray' => "47",
    ];

    public static function __callStatic($color, $arg) {
        $fg = $bg = '';
        if (isset(self::$forground_colors[ $color ])) {
            $fg = self::$forground_colors[ $color ];
            $fg = "\e[{$fg}m";
        }
        $bcolor = isset($arg[1]) ? $arg[1] : null;
        if (isset(self::$background_colors[ $bcolor ])) {
            $bg = self::$background_colors[ $bcolor ];
            $bg = "\e[{$bg}m";
        }
        return $bg . $fg . $arg[0] . "\e[0m";
    }
}