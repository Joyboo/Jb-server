<?php

namespace library;

/**
 * Db调度类
 */
class Db
{
    static protected $_instance = [];

    static protected $_config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'root',
        'password' => '',
        'dbname' => 'test',
        'charset' => 'utf8'
    ];

    /**
     * @param string|array $name config里面的key。如果传的是数组，则直接用该数组做为配置
     * @param string $key 单例模式的标识值
     * @param bool $ignore_dbname 数据库名是否参与实例标识计算
     *
     * @return sync\Mysql
     * @throws \Exception
     */
    public static function instance($name = 'db', $key = '', $ignore_dbname = false)
    {
        $config = (is_array($name) ? $name : (array)config($name)) + self::$_config;
        // 过滤掉无用的key
        $_config = $config = array_intersect_key($config, self::$_config);

        // 数据库名不参与实例标识的计算
        if ($ignore_dbname) {
            unset($_config['dbname']);
        }

        $key .= md5(serialize($_config));

        if (isset(self::$_instance[$key])) {
            // 防止数据库变动而没有切换
            if ($ignore_dbname) {
                self::$_instance[$key]->querySql("USE $config[dbname];");
            }
            return self::$_instance[$key];
        }

        return self::$_instance[$key] =  new sync\Mysql($config);
    }
}
