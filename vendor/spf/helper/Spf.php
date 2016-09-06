<?php
namespace spf\helper;

use spf\swoole\Base;
use spf\swoole\Manager;

class Spf extends Base {

    const CMDS = ['start', 'stop', 'reload', 'restart', 'shutdown', 'status', 'list'];
    protected $args;
    protected $argv;
    protected $name;

    public function __construct($argv) {
        $this->argv = $argv;
    }

    public function run() {
        $this->initArgs();
        $args = $this->args;
        $cmd = $args['cmd'];
        $name = $args['name'];
        if ($cmd === 'list') {
            return $this->listall();
        }
        if ($name && !in_array($name, $this->getServers())) {
            echo "The server ", Console::red($name), " not exists.", PHP_EOL;
            return $this->listall();
        }
        //加载检查配置
        $serverNames = $name ? [$name] : self::getServers();
        foreach ($serverNames as $servName) {
            $config = self::getServerConf($servName);
            if (!$config) {
                echo Console::red("Please return array in the end of the config file \"{$servName}.php\"."), PHP_EOL;
                return;
            }
            $server = new Manager($servName);
            $server->run($cmd, $servName);
        }
    }

    protected function initArgs() {
        $opt = getopt('vh');//初步处理命令行参数 v h, getopt("c:n:k:Vvh?");
        if ($opt) {
            if (isset($opt['h'])) {
                $this->help();
            } elseif (isset($opt['v'])) {
                echo 'spf version ', SPF_VERSION, PHP_EOL;
                exit;
            }
        }
        $args = $this->getOptions($this->argv);
        $this->args = $args;
    }

    /**
     * 帮助
     */
    function help() {
        $version = SPF_VERSION;
        echo <<<HEREDOC
spf version {$version}
Usage: 
  #spf start|stop|reload|restart|status server_name
  #spf shutdown|list
Options:
  start         : start server
  stop          : stop server
  reload        : reload server
  restart       : restart server
  status        : status server
  server_name   : server name
Others:
  -h            : this help
  -v            : show version and exit

HEREDOC;
        exit;
    }

    /**
     * 取命令行参数
     * @param $args
     * @return array
     */
    function getOptions($args) {
        $args = array_slice($args, 1);
        $cmd = $name = null;
        $num_args = count($args);
        if ($num_args > 1) {
            list($cmd, $name) = $args;
        } elseif ($num_args === 1) {
            $cmd = $args[0];
            /*if (!in_array($cmd, ['list', 'shutdown'])) {
                $this->help();
            }*/
        } else {
            $this->help();
        }
        if (!in_array($cmd, self::CMDS)) {
            $this->help();
        }
        return ['cmd' => $cmd, 'name' => $name];
    }

    public function listall() {
        echo "The following servers are available: ", PHP_EOL;
        foreach (self::getServers() as $v) {
            echo " ", Console::green($v), PHP_EOL;
        }
    }
}