<?php

namespace library\model;

use library\Db;
use library\Redis;

/**
 * @author dj
 * @version 2017-04-14
 */
class Base
{
    public $dbkey = ''; // config里面的db配置的key
    public $db = ''; // 数据库名，一般是$dbkey数组里的dbname成员
    public $table = null; // 数据表名
    public $taskname = '';
    public $pk = 'id'; // 主键的字段名

    // 数据信息
    protected $data = [];
    // 是否为更新数据
    protected $isUpdate = false;
    // 表前缀
    protected $tablePrefix = '';

    public function __construct()
    {
        // 表前缀
        $this->tablePrefix = config('db_prefix');
        $table = $this->tablePrefix . $this->getTable();
        // 获取表名
        $this->table = $this->table ?: $table;
        $this->dbkey = $this->dbkey ?: 'db';
        $this->db = $this->db ?: config($this->dbkey)['dbname'];
    }

    /**
     * 获取表名，并将将Java风格转换为C的风格
     * @return string
     */
    protected function getTable()
    {
        $name = basename(str_replace('\\', '/', get_called_class()));
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }

    /**
     * @param string|array $dbkey 数据库配置
     * @param string $key 单例模式的标识值
     * @param bool $async 是否采用异步操作
     * @param bool $ignore_dbname 数据库名是否参与实例标识计算
     */
    public function getDb($dbkey = '', $key = '', $async = false, $ignore_dbname = false)
    {
        if (empty($dbkey)) {
            $dbkey = $this->dbkey;
        }
        return Db::instance($dbkey, $key, $async, $ignore_dbname);
    }

    public function redisConnect($options = 'redis', $dbnum = 0)
    {
        // 实例化redis
        $Redis = Redis::instance($options, __METHOD__);
        return $Redis;
    }

    /**
     * 通过主键值从缓存中获取或设置信息
     * @param number $id ID
     * @return array
     */
    public function cacheInfo($id = 0)
    {
        return $this->_cacheInfo($id);
    }

    /**
     * 通过主键值从缓存中获取或设置信息
     * @param number $id ID
     * @param int $dbnum 要选择的redis库编号
     * @param array $options redis配置
     * @param string $prefix key的前缀，默认为取本模型的name属性，最终key的格式类似 Game-66
     * @return array
     */
    protected function _cacheInfo($id = 0, $dbnum = null, $options = null, $prefix = '')
    {
        list($Redis, $key, $pk, $id) = $this->redisAndKey($id, $dbnum, $options, $prefix);

        $data = $Redis->get($key);
        // 没有记录，则尝试从数据表里读取
        if (!$data) {
            $db = $this->getDb($this->dbkey, __METHOD__);
            $data = $db->queryOne("select * from {$this->table} where $pk='$id'");
            $data && $Redis->set($key, $data); // 存入缓存
        }

        isset($data['extend']) && !is_array($data['extend']) && $data['extend'] = json_decode($data['extend'], true);

        return $data;
    }

    /**
     * 返回redis对象和某条数据的key
     * @param number|array $id ID
     * @param int $dbnum 要选择的redis库编号
     * @param array $options redis配置
     * @param string $prefix key的前缀，默认为取本模型的name属性，最终key的格式类似 Game-66
     * @return array [redis对象, 某条数据的key]
     */
    public function redisAndKey($id = 0, $dbnum = null, $options = null, $prefix = '')
    {
        // 实例化redis
        $Redis = Redis::instance(is_array($options) ? $options : 'redis', __METHOD__);

        // 选择redis库
        $Redis->select($dbnum ?: $Redis->config['db']);


        $pk = is_array($id) ? key($id) : $this->pk; // 唯一字段名
        is_array($pk) && $pk = $pk[0];

        is_array($id) && $id = current($id); // 唯一值


        // 缓存前缀
        $prefix = $prefix ?: $this->db . '.' . ucfirst($this->table) . '-';
        $key = $prefix . $id;

        return [$Redis, $key, $pk, $id];
    }

    // 实现类似TP5的save功能
    public function save($data = [], $where = [], $replace = false)
    {
        $db = $this->getDb($this->dbkey, __METHOD__);
        $this->data = $this->autoCompleteData($data);

        if ($this->isUpdate === false) {
            // 前置操作
            $this->_before_insert($this->data);
            if ($replace) {
                $incId = $db->replace($this->table, $this->data);
            } else {
                $incId = $db->insert($this->table, $this->data, true);
            }
            // 后置
            $this->data[$this->pk] = $incId;
            $this->_after_insert($this->data);
            return $incId;
        }

        $id = $this->data[$this->pk] ?? 0;
        // 不指定条件的话，则根据$data中的主键字段值来做条件
        if (!$where) {
            $where = [$this->pk => $id];
        }
        return $db->update($this->table, $this->data, $where)
            &&
            // 对某一条记录进行 删、改的操作时，默认只删除该记录的缓存
            $this->resetCache($id);
    }

    protected function _before_insert(& $data) {}

    protected function _after_insert(& $data) {}

    // 实现类似TP5的extend字段修改器功能
    public function autoCompleteData($data)
    {
        foreach ($data as $key => &$value) {
            if (method_exists($this, $mod = 'set' . ucfirst($key) . 'Attr')) {
                $value = $this->$mod($value, $data);
                if ($value === false) {
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }

    protected function setExtendAttr($data, $alldata)
    {
        return is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
    }

    /**
     * 是否为更新数据
     * @access public
     * @param bool $update
     * @param mixed $where
     * @return $this
     */
    public function isUpdate($update = true)
    {
        $this->isUpdate = $update;
        return $this;
    }

    // 实现类似TP5的resetCache功能
    public function resetCache($id = 0)
    {
        return $this->_resetCache($id);
    }

    /**
     * 通过主键值从缓存中删除信息
     * @param number|array $id ID
     * @param int $dbnum 要选择的redis库编号
     * @param array $options redis其它配置
     * @param string $prefix key的前缀，默认为取本模型的name属性，最终key的格式类似 Game-66
     * @return bool
     */
    protected function _resetCache($id = 0, $dbnum = null, $options = null, $prefix = '')
    {
        list($Redis, $key, $pk, $id) = $this->redisAndKey($id, $dbnum, $options, $prefix);
        $Redis->del($key);
        return true;
    }
}
