<?php
namespace demo\controller;

use demo\model\Test as TestModel;
use spark\Container;
use spark\coroutine\Coroutine;
use spark\web\Controller;

class async extends Controller {

    /**
     * @var TestModel
     */
    protected $testModel;

    public function __construct(Container $container) {
        parent::__construct($container);
        $this->testModel = new TestModel();
    }

    /**
     * 演示任务按顺序执行
     * 外部路径 http://127.0.0.1:8081/demo/async/index
     */
    public function actionIndex() {
        $tcp = new \spark\client\Tcp('127.0.0.1', 9501, 'hello TCP!', 0.5);
        $rs1 = yield $tcp->run();
        $rs2 = yield $tcp->send('hello TCP again!')->run();
        $ret = ['rs1' => $rs1, 'rs2' => $rs2];
        $this->json($ret);
    }

    /**
     * 外部路径 http://127.0.0.1:8081/demo/async/model
     * 简单地与TestModel交互
     */
    public function actionModel() {
        $this->json(yield $this->testModel->test1());
    }

    /**
     * 外部路径 http://127.0.0.1:8081/demo/async/Parallel
     * 串行任务(按顺序执行异步任务)
     */
    public function actionSync() {
        $rs = (yield $this->testModel->syncTest());
        $this->json($rs);
    }

    /**
     * 并行任务
     * 外部路径 http://127.0.0.1:8081/demo/async/parallel
     * @return string
     */
    public function actionParallel() {
        $rs = (yield $this->testModel->parallelTest());
        $this->json($rs);
    }

    public function actionTask() {
        $container = (yield Coroutine::getContainer());
        $server = $container['swoole_server'];
        $task_client = new \spark\client\Task($server, [
            [
                'demo\task\model\Test',//task类
                'fetchTestDbTables',//要调用的task方法
            ],
            [],//参数
        ]);
        $rs = yield $task_client->run();
        $this->json($rs);
    }

    public function actionHttp() {
        //$url = 'http://www.weibo.com/u/1407642250/home?wvr=5&lf=reg';
        $url = 'http://jsqmt.qq.com/cdn_djl.js';
        //$http_client = new \spark\client\HttpClient();
        $http_client = new \spark\client\Http();
        $http_ret = (yield $http_client->get($url, null, null, [
            "_s_tentry"  => "www.google.com.hk",
            "Apache"     => "8168706906600.46.1465731293474",
            "SINAGLOBAL" => "8168706906600.46.1465731293474",
            "ULV"        => "1465731293606:1:1:1:8168706906600.46.1465731293474:",
            "SUB"        => "_2AkMgAcf9f8NhqwJRmP0Wym3gb411zwDEieLBAH7sJRMxHRl-yT83qmgStRSBg_rsks2Eh0bL2XD9LZXL7a...",
            "SUBP"       => "0033WrSXqPxfM72-Ws9jqgMF55529P9D9W5k.HzQZNEY8jRvYFaFYb14",
            "UOR"        => "www.google.com.hk,vdisk.weibo.com,v.baidu.com",
        ])->run());
        $http_client->close();
        $this->output($http_ret);
    }
}
