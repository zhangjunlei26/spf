<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/5/30
 * Time: 10:25
 */
//declare (strict_types = 1);
namespace spark\client;

use spark\coroutine\Callback;
use spark\coroutine\Scheduler;
use spark\coroutine\SystemCall;
use spark\coroutine\Task;
use spark\exception\Logic;
use spark\client\helper\Timer;

class Base {

    protected $ip;
    protected $port;
    protected $data;
    protected $timeout = 5;
    protected $begin_time;
    protected $elapse = 0;
    protected $is_returned = false;
    /**
     * @var callable
     */
    protected $callback;
    protected $client;

    public function __construct($ip, $port, $data, $timeout) {
        $this->ip = $ip;
        $this->port = $port;
        $this->data = $data;
        $this->timeout = floatval($timeout);
        $this->init();
    }

    protected function init() {
    }

    function run() {
        $this->reInit();
        return new SystemCall(function (Task $task, Scheduler $scheduler) {
            $this->fetch(function ($ret, self $client) use ($task, $scheduler) {
                if ($ret instanceof \Throwable) {
                    $task->setException($ret);
                } else {
                    $task->setSendValue($ret);
                }
                $scheduler->schedule($task);
                $scheduler->run();
            });
        });
    }

    protected function reInit() {
        $this->begin_time = microtime(true);
        $this->elapse = 0;
        $this->is_returned = false;
    }

    function fetch(callable $callable) {
    }

    function renderRet($data) {
        return $data;
    }

    function __destruct() {
        $this->close();
    }

    public function close() {
        return true;
    }

    public function setTimeout($client, $callback) {
        Timer::add($callback, function () use ($client, $callback) {
            $this->close();
            $msg = get_called_class() . "::fetch from {$this->getHostName()} timeout.";
            $e = new Logic(Logic::API_QUERY_TIMEOUT, $msg, 'Fetch result timeout.');
            $this->call($callback, $e);
        }, $this->timeout);
    }

    function getHostName() {
        return $this->ip . ':' . $this->port;
    }

    function call(callable $callback, $val) {
        $this->elapse();
        call_user_func($callback, $val, $this);
    }

    protected function elapse() {
        if ($this->elapse === 0) {
            $this->elapse = microtime(true) - $this->begin_time;
        }
        return $this->elapse;
    }
}