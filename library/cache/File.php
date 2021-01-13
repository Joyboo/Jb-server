<?php

namespace library\cache;

/**
 * 缓存驱动类--文件
 */
class File extends Driver
{
    protected $options = [
        'expire' => 0,
        'path' => CACHE_PATH
    ];

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($name, $default = '')
    {
        // 如果没有此缓存文件
        if (!is_file($file = $this->_getCacheKey($name))) {
            return $default;
        }

        $con = file_get_contents($file);
        $con = explode('^', $con, 3); // 数据的格式意义： 原数据是否为数组^有效期^原数据

        if ($con[1] > 0 && time() > filemtime($file) + $con[1]) {
            // 缓存过期删除缓存文件
            unlink($file);
            return null;
        }

        return $con[0] ? json_decode($con[2], true) : $con[2];
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param int|array $expire 有效时间 0为永久
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        // 缓存的有效期
        if (is_array($expire)) {
            $expire = $expire['expire'] ?? 0; // 修复查询缓存无法设置过期时间
        } else {
            $expire = is_numeric($expire) ? $expire : 0; //默认快捷缓存设置过期时间
        }

        // 数据的格式意义： 原数据是否为数组^有效期^原数据

        // 如果要缓存的数据为数组，则先转为字符串
        if (is_array($value)) {
            $value = "1^$expire^" . json_encode($value, JSON_UNESCAPED_UNICODE);
        } else {
            $value = "0^$expire^$value";
        }

        file_put_contents($this->_getCacheKey($name), $value);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name)
    {
        return $this->unlink($this->_getCacheKey($name));
    }

    /**
     * 取得变量的存储文件名
     * @access protected
     * @param string $name 缓存变量名
     * @return string
     */
    protected function _getCacheKey($name)
    {
        if (!is_dir($dir = $this->options['path'])) {
            mkdir($dir, 0777, true);
            chmod($dir, 0777); // add by
        }

        return $dir . md5($name);
    }
}