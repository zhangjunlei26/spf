<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/5/10
 * Time: 10:51
 */
//declare (strict_types = 1);
namespace spark\coroutine;

use spark\helper\Console;
use spark\Container;

class Scheduler {

    protected $is_running = false;
    protected $task_queue;
    /**
     * @var \SplQueue
     */
    protected $tasks;

    public function __construct() {
        $this->task_queue = new \SplQueue();
    }

    /**
     * 停止执行指定的任务
     * @param int $tid
     * @return bool
     */
    function kill(Task $task) {
        foreach ($this->task_queue as $i => $_task) {
            if ($task === $_task) {
                unset($this->task_queue[ $i ]);
            }
        }
        return true;
    }

    /**
     * 添加一个生成器成为可执行的Task
     * @param \Generator $coroutine
     * @param Container  $container
     * @return Task
     */
    function add(\Generator $coroutine, Container $container = null) {
        $task = new Task($coroutine, $container);
        $this->schedule($task);
        return $task;
    }

    /**
     * 调度一个Task进入Task队列
     * @param Task $task
     */
    public function schedule(Task $task) {
        $this->task_queue->enqueue($task);
    }

    /**
     * 运行Task队列
     */
    function run() {
        $queue = &$this->task_queue;
        while (!$queue->isEmpty()) {
            try {
                $task = $queue->dequeue();
                $val = $task->run();
                if ($val instanceof SystemCall) {
                    try {
                        $val($task, $this);
                    } catch (\Throwable $e) {
                        Console::dump($e->__toString());
                        $task->setException($e);
                        $this->schedule($task);
                    }
                    continue;
                }
                if (!$task->isFinished()) {
                    $this->schedule($task);
                }
                unset($task);
            } catch (\Throwable $e) {
                $task->showException($e);
                unset($task);
            }
        }
    }
}