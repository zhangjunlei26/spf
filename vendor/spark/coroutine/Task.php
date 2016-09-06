<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/5/10
 * Time: 11:51
 */
//declare (strict_types = 1);
namespace spark\coroutine;

use spark\Container;

class Task {

    /**
     * @var \Generator
     */
    protected $coroutine;
    /**
     * @var \SplObjectStorage
     */
    protected $container;
    /**
     * @var \SplStack
     */
    protected $call_stack;
    protected $send_value = null;
    protected $is_first_yield = true;
    protected $is_finished = false;
    protected $exception = null;

    function __construct(\Generator $coroutine, Container $container = null) {
        $this->call_stack = new \SplStack;
        $this->container = $container;
        $this->coroutine = $this->callStack($coroutine);
    }

    function callStack($gen) {
        $stack = &$this->call_stack;
        $exception = null;
        while (true) {
            try {
                if ($exception) {
                    $gen->throw($exception);
                    $exception = null;
                    continue;
                }
                $value = $gen->current();
                if ($value instanceof \Generator) {
                    $stack->push($gen);
                    $gen = $value;
                    continue;
                }
                $is_ret = $value instanceof ReturnValue;
                if (!$gen->valid() || $is_ret) {
                    if ($stack->isEmpty()) {
                        $this->is_finished = true;
                        return;
                    }
                    $gen = $stack->pop();
                    $gen->send($is_ret ? $value->getValue() : null);
                    continue;
                }
                try {
                    $sendValue = (yield $value);
                } catch (\Throwable $e) {
                    $gen->throw($e);
                    continue;
                }
                $gen->send($sendValue);
            } catch (\Throwable $exception) {
                if ($stack->isEmpty()) {
                    throw $exception;
                }
                $gen = $stack->pop();
            }
        }
    }

    public function __destruct() {
        //Console::dump("on Task destruct");
        unset($this->call_stack, $this->coroutine, $this->container);
    }

    public function getContainer() {
        return $this->container;
    }

    public function run() {
        $co = &$this->coroutine;
        if ($this->is_first_yield) {
            $this->is_first_yield = false;
            $retval = $co->current();
        } elseif ($this->exception) {
            $retval = $co->throw($this->exception);
            $this->exception = null;
        } else {
            $retval = $co->send($this->send_value);
            $this->send_value = null;
        }
        return $retval;
    }

    public function showException($e) {
        $app = $this->container['app'];
        $extra = ['Container' => $this->container->dump()];
        $app->exceptionHandler($e, $extra);
    }

    public function setException($exception) {
        $this->exception = $exception;
    }

    public function setSendValue($send_value) {
        $this->send_value = $send_value;
    }

    public function isFinished() {
        return $this->is_finished;
    }

}