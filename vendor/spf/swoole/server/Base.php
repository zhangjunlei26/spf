<?php
namespace spf\swoole\Server;

use spf\helper\Console;
use spf\swoole\worker\IHttpWorker;
use spf\swoole\worker\Task;

abstract class Base extends \spf\swoole\Base
{

    /**
     * @var
     */
    protected $server;
    /**
     * @var IHttpWorker
     */
    protected $worker;

    public function __construct($serverName)
    {
        parent::__construct($serverName);
    }

    public function start()
    {
        $config = $this->config;
        try {
            $listen = $this->fixListenItem($config['listen']);
            list($host, $port, $type) = array_shift($listen);
            $server = $this->listen($host, $port, $type);
            $this->server = $server;
            foreach ($listen as $v) {
                list($host, $port, $type) = $v;
                $server->addlistener($host, $port, $type);
            }
            $server->set($config['server_setting']);
            $this->bindEvents();
            $server->start();
        } catch (\Throwable $e) {
            echo Console::red($e->__toString()), PHP_EOL;
        }
    }

    protected function fixListenItem($listen)
    {
        $ret = [];
        if (is_scalar($listen)) {
            $listen = [[$listen]];
        }
        foreach ($listen as $v) {
            if (is_scalar($v)) {
                $v = [$v];
            }
            if (!isset($v[1])) {
                array_unshift($v, '0.0.0.0');
            }
            if (!isset($v[2])) {
                $v[] = SWOOLE_SOCK_TCP;
            }
            $ret[] = $v;
        }
        return $ret;
    }

    /**
     * @param      $host
     * @param      $port
     * @param null $type
     * @return
     */
    abstract protected function listen($host, $port, $type = null);

    protected function bindEvents()
    {
        $swoole = $this->server;
        $swoole->on('start', [$this, 'onStart']);
        $swoole->on('managerstart', [$this, 'onManagerStart']);
        $swoole->on('workerstart', [$this, 'onWorkerStart']);
        $swoole->on('workerstop', [$this, 'onWorkerStop']);
        if (!empty($this->config['server_setting']['task_worker_num'])) {
            $swoole->on('task', [$this, 'onTask']);
            $swoole->on('finish', [$this, 'onFinish']);
        }
    }

    public function onStart($server)
    {
        $master_process_name = sprintf(self::MASTER_PROCESS_NAME, $this->name);
        $this->setProcessName($master_process_name);
        echo Console::green("onMasterStart: {$master_process_name}"), PHP_EOL;
        print_r($server);
        file_put_contents(self::getMasterPidFile($this->name), $server->master_pid);
        file_put_contents(self::getManagerPidFile($this->name), $server->manager_pid);
    }

    protected function setProcessName($name)
    {
        return (PHP_OS !== 'Darwin') ? cli_set_process_title($name) : false;
    }

    public function onManagerStart($server)
    {
        $processName = sprintf(self::MANAGER_PROCESS_NAME, $this->name);
        $this->setProcessName($processName);
        echo Console::green("onManagerStart: {$processName}"), PHP_EOL;
    }

    public function onWorkerStart($server, $workerId)
    {
        //opcache_reset();
        $worker_process_name = $this->makeWorkerName($server, $workerId);
        $this->setProcessName($worker_process_name);
        $config = $this->config;
        //判断是task或worker
        if ($server->taskworker) {
            $class = $config['task_class'];
        } else {
            $class = $config['worker_class'];
        }
        $worker = new $class($server, $this->name, $workerId, $worker_process_name, $config);
        $this->worker = $worker;
        $worker->onStart($server, $workerId);
    }

    protected function makeWorkerName($server, $worker_id)
    {
        $type = $server->taskworker ? 'task' : 'event';//也可根据$worker_id>=worker_num也判断
        return $work_process_name = sprintf(self::WORKER_PROCESS_NAME, $this->name, $type, $worker_id);
    }

    public function onWorkerStop($server, $worker_id)
    {
        $this->worker->onShutdown($server, $worker_id);
    }

    public function onTask($server, $task_id, $from_id, $data)
    {
        $this->worker->onTask($server, $task_id, $from_id, $data);
    }

    public function onFinish($server, $task_id, $data)
    {
        $this->worker->onFinish($server, $task_id, $data);
    }
}