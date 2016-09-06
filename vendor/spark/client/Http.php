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

use spark\exception\Logic;
use spf\Log;

class Http extends Base {

    const METHODS = ['get', 'head', 'post', 'put', 'trace', 'options', 'delete', 'upgrade'];
    /**
     * @var callable
     */
    protected $callback;
    /**
     * @var \swoole_http_client
     */
    protected $client;
    protected $timeout = 10;
    protected $ip;
    protected $port = 80;
    protected $host;
    protected $requestMethod;
    protected $args = [];
    protected $ssl = false;
    protected $path;
    protected $withCompleteReturn = false;
    //client重要属性:errCode/sock/host/port/headers[]/requestHeaders[]/requestMethod/cookies[]/body/statusCode
    /**
     * HTTP constructor.
     * @param int $timeout
     */
    public function __construct($timeout = 10) {
        $this->timeout = floatval($timeout);
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
    }

    public function withSimpleReturn() {
        $this->withCompleteReturn = false;
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
        $class = __CLASS__;
        if (!in_array($method, self::METHODS)) {
            $methods = implode('/', self::METHODS);
            $msg = "{$class}::{$method} call error, The support methods are {$methods}.";
            throw new Logic(Logic::API_METHOD_ERROR, $msg, 'The api method called in wrong format.');
        }
        if (!isset($arguments[0])) {
            $msg = "The argument URL is needed for {$class}::{$method}.";
            throw new Logic(Logic::API_ARGUMENT_ERROR, $msg, 'The api method called in wrong format.');
        } else {
            $url = $arguments[0];
            $args = parse_url($url);
            if (!isset($args['scheme']) || !isset($args['host'])) {
                $msg = "please complete the url in correct format:{$url}";
                throw new Logic(Logic::API_ARGUMENT_ERROR, $msg, 'The api called in wrong arguments.');
            } else {
                $this->host = $args['host'];
            }
            $is_https = $args['scheme'] === 'https';
            if (isset($args['port'])) {
                $this->port = $args['port'];
            } else {
                if ($is_https) {
                    $this->port = 443;
                    $this->ssl = true;
                } else {
                    $this->port = 80;
                    $this->ssl = false;
                }
            }
            $this->path = isset($args['path']) ? $args['path'] : '/';
        }

        $data = isset($arguments[1]) ? $arguments[1] : [];
        $headers = isset($arguments[2]) ? $arguments[2] : [];
        $cookies = isset($arguments[3]) ? $arguments[3] : [];
        $this->args = [
            'method'  => $method,
            'url'     => $url,
            'data'    => $data,
            'headers' => $headers,
            'cookies' => $cookies,
        ];
        return $this;
    }

    public function fetch(callable $callback) {
        $this->callback = $callback;
        if (empty($this->args)) {
            $msg = "Syntax error, please use Http like: \$rs = yield (new Http(5))->get(\$url)->run().";
            throw new Logic(Logic::API_ARGUMENT_ERROR, $msg, 'The api method called in wrong format.');
        }
        //FIXME::注意,如果带上swoole_async_dns_lookup异步调用,会导致worker异常退出,这里只用host进行连接
        //swoole_async_dns_lookup($this->host, [$this, 'fetch2']);
        $this->doFetch($this->host, $this->host);
    }

    protected function doFetch($host, $ip) {
        if (empty($ip)) {
            throw new Logic(Logic::API_ARGUMENT_ERROR, "Invalid Api host: {$host}", 'Api called failed.');
        }
        $this->ip = $ip;
        $client = $this->client;
        if (!$client || $client->ip !== $this->ip || $client->port !== $this->port) {
            //client重要属性:errCode/sock/host/port/headers[]/requestHeaders[]/requestMethod/cookies[]/body/statusCode
            $client = $this->client = new \Swoole\Http\Client($ip, $this->port, $this->ssl);
        }
        $client->on("error", function ($cli) {
            echo "onError\n";
            $cli->close();
        });
        $client->on("close", function ($cli) {
            try {
                $cli->close();
            } catch (\Throwable $e) {
                Log::error($e->__toString());
            }
        });

        $this->reInit();
        $method = $this->args['method'];
        $data = $this->args['data'];
        if ($method === 'upgrade') {//websocket
            $this->initArgs();
            $client->on('message', function ($client, $frame) {
                $this->call($this->callback, $frame);
            });
            $client->upgrade($this->path, function ($cli) {
                $this->call($this->callback, $cli->body);
                $cli->push($this->args['data']);
            });
        } elseif ($method === 'post') {
            $this->initArgs();
            $client->post($this->path, $data, function ($cli) {
                $this->call($this->callback, $cli);
            });
        } else {
            if ($data) {
                $client->setData($this->args['data']);
            }
            $this->initArgs();
            $client->get($this->path, function ($client) {
                $this->call($this->callback, $client);
            });
        }
    }

    protected function initArgs() {
        $client = $this->client;
        $header = array_merge([
            'Host'            => $this->host,
            'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.10 Safari/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, sdch',
            'Accept-Language' => 'zh-CN,zh;q=0.8,en;q=0.6',
        ], $this->args['headers']);
        $client->setHeaders($header);
        $method = $this->args['method'];
        if ($method !== 'upgrade') {
            $client->setMethod(strtoupper($method));
        }
        if ($this->args['cookies']) {
            $client->setCookies($this->args['cookies']);
        }
    }

    function call(callable $callback, $val) {
        $this->elapse();
        //client重要属性:errCode/sock/host/port/headers[]/requestHeaders[]/requestMethod/cookies[]/body/statusCode
        if ($this->withCompleteReturn) {
            call_user_func($callback, $val, $this);
        } else {
            $ret = ($val instanceof \Swoole\Http\Client) ? $val->body : get_object_vars($val);
            call_user_func($callback, $ret, $this);
        }
    }

    function close() {
//        $this->client = null;
        $this->callback = null;
    }
}
