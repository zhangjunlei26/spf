<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/8/8
 * Time: 17:42
 */
$config = [
    /**
     *   日志
     */
    'worker_logger' => [
        'threshold' => 'warn',  //日志级别:all,fatal,error,warn,info,debug,trace,off
        'writer'    => [
            'File' => [//日志写入类型，下标可选为Scribe,AsyncFile,File,Console
                'threshold'    => 'warn',
                'path'         => APP_PATH . '/var/log',
                'base_name'    => 'demo-error.log',
                'group_as_dir' => false,//是否按组名分目录
            ],
            'Console'   => ['threshold' => 'info'],
        ],
    ],
    'task_logger'   => [
        'threshold' => 'warn',  //日志级别:all,fatal,error,warn,info,debug,trace,off
        'writer'    => [
            'File' => [//日志写入类型，下标可选为Scribe,AsyncFile,File,Console
                'threshold'    => 'warn',
                'path'         => APP_PATH . '/var/log',
                'base_name'    => 'demo-task-error.log',
                'group_as_dir' => false,//是否按组名分目录
            ],
        ],
    ],
];
return $config;