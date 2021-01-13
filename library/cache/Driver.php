<?php

namespace library\cache;

/**
 * 缓存驱动基础类
 */
abstract class Driver
{
    protected $_handler = null;
    protected $_error = ''; // 错误信息

    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    abstract public function get($name, $default = false);

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param int $expire 有效时间 0为永久
     * @return boolean
     */
    abstract public function set($name, $value, $expire = null);

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    abstract public function rm($name);

    public function getError()
    {
        return $this->_errorInfo;
    }

    /**
     * 返回句柄对象，可执行其它高级方法
     *
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->_handler;
    }
}
