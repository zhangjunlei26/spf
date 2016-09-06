<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/28
 * Time: 14:04
 */
//declare (strict_types = 1);
namespace spark\client;

use spark\exception\Logic;

class Task extends Base {

    protected $client;

    public function __construct(\swoole_server $server, $call, $timeout = 10) {
        $this->timeout = floatval($timeout);
        $this->client = $server;
        $this->data = $call;
        $this->init();
    }

    public function init() {
    }

    public function send($call) {
        $this->data = $call;
        return $this;
    }

    public function fetch(callable $callback) {
        $this->callback = $callback;
        $this->client->task(serialize($this->data), -1, function ($serv, $task_id, $data) {
            $ret = unserialize($data);
            //如果不重新包装,会产生新错误 Undefined property: spark\exception\Logic::$previous
            if ($ret instanceof Logic) {
                $msg = $ret->getMessage() . '|||' . $ret->getShowMsg();
                $ret = new \ErrorException($msg, $ret->getCode(), E_NOTICE, $ret->getFile(), $ret->getLine());
            }
            $this->call($this->callback, $ret);
        });
    }

    public function close() {
        $this->callback = null;
        $this->client = null;
    }
}