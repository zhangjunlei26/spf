<?php
/**
 * Project spf
 * Created by PhpStorm.
 * User: rosenzhang <rosenzhang@tencent.com>
 * Date: 16/6/3
 * Time: 11:11
 */
namespace spark;

class Container implements \ArrayAccess {

    protected $registry = [];
    protected $closure = [];
    protected $keys = [];

    public function offsetExists($offset) {
        return isset($this->keys[ $offset ]);
    }

    public function offsetSet($offset, $value) {
        $this->registry[ $offset ] = $value;
        $this->keys[ $offset ] = true;
    }

    public function offsetUnset($offset) {
        unset($this->closure[ $offset ], $this->closure[ $offset ], $this->keys[ $offset ]);
    }

    public function keys() {
        return array_keys($this->keys);
    }

    public function dump() {
        return array_merge($this->closure, $this->registry);
    }

    public function bind($key, callable $value) {
        $this->closure[ $key ] = $value;
        $this->keys[ $key ] = true;
        unset($this->registry[ $key ]);//清理重新绑定时可能存在的缓存
    }

    public function offsetGet($offset) {
        if (isset($this->registry[ $offset ])) {
            return $this->registry[ $offset ];
        } elseif (isset($this->closure[ $offset ])) {
            return call_user_func($this->closure[ $offset ], $offset, $this);
        } else {
            return null;
        }
    }
}