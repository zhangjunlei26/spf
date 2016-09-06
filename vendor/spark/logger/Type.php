<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/2
 * Time: 15:56
 */
//declare (strict_types = 1);
namespace spark\logger;

class Type {

    const ALL = 0;
    const TRACE = 1;
    const DEBUG = self::TRACE << 1;
    const INFO = self::TRACE << 2;
    const USER1 = self::TRACE << 3;
    const USER2 = self::TRACE << 4;
    const WARN = self::TRACE << 5;
    const ERROR = self::TRACE << 6;
    const CRITICAL = self::TRACE << 7;
    const OFF = self::TRACE << 8;

    /**
     * @param string $level
     *
     * @return int
     */
    static function get($level) {
        $str = __CLASS__ . '::' . strtoupper($level);
        return defined($str) ? constant($str) : -1;
    }
}

