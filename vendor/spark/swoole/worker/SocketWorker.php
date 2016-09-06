<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/8/9
 * Time: 10:22
 */
namespace spark\swoole\worker;

use spf\swoole\worker\Base;
use spf\swoole\worker\ISocketWorker;

class SocketWorker extends Base implements ISocketWorker {

    public function onConnect($server, $clientId, $fromId) {
        if (IN_DEV === true) {
            $info = $server->connection_info($clientId);
            $client = "{$info['remote_ip']}:{$info['remote_port']}";
            echo Console::green("onConnect from: {$client}"), PHP_EOL;
        }
    }

    public function onClose($server, $clientId, $fromId) {
        if (IN_DEV === true) {
            $info = $server->connection_info($clientId);
            $client = "{$info['remote_ip']}:{$info['remote_port']}";
            echo Console::green("onClose from: {$client}"), PHP_EOL;
        }
    }

    public function onReceive($server, $clientId, $fromId, $data) {
        if (IN_DEV === true) {
            Console::dump(['onReceive:' => $data]);
        }
    }
}