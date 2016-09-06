<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/3
 * Time: 10:48
 */
/**
 * SPF 是否处于开发调试模式
 */
const VENDOR_PATH = __DIR__;
const SPF_ROOT = __DIR__ . '/spf';
const DS = DIRECTORY_SEPARATOR;
define('APP_PATH', dirname(VENDOR_PATH));
/**
 * 初始化Loader(加载器),你也可使用composer带的autoload.php
 */
require __DIR__ . '/spf/Loader.php';
$loader = \spf\Loader::getInstance();
$loader->setAutoloadPath(VENDOR_PATH);
$loader->setIncludePath(APP_PATH);