<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/1
 * Time: 11:24
 */
//declare (strict_types = 1);
namespace spark\client;

class ParallelProcessor extends Base {

    /**
     * 请求的io对象列表
     * @var \SplObjectStorage
     */
    public $io_list;
    /**
     * 异步返回的结果集,以key为下标
     * @var array
     */
    protected $results = [];
    protected $exceptions = [];
    /**
     * 回调结果统计
     * @var int
     */
    protected $ret_count = 0;
    /**
     * 回调
     * @var callable
     */
    protected $callback;
    /**
     * 自动生成key序列
     * @var int
     */
    protected $auto_id = 0;

    public function __construct() {
        //重载父类,无参数
        $this->io_list = new \SplObjectStorage();
    }

    /**
     * 添加client
     * @param Base   $client
     * @param string $key
     */
    public function add(Base $client, $key = '') {
        $this->io_list->attach($client, $key ?: $this->auto_id++);
    }

    public function close() {
        if ($this->io_list) {
            $this->io_list->removeAll($this->io_list);
        }
        $this->callback = null;
    }

    /**
     * 取得多个异步结果集
     * @param callable $callback
     */
    public function fetch(callable $callback) {
        $this->callback = $callback;
        $this->reInit();
        foreach ($this->io_list as $client) {
            $client->fetch(function ($ret, Base $client) {
                $io_list = $this->io_list;
                $cnt = $io_list->count();
                if (!$cnt || !isset($this->io_list[ $client ])) {
                    //已经清空,再有多余回调不处理
                    return true;
                }
                $key = $io_list[ $client ];
                $exceptions = &$this->exceptions;
                if ($ret instanceof \Throwable) {
                    $exceptions[ $key ] = $ret;
                }
                $this->results[ $key ] = $ret;
                if ($cnt === (++$this->ret_count)) {
                    $this->auto_id = $this->ret_count = 0;
                    $this->io_list->removeAll($this->io_list);
                    $rs = $this->results;
                    $this->results = [];
                    if ($exceptions) {
                        $e = $exceptions[ array_rand($exceptions) ];
                        $exceptions = [];
                        $this->call($this->callback, $e);
                    } else {
                        $this->call($this->callback, $rs);
                    }
                }
            });
        }
    }

}