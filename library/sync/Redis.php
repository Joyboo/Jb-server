<?php
namespace library\sync;

use library\Exception;

/**
 * 同步Redis操作类
 */
class Redis
{
    /**
     * @var \Redis
     */
    protected $conn;
    public $config = [];
    public $option = [\Redis::OPT_SERIALIZER, \Redis::OPT_PREFIX];

    /**
     * Constructor
     *
     * @param array $config redis配置
     * @param string $key 连接存储ID
     * @throws \Exception
     */
    public function __construct($config = [], $key = null)
    {
        if (!extension_loaded('redis')) {
            throw new \Exception('extension redis is not exist!');
        }
        $this->config = $config;

        $this->conn = new \Redis();

        try {
            if (empty($this->config['persistent'])) {
                $this->conn->connect($this->config['host'], $this->config['port'], $this->config['timeout']);
            } else {
                // 建立长链接，如果想启用多个连接实例，一定要设置不同的persistent_id
                $this->conn->pconnect($this->config['host'], $this->config['port'], 0.0, $key);
            }

            if (isset($this->config['password'])) {
                $this->conn->auth($this->config['password']);
            }

            // 设置redis的选项
            foreach ($this->option as $key) {
                if (isset($this->config[$key])) {
                    $this->conn->setOption($key, $this->config[$key]);
                }
            }

            if (isset($this->config['db']) && is_numeric($this->config['db'])) {
                $this->conn->select($this->config['db']);
            }
        } catch (\Exception $e) {
            Exception::report($e, $config);
            throw $e;
        }
    }

    /**
     * Set cache
     *
     * @param mixed $id
     * @param mixed $data
     * @param int $ttl
     * @return bool
     */
    public function set($id, $data, $ttl = null)
    {
        if (null === $ttl) {
            $ttl = $this->config['ttl'];
        }

        is_scalar($data) or $data = json_encode($data, JSON_UNESCAPED_UNICODE);

        if (empty($ttl)) {
            return $this->conn->set($id, $data);
        } else {
            return $this->conn->setex($id, $ttl, $data);
        }
    }

    /**
     * Get Cache Value
     *
     * @param mixed $id
     * @return mixed
     */
    public function get($id)
    {
        if (!is_array($id)) {
            return $this->_jsonDecode($this->conn->get($id));
        }
        $value = $this->conn->mGet($id);
        return array_combine($id, array_map([$this, '_jsonDecode'], $value));
    }

    protected function _jsonDecode($value)
    {
        $json_data = json_decode($value, true);
        // 检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
        return (null === $json_data) ? $value : $json_data;
    }

    /**
     * Set cache
     *
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function __set($key, $value)
    {
        return null === $value ? $this->delete($key) : $this->set($key, $value);
    }

    /**
     * Get cache
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Delete cache
     *
     * @param string $key
     * @return boolean
     */
    public function __unset($key)
    {
        return $this->delete($key);
    }

    /**
     * Magic method
     *
     * @param string $method
     * @param array $args
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->conn, $method], $args);
    }
}