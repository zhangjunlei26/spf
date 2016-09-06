<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/7/11
 * Time: 14:59
 */
namespace spark\swoole\worker;

use spark\Log;
use spark\logger\Logger;
use spf\helper\Console;

class Task extends Base {

    public static $taskContainer;

    public function onTask($server, $taskId, $fromId, $data) {
        parent::onTask($server, $taskId, $fromId, $data);
        $container = self::$taskContainer;
        $data = unserialize($data);
        $isValidReqFormat = false;
        $isValidApi = false;
        do {
            if (empty($data) || !is_array($data) || !is_array($data[0]) || count($data[0]) !== 2) {
                break;
            } else {
                $isValidReqFormat = true;
            }
            list($class, $method) = $data[0];
            $args = [];
            if (isset($data[1])) {
                $args = is_scalar($data[1]) ? [$data[1]] : $data[1];
            }
            $taskService = $container->register($class);
            if (!$taskService) {//task类不存在
                break;
            }
            if (!method_exists($taskService, $method)) {
                break;
            }
            $isValidApi = true;
        } while (false);
        if ($isValidApi === true && $isValidReqFormat === true) {
            try {
                $ret = call_user_func_array([$taskService, $method], $args);
            } catch (\Throwable $e) {
                //返回捕获的异常
                $ret = $e;
            }
        } else {
            if ($isValidReqFormat) {
                $err = "The task {$class}::{$method} is not available.";
            } else {
                $err = "Invalid Task call format.";
            }
            //调用Task格式非法
            $ret = new \Exception($err, -1);
        }
        $server->finish(serialize($ret));
    }

    public function onShutdown($server, $workerId) {
        if (IN_DEV) {
            echo Console::green("onWorkerStop: Task worker {$this->workerProcessName} shutdown."), PHP_EOL;
        }
    }

    public function errorHandler($errno, $errmsg, $file, $line) {
        if (!(error_reporting() & $errno)) {//不符合的,返回false,由PHP标准错误进行处理
            return false;
        }
        $err = new \ErrorException($errmsg, 0, $errno, $file, $line);
        Log::error($err->__toString());
        return true;
    }

    protected function init() {
        $config = Swh::getConf('common', 'spark');
        Log::setLogger(new Logger($config['task_logger']));//创建会话容器
        set_error_handler([$this, 'errorHandler']);
    }

}