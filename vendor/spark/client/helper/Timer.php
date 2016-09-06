<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/5/31
 * Time: 11:19
 */
//declare (strict_types = 1);
namespace spark\client\helper;

class Timer {

    const LOOP_TICK = 0.5;
    protected static $is_running = false;
    /**
     * @var \SplObjectStorage
     */
    protected static $pool;

    /**
     * 添加IO事件
     * @param callable $key
     * @param callable $callback
     * @param float    $timeout
     * @param bool     $replace
     */
    static function add(callable $key, callable $callback, $timeout, $replace = true) {
        if (!isset(self::$pool)) {
            self::$pool = new \SplObjectStorage();
        }
        if (!$replace && self::$pool->contains($key)) {
            return;
        }
        self::$pool[ $key ] = [microtime(true) + $timeout, $callback];
        self::run();
    }

    /**
     *    启动定时器
     */
    static function run() {
        if (self::$is_running === false) {
            swoole_timer_tick(intval(self::LOOP_TICK * 1000), function ($timer_id) {
                $pool = &self::$pool;
                if (count($pool) === 0) {
                    self::$is_running = false;
                    swoole_timer_clear($timer_id);
                } else {
                    foreach ($pool as $key) {
                        list($timeout, $callback) = $pool[ $key ];
                        if ((microtime(true) - $timeout) > 0) {
                            $pool->detach($key);
                            $callback();
                        }
                    }
                }
            });
            self::$is_running = true;
        }
    }

    static function contains(callable $key) {
        return self::$pool->contains($key);
    }

    static function del($callback) {
        unset(self::$pool[ $callback ]);
    }
}
