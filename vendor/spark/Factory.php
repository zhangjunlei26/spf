<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/8/9
 * Time: 11:13
 */
namespace spark;

class Factory {

    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function makeCo($class) {
    }
}