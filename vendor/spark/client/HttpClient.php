<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/5/30
 * Time: 14:54
 */
//declare (strict_types = 1);
namespace spark\client;

use spark\client\helper\Timer;
use spark\exception\Logic;
use spf\client\message\http\Request;
use spf\client\message\http\Response;

class HttpClient extends Base {

    /**
     * @var Request
     */
    public $request;
    /**
     * @var Response
     */
    public $response;
    /**
     * @var callable
     */
    public $callback;
    /**
     * @var \swoole_client
     */
    public $client;
    protected $timeout = 10;
    protected $headers = [];
    protected $withCompleteReturn = false;
    protected $redirect_count = 0;
    protected $max_redirects = 5;
    //client重要属性:errCode/sock/host/port/headers[]/requestHeaders[]/requestMethod/cookies[]/body/statusCode
    /**
     * HTTP constructor.
     * @param int $timeout
     */
    public function __construct($timeout = 10) {
        $this->timeout = floatval($timeout);
        $this->response = new Response();//传递$this用于处理redirect
        $this->client = new  \Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
    }

    /**
     * WebSocket连续发送数据
     * @param string $data
     */
    public function push($data = '') {
        $this->args['data'] = $data;
    }

    public function withCompleteReturn() {
        $this->withCompleteReturn = true;
        return $this;
    }

    public function withSimpleReturn() {
        $this->withCompleteReturn = false;
        return $this;
    }

    /**
     * 调用方式类似
     * $http->get($url)->run();
     * $http->post($url,$data)->run();
     * $http->post($url,$data,$headers,$cookies)->run();
     * @param $method
     * @param $arguments
     */
    public function __call($method, $arguments) {
        if (!$this->request) {
            $this->request = new Request($method, $arguments);
        } else {
            //redirect或新请求
            $this->request->init($method, $arguments, $this);
        }
        return $this;
    }

    /**
     * 重定向
     * @param $location
     * @return bool
     */
    public function redirect($location, $status) {
        $this->redirect_count++;
        $exceed_max_redirect = $this->redirect_count > $this->max_redirects;
        $is_empty_location = empty($location);
        $callback = $this->callback;
        //超出最大循环或重定向地址为空,则返回错误
        if ($exceed_max_redirect || $is_empty_location) {
            $msg = $exceed_max_redirect ? 'max redirect over limit' : 'redirect location error';
            $this->close();
            Timer::del($callback);
            $e = new Logic(Logic::API_QUERY_TIMEOUT, "{$msg}, location: {$location}", $msg);
            $this->call($callback, $e);
            return false;
        } else {
            switch ($status) {
                case 307://307：对于POST请求，表示请求还没有被处理，客户端应该向Location里的URI重新发起POST请求。
                    $this->post($location, $this->request->data);
                    break;
                case 303://对于POST请求, 它表示请求已经被处理，客户端可以接着使用GET方法去请求Location里的URI
                case 302://如果果POST, 重定向为GET
                default:
                    $this->get($location);//转为GET到新地址
                    break;
            }
            return true;
        }
    }

    public function close() {
        $this->semiClose();
        $this->client = null;
        $this->callback = null;
        $this->request = null;
        $this->response = null;
    }

    public function semiClose() {
        if ($this->client && $this->client->isConnected()) {
            $this->client->close();
        }
        $this->client = null;
    }

    public function fetch(callable $callback) {
        $this->callback = $callback;
        $client = $this->client;
        $this->reInit();
        if (!$client->isConnected() && !$this->request->ip) {
            //FIXME::swoole中该函数还有bug,暂不使用
            //swoole_async_dns_lookup($this->request->host, [$this, 'fetch2']);
            $this->doFetch($this->request->host, $this->request->host);
        } else {
            $this->doFetch($this->request->host, $this->request->ip);
        }
    }

    protected function doFetch($host, $ip) {
        $client = $this->client;
        $this->request->ip = $ip;
        $this->bind();
        if (empty($ip)) {
            $err = new Logic(Logic::API_ARGUMENT_ERROR, "Invalid host: {$host}", 'Invalid arguments.');
            return $this->call($this->callback, $err);
        }
        if (!$client->isConnected()) {
            $this->client->connect($this->request->ip, $this->request->port, 1, 1);//timeout=1
        } else {
            $client->send($this->request);//返回发送长度
        }
        if ($client->isConnected()) {
            $this->setTimeout($client, $this->callback);
        }
    }

    protected function bind() {
        $client = $this->client;
        $client->on("connect", function (\swoole_client $cli) {
            $data = $this->request->__toString();
            $cli->send($data);
        });
        $client->on('close', function ($cli) {
            $cli->close();
        });
        $client->on('error', function ($cli) {
            $msg = get_called_class() . " connect to {$this->getHostName()} failed.";
            $e = new Logic(Logic::API_QUERY_TIMEOUT, $msg, 'connect error.');
            $this->call($this->callback, $e);
        });
        $client->on("receive", function (\swoole_client $cli, $data) {
            Timer::del($this->callback);
            $rs = $this->response->pack($data, $this);//可能需要触发多次才发送完数据,直接处理完才返回结果
            if (!empty($rs) && is_array($rs)) {
                /*if (!empty($rs['header']['Connection']) && $rs['header']['Connection'] === 'close') {
                    $this->semiClose();
                }*/
                if ($this->withCompleteReturn) {
                    $ret = array_merge(get_object_vars($this->request), $rs);
                } else {
                    $ret = $rs['body'];
                }
                $this->call($this->callback, $ret);
            }
        });
    }
}
