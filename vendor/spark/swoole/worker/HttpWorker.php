<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/7/11
 * Time: 15:18
 */
namespace spark\swoole\worker;

use spark\Container;
use spark\coroutine\Scheduler;
use spark\Log;
use spark\logger\Logger;
use spark\web\App;
use spark\web\Route;
use spf\helper\Spf;
use spf\swoole\worker\Base;
use spf\swoole\worker\IHttpWorker;

class HttpWorker extends Base implements IHttpWorker {

    /**
     * @var Container
     */
    public static $workerContiner;

    public function onRequest($request, $response) {
        //创建会话容器
        $container = new Container;
        $container['request'] = $request;
        $container['response'] = $response;
        //为容器注册对象生成器
        $app = new App($container);
        $container['app'] = $app;
        $app->run($container);
    }

    public function errorHandler($errno, $errmsg, $file, $line) {
        if (!(error_reporting() & $errno)) {//不符合的,返回false,由PHP标准错误进行处理
            return false;
        }
        $err = new \ErrorException($errmsg, 0, $errno, $file, $line);
        if ($this->logger) {
            Log::error($err->__toString());
        }
        return true;
    }

    protected function init() {
        define('ENVIROMENT', $this->config['enviroment']);
        define('IN_DEV', ENVIROMENT !== 'production');
        set_error_handler([$this, 'errorHandler']);
        swoole_timer_tick(10000, function ($id) {
            gc_collect_cycles();
        });
        $config = Spf::getConf('common', 'spark');
        $container = new Container();
        Log::setLogger(new Logger($config['worker_logger']));
        //保存到容器
        $container['route'] = new Route();
        $container['scheduler'] = new Scheduler();
        $container['swoole_server'] = $this->server;
        App::$workerContainer = $container;
    }
}