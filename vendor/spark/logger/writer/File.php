<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/2
 * Time: 16:07
 */
namespace spark\logger\writer;

use spark\logger\Event;
use spark\logger\Logger;
use spark\logger\Writer;

/**
 * 基于文件的日志写入器
 * @author leon
 */
class File extends Writer {

    protected $path = '/tmp';
    protected $base_name = 'all.log';
    protected $group_as_dir = 1;
    protected $handlers = [];

    function __destruct() {
        if ($this->handlers) {
            foreach ($this->handlers as $fd) {
                fclose($fd);
            }
        }
    }

    function write(Event $event) {
        if ($event->num_level < $this->threshold) {//低于日志等级或没定义writer,跳过
            return true;
        }
        if (!$this->is_running) {
            $this->init();
        }
        $message = $this->render($event);
        $filename = $this->getFile($event->group);
        fwrite($this->getHandler($filename), $message);
    }

    protected function init() {
        parent::init();
    }

    function getFile($group) {
        $dir = $this->path;
        if (!isset($this->files[ $group ])) {
            if ($this->group_as_dir) {
                $dir .= "/{$group}";
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $file = "{$this->base_name}";
            } else {
                if ($group) {
                    $group = "{$group}-";
                }
                $file = "{$group}{$this->base_name}";
            }
            return $this->files[ $group ] = "{$dir}/{$file}";
        }
        return $this->files[ $group ];
    }

    protected function getHandler($file) {
        if (!isset($this->handlers[ $file ])) {
            return $this->handlers[ $file ] = fopen($file, 'a+');
        }
        return $this->handlers[ $file ];
    }

    function multi($events) {
        $msgs = [];
        if (is_array($events)) {
            foreach ($events as $type => $arr) {
                foreach ($arr as $event) {
                    $msgs[ $event->group ][] = $this->render($event);
                }
            }
            foreach ($msgs as $group => $message) {
                $filename = $this->getFile($event->group);
                fwrite($this->getHandler($filename), implode('', $message));
            }
        }
        return true;
    }
}
