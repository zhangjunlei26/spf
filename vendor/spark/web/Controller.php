<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/7/12
 * Time: 17:07
 */
namespace spark\web;

use spark\Container;

class Controller {

    /**
     * @var Response
     */
    protected $response;
    /**
     * @var Request
     */
    protected $request;

    public function __construct(Container $container) {
        $this->request = $container['request'];
        $this->response = $container['response'];
//        $this->response->isJson = true;
    }

    protected function output($content) {
        if (IN_DEV) {
            $content .= $this->elapse('html');
        }
        $this->response->end($content);
        $this->response->isCompleted = true;
    }

    public function elapse($type = 'json') {
        $elapse = sprintf('%.6f秒', microtime(true) - $this->request->server['request_time_float']);
        $mem = (memory_get_peak_usage(true) / 1024 / 1024) . 'MB';
        if ($type === 'json') {
            return ['memory_used' => $mem, 'elapse' => $elapse];
        } else {
            return "<div style=\"text-align:center;color:red;\">执行时间:{$elapse}, 内存占用: {$mem}</div>";
        }
    }

    protected function json($data) {
        $ret = ['code' => 0, 'data' => $data];
        if (IN_DEV) {
            $ret = array_merge($ret, $this->elapse());
        }
        $this->_json($ret);
    }

    protected function _json($arr) {
        $content = json_encode($arr, JSON_UNESCAPED_UNICODE);
        $resp = $this->response;
        $resp->header('Content-Type', 'application/json');
        $resp->end($content);
        $resp->isCompleted = true;
    }

    protected function jsonError($code, $msg) {
        $ret = ['code' => $code, 'msg' => $msg];
        if (IN_DEV) {
            $ret = array_merge($ret, $this->elapse());
        }
        $this->_json($ret);
    }
}