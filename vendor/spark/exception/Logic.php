<?php
namespace spark\exception;

class Logic extends \LogicException {

    const DB_CONNECT_ERROR = -100000;
    const DB_QUERY_ERROR = -100001;
    const TASK_EXCEPTION = -100010;
    const API_QUERY_TIMEOUT = -100100;
    const API_METHOD_ERROR = -100101;
    const API_ARGUMENT_ERROR = -100102;
    const API_EXECUTE_ERROR = -100103;
    protected $showMessage = '系统繁忙,请稍后再试';
    protected $errno = 0;
    protected $enableLog = false;

    function __construct($errno, $message, $showMessage = '', $enableLog = true, \Throwable $previous = null) {
        $this->errno = $errno;
        if ($showMessage) {
            $this->showMessage = $showMessage;
        }
        $this->enableLog = $enableLog ? true : false;
        parent::__construct($message, $this->errno, $previous);
    }

    function isLogEnabled() {
        return $this->enableLog;
    }

    function getShowArr() {
        return ['code' => $this->getCode(), 'msg' => $this->showMessage];
    }

    public function getShowMsg() {
        return $this->showMessage;
    }
}