<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/2
 * Time: 16:10
 */
namespace spark\logger;

/**
 * 日志事件
 *
 * @author leon
 */
class Event {

    public $microtime;
    public $level;
    public $group;
    public $msg;
    public $file;
    public $line;
    public $process_id;
    public $num_level;
    public $host = '127.0.0.1';

    function __construct($level, $msg, $group, $num_level, $file, $line) {
        $this->microtime = microtime(true);
        $this->process_id = getmypid();
        $this->level = $level;
        $this->msg = $msg;
        $this->group = $group ?: '';
        $this->file = $file;
        $this->line = $line;
        $this->num_level = $num_level;
        if (isset($_SERVER['SERVER_ADDR'])) {
            $this->host = $_SERVER['SERVER_ADDR'];
        } elseif (function_exists('swoole_get_local_ip')) {
            $this->host = implode(';', swoole_get_local_ip());
        }
    }

    public function __toString() {
        $date = date('c', $this->microtime);
        $level = $this->level;
        $group = $this->group ? $this->group . ':' : '';
        $msg = $this->msg;
        if (!is_scalar($msg)) {
            $msg = var_export($msg, true);
        }
        return "{$date} [host: {$this->host} pid:{$this->process_id} {$this->file}:{$this->line} {$group}{$level}]\n{$msg} \n\n";
    }
}

