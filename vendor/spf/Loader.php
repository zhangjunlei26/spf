<?php
/**
 * 1 加载器,用于类库加载自动自动化,须用setAutoloadPath方法指定类库目录
 * 2 在指定的一个或多个目录中加载指定文件,须用setIncludePath方法指定目录
 */
namespace spf;

class Loader {

    static $instance;
    public $autoload_paths = [];
    public $include_path = [];

    function __construct() {
        spl_autoload_register([$this, 'autoload'], true, true);//添加到队列之首
        self::$instance = $this;
    }

    /**
     * @return Loader
     */
    static function getInstance() {
        if(!isset(self::$instance)){
            new self();
        }
        return self::$instance;
    }

    function __destruct() {
        spl_autoload_unregister([$this, 'autoload']);
        unset($this->autoload_paths, $this->include_path);
    }

    /**
     * 自动加载类
     * @param $class
     * @return bool
     */
    function autoload($class) {
        $file = $this->findClass($class);
        if ($file) {
            return include $file;
        } else {
            return false;
        }
    }

    /**
     * 查找类对应的文件
     * @param       $class
     * @param array $paths 查找路径
     * @return bool|string
     */
    function findClass($class, $paths = []) {
        return $this->findFile(str_replace('\\', DS, ltrim($class, '\\')) . '.php', $paths ?: $this->autoload_paths);
    }

    /**
     * 查找文件
     * @param       $file
     * @param array $path
     * @return bool|string
     */
    function findFile($file, $path = []) {
        $cache = realpath_cache_get();
        $files = [];
        foreach (($path ?: $this->include_path) as $p) {
            $f = $p . DS . $file;
            if (isset($cache[ $f ])) {
                return $f;
            }
            $files[] = $f;
        }
        foreach ($files as $file) {
            if (is_file($file)) {
                return $file;
            }
        }
        return false;
    }

    /**
     * 设置类库加载路径
     * @param string|array $paths  路径,可以字符串或数组
     * @param bool         $prefix 默认加在其它路径之前,为false则追加路径在后
     */
    function setAutoloadPath($paths, $prefix = true) {
        array_splice($this->autoload_paths, ($prefix ? 0 : count($this->autoload_paths)), 0, $paths);
    }

    /**
     * 设置文件查找路径
     * @param string|array $paths
     * @param bool         $prefix
     */
    function setIncludePath($paths, $prefix = true) {
        array_splice($this->include_path, ($prefix ? 0 : count($this->autoload_paths)), 0, $paths);
    }
}