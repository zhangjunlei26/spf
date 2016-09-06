<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/2
 * Time: 16:09
 */
namespace spark\logger\writer;

use spf\helper\Console as ConsoleColor;
use spark\logger\Event;
use spark\logger\Type;
use spark\logger\writer;

/**
 * 控制台打印日志内容
 */
class Console extends Writer {

    function multi($events) {
        foreach ($events as $event) {
            $this->write($event);
        }
    }

    function write(Event $event) {
        if ($event->num_level < $this->threshold) {
            return true;//低于日志等级或没定义writer,跳过
        }
        $msg = $this->render($event);
        if (PHP_SAPI !== 'cli') {
            echo "<pre>", $msg, "</pre>\n";
        } else {
            list($path, $data) = explode("\n", $msg, 2);
            $level = $event->num_level;
            $path = ConsoleColor::dark_gray($path);
            if ($level < Type::WARN) {
                $data = ConsoleColor::green($data);
            } elseif ($level < Type::ERROR) {
                $data = ConsoleColor::yellow($data);
            } else {
                $data = ConsoleColor::red($data);
            }
            echo $path, "\n", $data;
        }
    }
}