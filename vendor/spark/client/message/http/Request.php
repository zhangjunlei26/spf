<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/5/31
 * Time: 23:11
 */
namespace spark\client\message\http;

use spf\client\HttpClient;

class Request {

    const METHODS = ['get', 'head', 'post', 'put', 'trace', 'options', 'delete', 'upgrade'];
    //////////////////////////////////////////////////////////////////////
    //          header
    //////////////////////////////////////////////////////////////////////
    public $host;
    public $port = 80;
    public $ip;
    public $url;
    public $scheme = 'http';
    public $requestMethod;
    public $path = '/';
    public $uri;
    public $requestHeaders = [
        'Host'            => '',
        'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.10 Safari/537.36',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Encoding' => 'gzip, deflate, sdch',
        'Accept-Language' => 'zh-CN,zh;q=0.8,en;q=0.6',
    ];
    public $cookies = [];
    //////////////////////////////////////////////////////////////////////
    //          Body
    //////////////////////////////////////////////////////////////////////
    public $data = '';

    function __construct($method = "GET", $args = []) {
        $this->initNew($method, $args);
    }

    function initNew($method = "GET", $args) {
        $this->checkArgs($method, $args);
        $url = $args[0];
        $this->url = $url;
        $urlSeg = parse_url($url);
        if (!isset($urlSeg['scheme']) || !isset($urlSeg['host'])) {
            $msg = "please complete the url in correct format:{$url}";
            throw new Logic(Logic::API_ARGUMENT_ERROR, $msg, 'The api called in wrong arguments.');
        } else {
            $this->requestHeaders['Host'] = $this->host = $urlSeg['host'];
        }
        $is_https = $urlSeg['scheme'] === 'https';
        if (isset($urlSeg['port'])) {
            $this->port = $urlSeg['port'];
        } else {
            if ($is_https) {
                $this->port = 443;
                $this->ssl = true;
            } else {
                $this->port = 80;
                $this->ssl = false;
            }
        }
        $this->requestMethod = $method;
        $this->path = isset($urlSeg['path']) ? $urlSeg['path'] : '/';
        $this->uri = isset($urlSeg['query']) ? "{$this->path}?{$urlSeg['query']}" : $this->path;
        if (isset($args[1])) {
            $this->data = is_scalar($args[1]) ? $args[1] : http_build_query($args[1]);
        }
        if (isset($args[2])) {
            $this->setHeader($args[2]);
        }
        if (isset($args[3])) {
            $this->setCookie($args[3]);
        }
    }

    public function checkArgs($method, $args) {
        if (!in_array($method, self::METHODS)) {
            $methods = implode('/', self::METHODS);
            $msg = "HttpClient::{$method} call error, The support methods are {$methods}.";
            throw new Logic(Logic::API_METHOD_ERROR, $msg, 'The api method called in wrong format.');
        }
        if (!isset($args[0])) {
            $msg = "The argument URL is needed for HttpClient::{$method}.";
            throw new Logic(Logic::API_ARGUMENT_ERROR, $msg, 'The api method called in wrong format.');
        }
    }

    function setHeader($headers) {
        $h = &$this->requestHeaders;
        foreach ($headers as $k => $v) {
            $h[ $k ] = $v;
        }
        return $this;
    }

    function setCookie($arr) {
        $this->cookies = array_merge($this->cookies, $arr);
        return $this;
    }

    /**
     * 重定向或重入,进行清理
     * @param string $method
     * @param array  $args
     */
    function init($method = "GET", $args = [], HttpClient $httpClient) {
        $this->checkArgs($method, $args);
        $url = $args[0];
        $urlSeg = parse_url($url);
        //重定向到新的主机
        $host = $urlSeg['host'] ?? '';
        if ($host && $host !== $this->host) {
            $this->host = $host;
            swoole_async_dns_lookup("weibo.com", function ($host, $ip) use ($method, $args, $httpClient, $urlSeg) {
                if ($ip !== $this->ip) {
                    $isNewHost = true;
                    $this->ip = $ip;
                } else {
                    $isNewHost = false;
                }
                $this->initLast($method, $args, $httpClient, $urlSeg, $isNewHost);
            });
        } else {
            $this->initLast($method, $args, $httpClient, $urlSeg, false);
        }
    }

    function initLast($method, $args, HttpClient $httpClient, $urlSeg, $isNewHost) {
        $url = $args[0];
        $scheme = $urlSeg['scheme']??'';
        $port = $urlSeg['port'] ?? 0;
        if ($port && $port !== $this->port) {
            $isNewHost = true;
            $this->port = $port;
            $this->scheme = $scheme;
        } else {
            if ($scheme && $scheme !== $this->scheme) {
                $isNewHost = true;
                $this->scheme = $scheme;
                if ($scheme === 'https') {
                    $this->port = 443;
                    $this->ssl = true;
                } else {
                    $this->port = 80;
                    $this->ssl = false;
                }
            }
        }
        $this->path = isset($urlSeg['path']) ? $urlSeg['path'] : '/';
        $this->uri = isset($urlSeg['query']) ? "{$this->path}?{$urlSeg['query']}" : $this->path;
        if (isset($urlSeg['host'])) {//已是完整url
            $this->url = $url;
        } else {//未完整
            if ($scheme === 'https') {
                $port_seg = ($this->port !== 443) ? ":{$this->port}/" : '/';
            } elseif ($scheme !== 'http') {
                $port_seg = ($this->port !== 80) ? ":{$this->port}/" : '/';
            } else {
                $port_seg = ":{$port}/";
            }
            $this->url = $this->scheme . '://' . $this->host . $port_seg . $this->uri;
        }
        $this->requestMethod = $method;
        //只保留必要的Head
        $this->requestHeaders = array_intersect_key($this->requestHeaders, [
            'Host'            => '',
            'User-Agent'      => '',
            'Accept'          => '',
            'Accept-Encoding' => '',
            'Accept-Language' => '',
            'Connection'      => 'Keep-Alive',
        ]);
        //将cookie转移到新request
        $setCookies = $httpClient->response->headers['Set-Cookie']??[];
        //处理上次访问得到的cookie设置到新请求
        if ($setCookies) {
            $this->handleCookie($setCookies);
        }
        if (isset($args[1])) {
            $this->data = is_scalar($args[1]) ? $args[1] : http_build_query($args[1]);
        }
        if (isset($args[2])) {
            $this->setHeader($args[2]);
        }
        if (isset($args[3])) {
            $this->setCookie($args[3]);
        }

        $callback = $httpClient->callback;
        if ($isNewHost) {
            $httpClient->semiClose();
            $httpClient->client = new  \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        }
        $httpClient->response = new Response();
        $httpClient->fetch($callback);
    }

    protected function handleCookie($setCookies) {
        $ret = [];
        foreach ($setCookies as $cookie) {
            $arr = preg_split('/;\s*/', $cookie);
            $tmp = [];
            foreach ($arr as $v) {
                list($key, $val) = explode('=', $v);
                $tmp[ $key ] = $val;
            }
            $ret[] = $tmp;
        }
        foreach ($ret as &$v) {
            $cookieName = key($v);
            $cookieVal = current($v);
            if (isset($v['expires'])) {
                $t = strtotime($v['expires']);
                if ($t < time()) {
                    unset($this->cookies[ $cookieName ]);
                    continue;
                }
            }
            $this->cookies[ $cookieName ] = $cookieVal;
        }
    }

    function setUrl($url) {
        $this->url = $url;
        $info = parse_url($url);
        $this->scheme = isset($info['scheme']) ? $info['scheme'] : 'http';
        $this->port = isset($info['port']) ? $info['port'] : (('https' === $this->scheme) ? 443 : 80);
        if (!isset($info['host'])) {
            throw new \InvalidArgumentException("Request invalid url: {$url}");
        } else {
            $this->host = $info['host'];
        }
        $this->path = isset($info['path']) ? $info['path'] : "/";
        return $this;
    }

    function set($arr) {
        foreach ($arr as $k => $v) {
            $this->$k = $v;
        }
        return $this;
    }

    function setAuth($username, $password) {
        $this->username = $username;
        $this->password = $password;
        $this->requestHeaders['Authorization'] = 'BASIC ' . base64_encode($username . ':' . $password);
        return $this;
    }

    function __toString() {
        $method = strtoupper($this->requestMethod);
        $data = $this->data;
        $header = &$this->requestHeaders;
        $header['Host'] = $host = $this->host;
        $header['Referer'] = $this->url;
        if (isset($this->cookies)) {
            $header['Cookie'] = http_build_query($this->cookies, '', '; ');
        }
        if ($data) {
            if (is_array($data)) {
                $data = http_build_query($data);
            }
            switch ($method) {
                case 'POST':
                case 'PUT':
                    $header['Content-Type'] = 'application/x-www-form-urlencoded';
                    $header['Content-Length'] = strlen($data);
                    break;
                default:
            }
        }
        $req_str = "{$method} {$this->uri} HTTP/1.1";
        foreach ($header as $k => $v) {
            $req_str .= "\r\n{$k}: {$v}";
        }
        $req_str .= "\r\n\r\n{$this->data}";
        return $req_str;
    }
}
