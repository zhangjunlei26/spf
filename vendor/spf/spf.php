#!/usr/bin/env php
<?php
/**
 * Project sh
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/8/5
 * Time: 15:38
 */
const SPF_VERSION = '2.1.3';
require __DIR__ . '/../bootstrap.php';
$spf = new \spf\helper\Spf($argv);
$spf->run();