<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/2
 * Time: 15:57
 */
namespace spark\logger;

class Logger {

    protected $threshold = Type::ALL;
    protected $buffer = [];
    protected $optmizer = false;
    protected $writers = [];

    function __construct($cfg = []) {
        if (!empty($cfg['optmizer'])) {
            register_shutdown_function([$this, 'flush']);
            $this->optmizer = true;
        }
        if (!empty($cfg['threshold'])) {
            $threshold = Type::get($cfg['threshold']);
            if ($threshold === -1) {
                throw new \InvalidArgumentException('The Logger threshold configuration in wrong value.');
            }
            $this->threshold = $threshold;
        }
        if ($cfg['writer']) {
            foreach ($cfg['writer'] as $key => $conf) {
                $writer_class = __NAMESPACE__ . "\\writer\\{$key}";
                if (!class_exists($writer_class)) {
                    throw new \Exception("The Class {$writer_class} not found!");
                }
                $this->writers[ $key ] = new $writer_class($conf);
            }
        }
    }

    function __call($name, $arg) {
        $this->log($name, $arg[0], isset($arg[1]) ? $arg[1] : '');
    }

    function log($type, $msg, $group = '') {
        $level_num = Type::get($type);
        if (empty($this->writers)) {
            return true;//未定义writer跳过
        }
        $backtrace = debug_backtrace()[1];
        $event = new Event($type, $msg, $group, $level_num, $backtrace['file'], $backtrace['line']);
        if (!$this->optmizer) {
            $this->write($event);
        } else {
            $this->buffer[ $type ][] = $event;
        }
    }

    protected function write(Event $event) {
        foreach ($this->writers as $writer) {
            $writer->write($event);
        }
    }

    function flush() {
        if (empty($this->buffer)) {
            return true;
        }
        foreach ($this->writers as $writer) {
            $writer->multi($this->buffer);
        }
        unset($this->buffer);
    }
}