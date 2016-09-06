<?php
namespace spf\swoole\server;

class Http extends Base {

    public function onRequest($request, $response) {
        $this->worker->onRequest($request, $response);
    }

    /**
     * @param      $host
     * @param      $port
     * @param null $type
     * @return \swoole_http_server
     */
    protected function listen($host, $port, $type = null) {
        return new \Swoole\Http\Server($host, $port, SWOOLE_BASE);
//        return new \swoole_http_server($host, $port);
    }

    protected function bindEvents() {
        $this->server->on('request', [$this, 'onRequest']);
        parent::bindEvents();
    }
}