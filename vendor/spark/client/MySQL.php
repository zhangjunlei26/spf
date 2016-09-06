<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 31/5/30
 * Time: 14:10
 */
//declare (strict_types = 1);
namespace spark\client;

use spark\exception\Logic;

class MySQL extends Base {

    protected $sql;
    protected $config;
    /**
     * @var \swoole_mysql
     */
    protected $client;
    protected $connected = false;

    /**
     * 初始化MySQLi需要的参数
     * $conf = ['host'=>'127.0.0.1','port'=>3306,'user'=>'test','password'=>'test123','database'=>'db1','charset'=>'utf-8'];
     * @param $conf
     */
    public function __construct($conf) {
        $this->config = $conf;
        $this->client = new \swoole_mysql;
    }

    /**
     * @param callable $callback
     */
    public function fetch(callable $callback) {
        $this->callback = $callback;
        $db = $this->client;
        //如果未连接,进行连接
        if (empty($db->connected)) {
            $db->connect($this->config, function ($db, $rs) {
                $callback = $this->callback;
                if ($rs === false) {
                    $msg = "DB Connect failed to: " . json_encode($this->config);
                    $err = new Logic(Logic::DB_CONNECT_ERROR, $msg, "Db connect error.");
                    $this->call($callback, $err);
                } else {
                    $this->doQuery();
                }
            });
        } else {
            $this->doQuery(true);
        }
    }

    /**
     * 异步查询结果
     */
    protected function doQuery($again = false) {
        $db = $this->client;
        $db->query($this->sql, function (\Swoole\Mysql $db, $rs) use ($again) {
            $callback = $this->callback;
            if ($rs === false) {
                //SQL执行失败
                $msg = "swoole_mysql error[{$db->errno}]: {$db->error}";
                $err = new Logic(Logic::DB_QUERY_ERROR, $msg, 'DB query error');
                $this->call($callback, $err);
            } elseif ($rs === true) {
                //执行成功，update/delete/insert语句，没有结果集
                $data = ['affected_rows' => $db->affected_rows, 'insert_id' => $db->insert_id];
                $this->call($callback, $data);
            } else {
                //执行成功，$r是结果集数组
                $this->call($callback, $rs);
            }
        });

    }

    function query($sql) {
        $this->sql = $sql;
        return $this;
    }

    public function close() {
        if (!empty($this->client->connected)) {
            $this->client->close();
        }
    }
}
