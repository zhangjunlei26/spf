<?php
namespace spark\web;

use spark\Container;
use spark\exception\Http;
use spark\Log;

class App {

    /**
     * @var Container
     */
    public static $workerContainer;
    /**
     * @var \swoole_http_request
     */
    protected $request;
    /**
     * @var \swoole_http_response
     */
    protected $response;
    /**
     * @var Route
     */
    protected $route;

    public function __construct(Container $container) {
        $this->request = $container['request'];
        $this->response = $container['response'];
        $workerContainer = self::$workerContainer;
        $this->route = $workerContainer['route'];
    }

    public function run(Container $container) {
        try {
            $route = $this->route;
            list($controller, $action) = $route->parse($container);
            $gen = $controller->$action();
            if ($gen instanceof \Generator) {
                $workerContainer = self::$workerContainer;
                $scheduler = $workerContainer['scheduler'];
                $scheduler->add($gen, $container);
                $scheduler->run();
            }
        } catch (\Throwable $e) {
            $extra = ['Container' => $container->dump()];
            $this->exceptionHandler($e, $extra);
        }
    }

    public function exceptionHandler($e, $extra = []) {
        $response = $this->response;
        //如果已完成页面输出,只进行日志
        if (!empty($response->isCompleted)) {
            if (IN_DEV === true) {
                Log::info("Exception after display.");
                Log::error($e->__toString());
            }
            return;
        }

        $isLogic = $e instanceof Logic;
        //日志异常
        if (!$isLogic || $e->isLogEnabled()) {
            Log::error($e->__toString());
        }

        if ($e instanceof Http) {//Http异常,主要是404显示
            $response->status($e->getCode());
            $response->end($e->getMessage());
            $response->isCompleted = true;
            return;
        }
        //显示给最终用户的信息
        if ($isLogic) {
            $msg = $e->getShowMsg();
        } else {
            $msg = $e->getMessage();
            if ($e instanceof \ErrorException && $e->getCode() !== 0) {
                list(, $msg) = explode('|||', $msg, 2);
            }
        }
        //判断返回ajax还是html
        if ($this->response->isJson) {
            $content = ['code' => $e->getCode(), 'msg' => $msg];
            if (\IN_DEV === true) {
                $content['exception'] = $e->__toString();
            }
            $content = json_encode($content, JSON_UNESCAPED_UNICODE);
        } else {
            $response->status(500);
            if (\IN_DEV === true) {
                $content = ExceptionRender::render($e, $extra);
            } else {
                $content = "{$msg}[{$e->getCode()}]";
            }
        }
        $response->end($content);
        $response->isCompleted = true;
    }

    public function __destruct() {
        $this->fatalHandler();
    }

    /**
     * Fatal Error是最后得到的,无法try..catch得到,所以直接处理
     */
    public function fatalHandler() {
        $error = error_get_last();
        if ($error) {
            $e = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            $this->exceptionHandler($e);
        }
    }
}