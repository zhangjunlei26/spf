<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/7
 * Time: 23:01
 */
namespace spark\coroutine;

class Coroutine {

    static function asynCall(callable $callback) {
        return new SystemCall(function (Task $task, Scheduler $scheduler) use ($callback) {
            $callback(function ($ret) use ($task, $scheduler) {
                if ($ret instanceof \Throwable) {
                    $task->setException($ret);
                } else {
                    $task->setSendValue($ret);
                }
                $scheduler->schedule($task);
                $scheduler->run();
            });
        });
    }

    static function getTask() {
        return new self(function (Task $task, Scheduler $scheduler) {
            $task->setSendValue($task);
            $scheduler->schedule($task);
        });
    }

    /**
     * 删除Task任务,如果未指定则删除自己
     * @param Task $task4kill
     * @return SystemCall
     */
    static function kill(Task $task4kill = null) {
        return new self(function (Task $task, Scheduler $scheduler) use ($task4kill) {
            if ($task4kill) {
                $scheduler->kill($task4kill);
                $scheduler->schedule($task);
            } else {
                //不添加自身到任务队列,则为删除自己
            }
        });
    }

    static function newTask(\Generator $coroutine) {
        return new self(function (Task $task, Scheduler $scheduler) use ($coroutine) {
            $task->setSendValue($scheduler->add($coroutine));
            $scheduler->schedule($task);
        });
    }

    static function ret($value = null) {
        return new ReturnValue($value);
    }

    static function getContainer() {
        $container = (yield new SystemCall(function (Task $task, Scheduler $scheduler) {
            $task->setSendValue($task->getContainer());
            $scheduler->schedule($task);
            $scheduler->run();
        }));
        yield new ReturnValue($container);
    }
}