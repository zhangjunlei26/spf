<?php
namespace spf\swoole\worker;

interface IWebSocketWorker {

    public function onStart($server, $workerId);

    public function onShutdown($server, $workerId);

    public function onTask($server, $taskId, $fromId, $data);

    public function onFinish($server, $taskId, $data);

    public function onOpen($server, $request);

    public function onClose();

    public function onMessage($server, $frame);

}
