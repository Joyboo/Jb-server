<?php
namespace library;

/**
 * Redis调度类
 */
class Redis
{
    protected static $_instance = [];

    protected static $_config = [
        'persistent' => true,
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 3,
        'ttl' => 0,
    ];

    /**
     * @param string|array $name config里面的key。如果传的是数组，则直接用该数组做为配置
     * @param string $key 单例模式的标识值
     * @return sync\Redis
     */
    public static function instance($name = 'redis', $key = '')
    {
        $config = (is_array($name) ? $name : (array)config($name)) + self::$_config;

        $key .= md5(serialize($config));

        if (isset(self::$_instance[$key])) {
            return self::$_instance[$key];
        }

        return self::$_instance[$key] = new sync\Redis($config, $key);
    }
}