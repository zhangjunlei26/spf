<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/8/8
 * Time: 15:39
 */
include __DIR__ . '/bootstrap.php';
//确认当前包是否安装在vendor目录下
if (basename(VENDOR_PATH) !== 'vendor') {
    die('spf must be installed under the directory vendor.');
}
$app_dir = dirname(VENDOR_PATH);
$bin_dir = "{$app_dir}/bin";
$conf_dir = "{$app_dir}/conf/spf";
foreach ([$bin_dir, $conf_dir] as $_dir) {
    if (!is_dir($_dir)) {
        mkdir($_dir, true);
        chmod($_dir, 0755);
    }
}

//创建软链
$spf_bin = "{$bin_dir}/spf";
if (!is_file($spf_bin)) {
    $spf_php = __DIR__ . '/spf.php';
    chmod($spf_php, 0755);
    symlink($spf_php, $spf_bin);
}

////////////////////////////////////////////////////////////////////////////////////
//
//                      打包成phar
//
////////////////////////////////////////////////////////////////////////////////////
exit;
$exts = ['php'];      // 需要打包的文件后缀
$dir = __DIR__;             // 需要打包的目录
$phar_name = 'spf';     // 包的名称, 注意它不仅仅是一个文件名, 在stub中也会作为入口前缀
//转移已编译好的
$phar_file = "{$bin_dir}/{$phar_name}.phar";
if (is_file($phar_file)) {
    $time = date('Y-m-d-His');
    rename($phar_file, "{$bin_dir}/{$phar_name}-{$time}.phar");
}

$phar = new Phar(
    $phar_file,
    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
    $phar_name
);

// 将后缀名相关的文件打包
foreach ($exts as $ext) {
    $phar->buildFromDirectory($dir, '/.' . $ext . '$/');
}
// 开始打包
$phar->startBuffering();
$phar->compressFiles(Phar::GZ);//Phar::TAR
$phar->delete('build.php');//删除build.php本身
// 设置入口
/*
$phar->setStub("<?php
Phar::mapPhar('{$spf_phar}');
require 'phar://{$spf_phar}/spf.php';
__HALT_COMPILER();
?>");
//*/
$phar->setStub($phar->createDefaultStub('spf.php', 'spf.php'));
$phar->stopBuffering();
