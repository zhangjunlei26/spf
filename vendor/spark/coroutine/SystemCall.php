<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/5/10
 * Time: 10:51
 */
namespace spark\coroutine;

use spark\helper\Console;

class SystemCall {

    protected $callback;

    public function __construct(callable $callback) {
        $this->callback = $callback;
    }

    public function __invoke(Task $task, Scheduler $scheduler) {
        $callback = $this->callback;
        $callback($task, $scheduler);
    }

    public function __destruct() {
        unset($this->callback);
    }
}