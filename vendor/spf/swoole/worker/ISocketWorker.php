<?php
namespace spf\swoole\worker;

interface ISocketWorker {

    public function onStart($server, $workerId);

    public function onShutdown($server, $workerId);

    public function onConnect($server, $clientId, $fromId);

    public function onReceive($server, $clientId, $fromId, $data);

    public function onClose($server, $clientId, $fromId);

    public function onTask($server, $taskId, $fromId, $data);

    public function onFinish($server, $taskId, $data);

}
