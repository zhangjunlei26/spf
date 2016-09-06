<?php
namespace spf\swoole\worker;

interface IHttpWorker {

    public function onStart($server, $workerId);

    public function onShutdown($server, $workerId);

    public function onRequest($request, $response);

    public function onTask($server, $taskId, $fromId, $data);

    public function onFinish($server, $taskId, $data);
}
