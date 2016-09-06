<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/7/6
 * Time: 15:25
 */
$config = [
    /**
     * 工作模式:development;testing;production
     */
    'enviroment'     => 'development',
    /**
     * 服务模式: Socket/Http/WebSocket
     */
    'type'           => 'Http',
    /**
     * 可侦听多个端口，如果Type为Http类型,第二个端口一般用来管理服务进程
     * listen完整格式示例如下:
     * ['0.0.0.0', 8081, SWOOLE_SOCK_TCP],
     * ['0.0.0.0', 8082, SWOOLE_SOCK_UDP],
     * ['/tmp/s2.sock', 0, SWOOLE_UNIX_STREAM],
     */
    'listen'         => [
        8080,
    ],
    /**
     * 当前服务有关的类库PATH,用于指定类库目录,实现该目录下类库自动加载
     */
    'root'           => APP_PATH . '/examples',
    /**
     * worker进程用哪个类名，由用户指定自己实现的worker/tasker类,自定制进程工作行为
     */
    'worker_class'   => '\spark\swoole\worker\HttpWorker',
    /**
     * ini_set
     */
    'php_ini_set'    => [
        'date.timezone'   => 'Asia/Shanghai',
        'error_reporting' => E_ALL,
        'display_errors'  => 1,
        'log_errors'      => 1,
        'error_log'       => APP_PATH . '/var/log/phperror.log',
    ],
    /**
     * 设置swoole_server/swoole_http_server的配置项
     */
    'server_setting' => [
        'daemonize'           => 1,
        'debug_mode'          => 0,
        'backlog'             => 1024,
        'cpu_affinity_ignore' => [0],
//        'user'           => '',
//        'group'=>'',
//        'chroot'              => '',
        //'reactor_num'              => 4,
        //默认启用CPU核数相同的工作进程，建议值为CPU核1-4倍
        'worker_num'          => 1,
//        'task_worker_num'          => 500,
//        'task_max_request'         => 500000,
        'open_cpu_affinity'   => 1,
        'open_tcp_nodelay'    => 1,
        'tcp_defer_accept'    => 3,//http服务器，可以提升响应速度
        //'log_file'                 => APP_PATH . '/var/log/swoole.log',
//        'enable_reuse_port'        => true,//Linux-3.9.0+
    ],
];

if ($config['enviroment'] !== 'production') {
    $config['server_setting']['daemonize'] = 0;
    $config['server_setting']['debug_mode'] = 1;
    $config['server_setting']['reactor_num'] = 1;
    $config['server_setting']['worker_num'] = 1;
    $config['server_setting']['max_request'] = 100;
    $config['server_setting']['task_worker_num'] = 0;
    $config['server_setting']['task_max_request'] = 5;
}
return $config;