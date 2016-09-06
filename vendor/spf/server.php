<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/7/21
 * Time: 10:25
 */
require __DIR__ . '/../bootstrap.php';
$serverName = isset($argv[1]) ? $argv[1] : '';
$config = [];
if ($serverName) {
    $config = \spf\swoole\Base::getServerConf($serverName);
}
if (empty($serverName) || empty($config)) {
    echo \spark\helper\Console::red("[Error]:: invalid server name [$serverName] or config file '{$serverName}.php' in wrong format."), PHP_EOL;
    exit();
}
$class = '\spf\swoole\server\\' . $config['type'];
if (!class_exists($class)) {
    echo \spf\helper\Console::red("[Error]:: class '{$class}' not found."), PHP_EOL;
    exit();
}
//$spfContainer = new \spf\Container\Container();
(new $class($serverName))->start();