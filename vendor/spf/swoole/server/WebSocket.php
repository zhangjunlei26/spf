<?php
namespace spf\swoole\server;

class WebSocket extends Http {

    public function onOpen($server, $request) {
        $this->worker->onOpen($server, $request);
    }

    public function onClose() {

    }

    public function onMessage($server, $frame) {
        $this->worker->onMessage($server, $frame);
    }

    protected function bindEvents() {
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        parent::bindEvents();
    }

    /**
     * @param      $host
     * @param      $port
     * @param null $type
     * @return \swoole_websocket_server
     */
    protected function listen($host, $port, $type = null) {
        return new \swoole\websocket\server($host, $port);
    }
}
