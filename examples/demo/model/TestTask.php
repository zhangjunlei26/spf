<?php
/**
 * Created by PhpStorm.
 * User: zjl
 * Date: 16-9-8
 * Time: 下午5:12
 */
namespace demo\model;
class TestTask
{
    /**
     * 简单返回数据
     * @return \Generator
     */
    public function test1()
    {
        $rs = [
            'num' => 1,
            'bt' => true,
            'bf' => false,
            'null' => null,
            'str' => 'hello world!',
        ];
        return $rs;
    }
}
