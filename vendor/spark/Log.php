<?php
namespace spark;

use spark\logger\Logger;

class Log {

    /**
     * @var Logger
     */
    static $instance;

    public static function __callStatic($name, $args) {
        self::$instance->log($name, $args[0], (isset($args[1]) ? $args[1] : null));
    }

    /**
     * @return Logger
     */
    public static function setLogger(Logger $logger) {
        self::$instance = $logger;
    }
}