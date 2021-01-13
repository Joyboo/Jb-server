<?php

namespace library;

/**
 * 加载器
 */
class Loader
{
    /**
     * 命名空间的路径
     * @var array
     */
    protected static $namespacePath = [];

    /**
     * 映射类
     * @var array
     */
    protected static $classMap = [];

    /**
     * 默认加载路径
     * @var string|array|null
     */
    protected static $includePath = null;

    /**
     * 注册默认加载路径并启动
     * @param string|array $path 路径。多个路径请传成数组格式
     * @param array $namespace 命名空间
     * @param array $class 映射类
     */
    public static function register($path = '', array $namespace = [], array $class = [])
    {
        // 注册默认加载路径
        if ($path) {
            self::$includePath .= implode(PATH_SEPARATOR, (array)$path) . PATH_SEPARATOR;
        }
        if (!is_null(self::$includePath)) {
            set_include_path(get_include_path() . PATH_SEPARATOR . self::$includePath);
        }

        self::registerNamespace($namespace);
        self::registerClass($class);

        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * 注册命名空间映射
     * @param array $namespace
     * Loader::registerNamespace(['Swoole'=>'/usr/local/src','App'=>'/usr']);
     */
    public static function registerNamespace(array $namespace)
    {
        $namespace && self::$namespacePath = array_merge(self::$namespacePath, $namespace);
    }

    /**
     * 注册类映射
     * @param array $class
     * Loader::registerClass(['Swoole'=>'/usr/local/src/Swoole.php','App'=>'/usr/app.php']);
     */
    public static function registerClass(array $class)
    {
        $class && self::$classMap = array_merge(self::$classMap, $class);
    }

    /**
     * 手动加载文件
     * @param $file
     * @return bool|mixed
     */
    public static function import($file)
    {
        return is_file($file) ? (include $file) : '';
    }

    /**
     * 自动加载文件方法
     * @param string $className
     */
    public static function autoload($className)
    {
        // 类名映射加载
        if (isset(self::$classMap[$className])) {
            self::import(self::$classMap[$className]);
            return;
        }

        // 默认路径加载
        if (false === strpos($className, '\\')) {
            // 兼容'_'作分割的命名
            if (false !== strpos($className, '_')) {
                include str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
            } else {
                include $className . '.php';
            }
            return;
        }

        // 命名空间加载
        foreach (self::$namespacePath as $namespace => $path) {
            if (false !== strpos($className, $namespace)) {
                $filename = $path . str_replace('\\', DIRECTORY_SEPARATOR, str_replace($namespace, '', $className)) . '.php';
                if (self::import($filename)) {
                    return;
                }
            }
        }
    }
}