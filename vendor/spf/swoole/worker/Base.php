<?php
namespace spf\swoole\worker;

use spf\Container;
use spf\helper\Console;
use spf\Log;
use spf\logger\Logger;

class Base {

    protected $workId;
    protected $name;
    protected $workerProcessName;
    protected $config;
    protected $logger;
    protected $server;

    public function __construct($server, $serverName, $workerId, $processName, $config) {
        $this->server = $server;
        $this->name = $serverName;
        $this->workId = $workerId;
        $this->workerProcessName = $processName;
        $this->config = $config;
        $this->init();
    }

    protected function init() {
    }

    public function onStart($server, $workerId) {
        $this->server = $server;
        echo Console::green("onWorkerStart: {$this->workerProcessName}\n");
    }

    public function onShutdown($server, $workerId) {
        echo Console::green("onWorkerStop: {$this->workerProcessName}"), PHP_EOL;
    }

    public function onTask($server, $taskId, $fromId, $data) {
    }

    public function onFinish($server, $taskId, $data) {
    }
}
