<?php
namespace demo\model;

use spark\coroutine\Coroutine;

class Test {

    /**
     * 简单返回数据
     * @return \Generator
     */
    public function test1() {
        $rs = [
            'num'  => 1,
            'bt'   => true,
            'bf'   => false,
            'null' => null,
            'str'  => 'hello world!',
        ];
        yield Coroutine::ret($rs);
    }

    /**
     * 两个任务串行
     * @return \Generator
     */
    public function syncTest() {

        //TCP请求
        $tcp_ret = $tcp_ret2 = $udp_ret = $udp_ret2 = $db_ret = $db_ret2 = $http_ret = null;
        $tcp = new \spark\client\Tcp('127.0.0.1', 9501, 'hello TCP!', 0.5);
        $tcp_ret = (yield $tcp->run());
        $tcp_ret2 = (yield $tcp->send('hello TCP again!')->run());
        $tcp->close();

        //UDP请求
        $udp = new \spark\client\Udp('127.0.0.1', 9505, 'hello UDP!', 0.5);
        $udp_ret = (yield $udp->run());
        $udp_ret2 = (yield $udp->send('Hello UDP again!')->run());
        $udp->close();

        //DB请求
        $db = new \spark\client\MySQL([
            'host'     => '127.0.0.1',
            'port'     => '3306',
            'user'     => 'root',
            'password' => '951753',
            'database' => 'test',
            'charset'  => 'utf-8',
        ]);
        //sql1
        $db_ret = (yield $db->query('show tables')->run());
        //sql2
        $db_ret2 = (yield $db->query('select * from test')->run());

        //http请求
//        $url = 'http://127.0.0.1/t.txt';
//        $url = 'http://sybtest1.qt.qq.com:8080/t.txt';
//        $url = 'http://127.0.0.1/haproxy-stats';
        $url = 'http://daxue.qq.com/content/content/id/2557';
        $http_client = new \spark\client\Http();
        $http_ret = (yield $http_client->get($url)->run());//(yield $http_client->withHead()->run());

        $rs = [
            'tcp_ret'  => $tcp_ret,
            'tcp_ret2' => $tcp_ret2,
            'udp_ret'  => $udp_ret,
            'udp_ret2' => $udp_ret2,
            'db_ret'   => $db_ret,
            'db_ret2'  => $db_ret2,
            'http_ret' => $http_ret,
        ];

        yield Coroutine::ret($rs);
    }

    /**
     * 多个IO调用同时运行demo
     * @return \Generator
     */
    public function parallelTest() {
        $pp = new \spark\client\ParallelProcessor();

        //添加需要并行执行的任务
        $tcp_client = new \spark\client\Tcp('127.0.0.1', 9501, 'Hello TCP!', 0.5);
        $udp_client = new \spark\client\Udp('127.0.0.1', 9505, 'Hello UDP!', 0.5);

        //添加二个并发任务,第二个参数为返回结果标识key
        $pp->add($tcp_client, 'rs1');
        $pp->add($udp_client, 'rs2');
        $rs = (yield $pp->run());//执行
//        Console::dump($rs);

        //再次添加二个并发任务
        $pp->add($tcp_client->send('Hello TCP again!'), 'rs3');
        $pp->add($udp_client->send('Hello UDP again!'), 'rs4');
        $rs2 = (yield $pp->run());//执行
//        Console::dump($rs2);
        //关闭连接
        $tcp_client->close();
        $udp_client->close();
        yield Coroutine::ret($rs + $rs2);
        //返回结果

    }
}