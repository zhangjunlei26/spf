<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/8
 * Time: 11:08
 */
namespace spark\exception;

class Http extends Logic {

    function __construct($http_code, $message, \Throwable $previous = null) {
        $show_message = \spark\web\helper\Http::getStatusName($http_code);
        parent::__construct($http_code, $message, $show_message, true, $previous);
    }

}