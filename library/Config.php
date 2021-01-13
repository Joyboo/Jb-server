<?php

namespace library;

/**
 * 配置类
 */
class Config
{
    // 配置参数
    protected $_data = [];

    /**
     * 加载配置文件（PHP格式）
     * @param string $file 配置文件名
     * @param string $name 配置名（如设置即表示二级配置）
     * @return mixed
     */
    public function __construct($file, $name = '')
    {
        if (!is_file($file)) {
            return $this->_data;
        }

        $name = strtolower($name);
        $this->set(include $file, $name);
    }

    /**
     * 检测配置是否存在
     * @param string $name 配置参数名（支持二级配置 .号分割）
     * @return bool
     */
    public function has($name)
    {
        if (!strpos($name, '.')) {
            return isset($this->_data[strtolower($name)]);
        } else {
            // 二维数组设置和获取支持
            $name = explode('.', $name, 2);
            return isset($this->_data[strtolower($name[0])][$name[1]]);
        }
    }

    /**
     * 获取配置参数 为空则获取所有配置
     * @param string $name 配置参数名（支持二级配置 .号分割）
     * @return mixed
     */
    public function get($name = null)
    {
        // 无参数时获取所有
        if (empty($name)) {
            return $this->_data;
        }

        if (!strpos($name, '.')) {
            $name = strtolower($name);
            return $this->_data[$name] ?? null;
        } else {
            // 二维数组设置和获取支持
            $name = explode('.', $name, 2);
            $name[0] = strtolower($name[0]);
            return $this->_data[$name[0]][$name[1]] ?? null;
        }
    }

    /**
     * 设置配置参数 name为数组则为批量设置
     * @param string|array $name 配置参数名（支持二级配置 .号分割）
     * @param mixed $value 配置值
     * @return mixed
     */
    public function set($name, $value = null)
    {
        if (is_string($name)) {
            if (!strpos($name, '.')) {
                $this->_data[strtolower($name)] = $value;
            } else {
                // 二维数组设置和获取支持
                $name = explode('.', $name, 2);
                $this->_data[strtolower($name[0])][$name[1]] = $value;
            }
            return;
        } elseif (is_array($name)) {
            // 批量设置
            if (!empty($value)) {
                $this->_data[$value] = isset($this->_data[$value]) ?
                    array_merge($this->_data[$value], $name) :
                    $this->_data[$value] = $name;
                return $this->_data[$value];
            } else {
                return $this->_data = array_merge($this->_data, array_change_key_case($name));
            }
        } else {
            // 为空直接返回 已有配置
            return $this->_data;
        }
    }

    /**
     * 重置配置参数
     */
    public function reset()
    {
        $this->_data = [];
    }
}