<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/5/30
 * Time: 11:01
 */
//declare (strict_types = 1);
namespace spark\client;

use spark\exception\Logic;
use spark\client\helper\Timer;

class Tcp extends Base {

    /**
     * @var \swoole_client
     */
    protected $client;

    public function init() {
        $this->client = new  \Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
    }

    public function send($data) {
        $this->data = $data;
        return $this;
    }

    public function fetch(callable $callback) {
        $this->callback = $callback;
        $client = $this->client;
        $this->reInit();
        if (!$client->isConnected()) {
            $this->bind();
            $client->connect($this->ip, $this->port, $this->timeout, 1);//连接成功与否都返回true,所以不能用
        } else {
            $client->send($this->data);//返回发送长度
        }
        if ($client->isConnected()) {
            $this->setTimeout($client, $callback);
        }
    }

    protected function bind() {
        $client = $this->client;
        $client->on("connect", function ($cli) {
            $cli->send($this->data);
        });
        $client->on('close', function ($cli) {
            if (IN_DEV) {
//                Log::info(__CLASS__ . "::onClose called from ".$this->getHostName());
            }
        });
        $client->on('error', function ($cli) {
            $msg = get_called_class() . " connect to {$this->getHostName()} failed.";
            $e = new Logic(Logic::API_QUERY_TIMEOUT, $msg, 'connect error.');
            $this->call($this->callback, $e);
        });
        $client->on("receive", function ($cli, $data) {
            Timer::del($this->callback);
            $this->call($this->callback, $data);
        });
    }

    public function close() {
        if ($this->client && $this->client->isConnected()) {
            $this->client->close();
        }
        $this->client = null;
        $this->callback = null;
    }
}