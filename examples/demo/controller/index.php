<?php
namespace demo\controller;

use spark\web\Controller;

class index extends Controller {

    /**
     * 对应的请求路径为: http://127.0.0.1:8081/demo/index/index
     * @return \Generator
     */
    function actionIndex() {
//        $msg = "<h1>hello world!</h1>\n";
//        $this->response->end($msg);//swoole_http_response写法
//        $this->output($msg);//简化写法
        $response = $this->response;
        $response->header('Last-Modified', 'Tue, 26 Jul 2016 10:24:27 GMT');
        $response->header('E-Tag', '55829c5b-17');
        $response->header('Accept-Ranges', 'bytes');
        $out = print_r($this->request,true);
        $response->end("<h1>\nHello World.\n</h1><pre>{$out}</pre>");
    }

    /**
     * 对应的请求路径为: http://127.0.0.1:8081/demo/index/json
     */
    function actionJson() {
        $this->json([
            'num'  => 1,
            'bt'   => true,
            'bf'   => false,
            'null' => null,
            'str'  => 'hello world!',
        ]);
    }

    function actionJsonError() {
        $this->jsonError(2, '参数错误!');//输出json错误
    }

    /**
     * 外部url http://127.0.0.1:8081/demo/index/error
     * @return \Generator
     * @throws \Throwable
     */
    function actionError() {
        //手工抛出异常
//        throw new \Exception('some exception', 1);
        //函数不存在
        test_function();
    }
}