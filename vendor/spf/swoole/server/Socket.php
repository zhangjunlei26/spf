<?php
namespace spf\swoole\server;

class Socket extends Base {

    public function onConnect($server, $fd, $from_id) {
        $this->worker->onConnect($server, $fd, $from_id);
    }

    public function onClose($server, $clientId, $from_id) {
        $this->worker->onClose($server, $clientId, $from_id);
    }

    public function onReceive($server, $fd, $from_id, $data) {
        $this->worker->onReceive($server, $fd, $from_id, $data);
    }

    /**
     * @param      $host
     * @param      $port
     * @param null $type
     * @return
     */
    protected function listen($host, $port, $type = SWOOLE_SOCK_TCP) {
        return new \Swoole\Server($host, $port, SWOOLE_PROCESS, $type);
    }

    protected function bindEvents() {
        $swoole = $this->server;
        $swoole->on('connect', [$this, 'onConnect']);
        $swoole->on('close', [$this, 'onClose']);
        $swoole->on('receive', [$this, 'onReceive']);
        parent::bindEvents();
    }
}