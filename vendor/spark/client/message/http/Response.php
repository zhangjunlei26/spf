<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/5/31
 * Time: 22:47
 */
namespace spark\client\message\http;

use spf\client\HttpClient;

class Response {

    public $headers;
    public $body = '';
    protected $buffer = '';
    protected $handle_redirect = true;
    protected $is_error = false;
    protected $is_finished = false;
    protected $trunk_length = 0;
    protected $status = [];

    function __construct($conf = []) {
        $this->set($conf);
    }

    function set($conf = []) {
        if (!empty($conf)) {
            foreach ($conf as $k => $v) {
                $this->$k = $v;
            }
        }
        return $this;
    }

    /**
     * 1.设置标记位，开始时，解析头部信息 2.合并boty，两种头部协议  3.特殊处理 重定向+超时
     * @param string $data
     */
    function pack($data, HttpClient $http_client) {
        $this->buffer .= $data;
        $headers = &$this->headers;
        do {
            if ($this->trunk_length > 0 and strlen($this->buffer) < $this->trunk_length) {
                return;
            }
            if (!empty($headers)) {
                break;//已经处理完http_header,跳到body处理判断
            }
            $parts = explode("\r\n\r\n", $data, 2);//header + body
            $ret = $this->parseHeader($parts[0]);
            if ($ret === false) {
                return;//等数据完整再处理
            }
            if (isset($parts[1])) {
                $this->buffer = $parts[1];
            }
            //handle redirect
            $status = intval($headers['Status']);
            if ($this->handle_redirect && $status >= 300 && $status < 400) {
                $location = isset($headers['Location']) ? $headers['Location'] : '';
                $location .= isset($headers['Uri']) ? $headers['Uri'] : '';
                $http_client->redirect($location, $status);
                return;
            }
        } while (false);
        $rs = $this->parseBody();
        if ($rs === true && $this->is_finished) {
            $compress_type = empty($headers['Content-Encoding']) ? '' : $headers['Content-Encoding'];
            $body = $this->decode($this->body, $compress_type);
            return ['header' => $headers, 'body' => $body];
        }
    }

    protected function parseHeader($data) {
        $head_lines = explode("\r\n", $data);
        if (is_string($head_lines)) {
            $head_lines = explode("\r\n", $head_lines);
        }
        if (empty($head_lines)) {
            return false;
        }
        $headers = &$this->headers;
        //第一行:HTTP/1.1 200 OK (http_version  status_code  message)
        list($headers['Protocol'], $headers['Status'], $headers['Msg']) = explode(' ', $head_lines[0], 3);
        unset($head_lines[0]);
        //循环处理其它header
        $cookies = [];
        foreach ($head_lines as $header) {
            $header = trim($header);
            if (empty($header)) {
                continue;
            }
            list($k, $v) = explode(': ', $header, 2);
            $k = trim($k);
            if ($k === 'Set-Cookie') {
                $cookies[] = $v;
            } else {
                $headers[ $k ] = trim($v);
            }
        }
        if ($cookies) {
            $headers['Set-Cookie'] = $cookies;
        }
        return true;
    }

    protected function parseBody() {
        //trunk
        $headers = &$this->headers;
        $is_chunked = isset($headers['Transfer-Encoding']) && $headers['Transfer-Encoding'] === 'chunked';
        if ($this->is_finished) {
            return true;
        }
        //非chunked请求,由Content-Length定义长度
        if ($is_chunked === false) {
            if (strlen($this->buffer) < $headers['Content-Length']) {
                return false;
            } else {
                $this->body = $this->buffer;
                $this->is_finished = true;
                return true;
            }
        } else {
            for (; ;) {//循环解析chunk,直到接收完
                if ($this->trunk_length === 0) {
                    $chunk_hex_len = strstr($this->buffer, "\r\n", true);//hex_chunk_len + CRLF + body
                    if ($chunk_hex_len === false) {
                        return false;
                    }
                    $this->trunk_length = $chunk_len = hexdec($chunk_hex_len);
                    if ($chunk_len === 0) {
                        return $this->is_finished = true;//最后一个标明长度为0的chunk,标示结束
                    }
                    $this->buffer = substr($this->buffer, strlen($chunk_hex_len) + 2);
                }
                $buf_len = strlen($this->buffer);
                if ($buf_len === 0 || $buf_len < $this->trunk_length) {
                    return false;//等数据接收完全
                }
                $this->body .= substr($this->buffer, 0, $this->trunk_length);
                $this->buffer = substr($this->buffer, $this->trunk_length + 2);
                $this->trunk_length = 0;
            }
        }
    }

    function decode($data, $type = 'gzip') {
        if ($type === 'gzip') {
            return gzdecode($data);
        } elseif ($type === 'compress') {
            return gzinflate(substr($data, 2, -4));
        } elseif ($type === 'deflate') {
            return gzinflate($data);
        } else {
            return $data;
        }
    }

    public function getCookies($host = null) {
        if ($host === null) {
            $host = $this->host;
        }
        return isset($this->cookies[ $host ]) ? $this->cookies[ $host ] : [];
    }
}