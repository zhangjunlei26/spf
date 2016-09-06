<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/2
 * Time: 16:09
 */
namespace spark\logger;

/**
 * 日志写入器基类
 */
abstract class Writer {

    protected $threshold = Type::ALL;
    protected $layout_callback = null;
    protected $is_running = false;

    function __construct($options = []) {
        foreach ($options as $k => $v) {
            $this->$k = $v;
        }
        $this->init();
    }

    protected function init() {
        $this->is_running = true;
    }

    public function __destruct() {
        unset($this->io_handler);
    }

    public function switchTo($mode = Logger::SYNC_IO) {
        $this->io_mode = $mode;
        return true;
    }

    /**
     * @param Event $event
     * @return string
     */
    function render(Event $event) {
        if (is_callable($this->layout_callback)) {
            return call_user_func($this->layout_callback, $event);
        } else {
            return $event->__toString();
        }
    }
}
