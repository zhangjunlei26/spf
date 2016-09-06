<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/2
 * Time: 16:06
 */
namespace spark\logger\writer;

use spark\logger\Event;
use spark\logger\writer;

/**
 * 基于scribe的日志写入器
 *
 * @author leon
 */
class Scribe extends Writer {
    protected $transport;
    protected $scribe_client;
    protected $host;
    protected $port = 1463;

    public function write(Event $event) {
        if (!$this->is_running) {
            $this->init();
        }
        $msg = [
            'category' => $event->group,
            'message'  => $this->render($event),
        ];
        $messages = [
            new LogEntry($msg),
        ];
        return $this->scribe_client->Log($messages);
    }

    public function init() {
        $GLOBALS['THRIFT_ROOT'] = THRIFT_ROOT;
        include_once THRIFT_ROOT . '/scribe.php';
        include_once THRIFT_ROOT . '/transport/TSocket.php';
        include_once THRIFT_ROOT . '/transport/TFramedTransport.php';
        include_once THRIFT_ROOT . '/protocol/TBinaryProtocol.php';
        $socket = new TSocket($this->host, $this->port, true);
        $this->transport = new \TFramedTransport($socket);
        $protocol = new \TBinaryProtocol($this->transport, false,
            false);//$protocol = new TBinaryProtocol($trans,$strictRead=false,$strictWrite=true)
        $this->scribe_client = new \scribeClient($protocol,
            $protocol);//$scribe_client = new scribeClient($iprot=$protocol, $oprot=$protocol)
        $this->transport->open();
        $this->is_running = true;
    }

    public function multi($events) {
        if (!$this->is_running) {
            $this->init();
        }
        $messages = [];
        foreach ($events as $type => $arr) {
            foreach ($arr as $event) {
                $messages[] = new LogEntry([
                    'category' => $event->group,
                    'message'  => $this->render($event),
                ]);
            }
        }
        return $this->scribe_client->Log($messages);
    }

    public function __destruct() {
        if ($this->is_running) {
            $this->transport->close();
        }
    }
}
