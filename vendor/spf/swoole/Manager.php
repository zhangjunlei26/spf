<?php
namespace spf\swoole;

use spf\helper\Console;

class Manager extends Base {

    public function run($cmd, $name) {
        return $this->$cmd($name);
    }

    protected function start($name) {
        //日志初始化
        $process = new \swoole\process(function (\swoole\process $worker) {
            $worker->exec(self::PHP_BIN, [SPF_ROOT . '/server.php', $this->name]);
        }, false);
        return $process->start();
    }

    protected function stop() {
        $master_id = $this->getMasterPid();
        if (!$master_id) {
            $msg = "[Failed] Stop Master: can not find master pid file.";
            echo Console::red($msg), PHP_EOL;
            self::log($msg);
            return false;
        } elseif (!posix_kill($master_id, 15)) {//SIGTERM
            $msg = "[Failed] Stop Master: send signal to master failed.";
            echo Console::red($msg), PHP_EOL;
            self::log($msg);
            return false;
        }
        @unlink(self::getMasterPidFile($this->name));
        @unlink(self::getManagerPidFile($this->name));
        usleep(50000);
        $msg = "[Seccess] Stop Master:{$master_id} sucess.";
        echo Console::green($msg), PHP_EOL;
        self::log($msg);
        return true;
    }

    protected function reload() {
        $manager_id = $this->getManagerPid();
        if (!$manager_id) {
            $msg = "[warning] can not find manager pid file. Manager reload failed!";
            echo Console::red($msg), PHP_EOL;
            self::log($msg);
            return false;
        }
        if (!posix_kill($manager_id, 10)) {//SIGUSR1
            $msg = "[Failed] Send signal to manager process {$manager_id} failed.";
            echo Console::red($msg), PHP_EOL;
            self::log($msg);
            return false;
        }
        $msg = "[Success] Manager {$manager_id} reload OK!";
        echo Console::green($msg), PHP_EOL;
        self::log($msg);
        return true;
    }

    protected function status($name) {
        $sw_version = SWOOLE_VERSION;
        $spf_version = SPF_VERSION;
        $running = $this->isRunning() ? Console::green('[OK]') : Console::red('[STOPED]');
        $master = $this->getMasterPid();
        $manager = $this->getManagerPid();
        $config = $this->config;
        $server = Console::green($name);
        $enviroment = $config['enviroment'];
        $title = Console::yellow(strtoupper("{$name} STATUS Summary"));
        echo <<<HEREDOC

{$title}
-----------------------------------------------------------------
Server: {$server}
Server Enviroment: {$enviroment}
SPF Version: {$spf_version}
Swoole Version: {$sw_version}
Swoole master pid is : {$master}
Swoole manager pid is : {$manager}
Server running: {$running}

HEREDOC;
    }
}
