<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/5/30
 * Time: 10:01
 */
//declare (strict_types = 1);
namespace spark\client;

use spark\client\helper\Timer;
use spark\exception\Logic;
use spark\helper\Console;

class Udp extends Base {

    /**
     * @var \Swoole\Client
     */
    protected $client;
    protected $is_connected = false;

    function init() {
        $this->client = new  \Swoole\Client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_ASYNC);
    }

    public function send($data) {
        $this->data = $data;
        return $this;
    }

    function fetch(callable $callback) {
        $client = $this->client;
        $this->callback = $callback;
        $this->reInit();
        if (!$this->is_connected) {
            $this->bind();
            $client->connect($this->ip, $this->port, $this->timeout);//flat只能为默认0,为1不能正常连接?
        } else {
            $client->send($this->data);//返回发送长度
            $this->setTimeout($client, $this->callback);
        }
        //注意:UDP没有连接成功与否的概念,所有操作都需要进行超时判断

    }

    protected function bind() {
        $client = $this->client;
        $client->on("connect", function (\Swoole\Client $cli) {
            try {
                $cli->send($this->data);
                $this->setTimeout($this->client, $this->callback);
                $this->is_connected = true;
            } catch (\Throwable $e) {
                $this->onConnectException($e);
            }
        });
        $client->on('close', function (\Swoole\Client $cli) {
        });
        $client->on("receive", function ($cli, $data) {
            $this->is_connected = true;
            Timer::del($this->callback);//删除超时判断定时任务
            $this->call($this->callback, $data);
        });
    }

    protected function onConnectException($e) {
        Console::dump($e->__toString());
        Timer::del($this->callback);//删除超时判断定时任务
        $this->is_connected = false;
        $msg = get_called_class() . " connect to {$this->getHostName()} failed.";
        $e = new Logic(Logic::API_QUERY_TIMEOUT, $msg, 'connect error.');
        $this->call($this->callback, $e);
    }

    public function close() {
        if ($this->client) {
            $this->client->close();
        }
        $this->client = null;
        $this->callback = null;
        $this->is_connected = false;
    }
}