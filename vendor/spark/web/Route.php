<?php
/**
 * TODO::该类预留下接口,以后再实现
 */
namespace spark\web;

use spark\Container;
use spark\exception\Http;

class Route {

    protected $rules = [];

    function addRule($rules, $prefix = true) {
        $rules = &$this->rules;
        array_splice($rules, 0, ($prefix ? 0 : count($rules)), $rules);
        $this->rules = $rules;
    }

    function parse(Container $container) {
        $request = $container['request'];
        $uri = $request->server['request_uri'];
        $uri = trim($uri, '/ ');
        if ($uri === 'favicon.ico') {
            throw new Http(404, "The page favicon.ico not found.");
        }
        $arr = isset($uri[0]) ? explode('/', $uri) : [];
        $count = count($arr);
        switch ($count) {
            case 0:
                $arr = ['demo', 'index', 'index'];
                break;
            case 1:
                array_splice($arr, 1, 0, ['index', 'index']);
                break;
            case 2:
                array_splice($arr, 1, 0, 'index');
                break;
        }
        array_splice($arr, -2, 0, 'controller');
        $action = 'action' . array_pop($arr);
        $class = implode('\\', $arr);
        if (!class_exists($class)) {
            throw new Http(404, "The class '{$class}' not exists.");
        }
        $controller = new $class($container);
        if (!method_exists($controller, $action)) {
            throw new Http(404, "The page [{$class}::{$action}()] not found.");
        }
        return [$controller, $action];
    }
}