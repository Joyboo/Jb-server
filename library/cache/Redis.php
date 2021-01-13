<?php
namespace library\cache;

/**
 * 缓存驱动类--redis
 */
class Redis extends Driver
{
    protected $options = [];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {
        // 为了提高并发处理能力，每个子进程都占用一个独立的redis长链接
        $this->_handler = \library\Redis::instance($this->options = array_merge($this->options, $options), 'CacheSpecial');
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($name, $default = '')
    {
        $value = $this->_handler->get($name);
        return is_null($value) ? $default : $value;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param int $expire 有效时间 0为永久
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        return $this->_handler->set($name, $value, $expire);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name)
    {
        return $this->_handler->delete($name);
    }
}