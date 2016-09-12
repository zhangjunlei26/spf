<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/7/21
 * Time: 10:47
 */
namespace spf\swoole;

use spf\Loader;

class Base {

    const MASTER_PID_FILE = APP_PATH . "/var/run/spf-%s-master.pid";
    const MANAGER_PID_FILE = APP_PATH . "/var/run/spf-%s-manager.pid";
    const PHP_BIN = PHP_BINDIR . '/php';
    const MASTER_PROCESS_NAME = 'spf-%s-master';
    const MANAGER_PROCESS_NAME = 'spf-%s-manager';
    const WORKER_PROCESS_NAME = 'spf-%s-%s-work-%d';

    protected $name;
    protected $config;

    public function __construct($name) {
        $this->name = $name;
        $this->config = $config = self::getServerConf($name);
        if (!empty($config['php_ini_set'])) {
            foreach ($config['php_ini_set'] as $k => $v) {
                ini_set($k, $v);
            }
        }
        if (!empty($config['root']) && is_dir($config['root'])) {
            $loader = Loader::getInstance();
            $loader->setAutoloadPath($config['root']);
        }
    }

    public static function getServerConf($serverName) {
        return self::getConf($serverName);
    }

    public static function getConf($name, $package = 'spf') {
        $conf = include APP_PATH . "/conf/{$package}/{$name}.php";
        return is_array($conf) ? $conf : [];
    }

    public static function getServers() {
        $hosts = [];
        $files = glob(APP_PATH . "/conf/spf/*.php");
        foreach ($files as $file) {
            $hosts[] = basename($file, '.php');
        }
        return $hosts;
    }

    public static function log($msg) {
        $data = date('c') . "\t" . $msg . "\n";
        file_put_contents(APP_PATH . '/var/log/error.log', $data, FILE_APPEND);
    }

    public function getMasterPid() {
        $master_pid_file = self::getMasterPidFile($this->name);
        return is_file($master_pid_file) ? file_get_contents($master_pid_file) : false;
    }

    public static function getMasterPidFile($name) {
        return sprintf(self::MASTER_PID_FILE, $name);
    }

    public function getManagerPid() {
        $manager_pid_file = self::getManagerPidFile($this->name);
        return is_file($manager_pid_file) ? file_get_contents($manager_pid_file) : false;
    }

    public static function getManagerPidFile($name) {
        return sprintf(self::MANAGER_PID_FILE, $name);
    }

    public function isRunning() {
        $pid = $this->getMasterPid();
        if ($pid) {
            return posix_kill($pid, 0);
        } else {
            return false;
        }
    }
}