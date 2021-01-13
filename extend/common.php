<?php
/**
 * 通用函数库
 */

use library\Config;
use library\Exception;
use library\cache\Redis as Cache;

if (!function_exists('halt')) {
    /**
     * 输出并终止，用于调试变量
     *
     * @param mixed $var 要输出的变量
     * @param bool $exit 是否要终止
     * @param bool $print 使用print_r函数（值为true时） 或者 var_dump函数（值为false时）
     * @return void
     */
    function halt($var, $exit = true, $print = true)
    {
        echo '<pre>';
        $print ? print_r($var) : var_dump($var);
        echo '</pre>';
        $exit && exit();
    }
}

if (!function_exists('model')) {
    /**
     * 实例化模型
     *
     * @param string $name Model名称
     * @param array $param 传给模型的参数
     */
    function model($name = '', $param = [])
    {
        $path = '\\app\\models\\';
        $name = ucfirst($name);

        $class = $path . $name;

        if (!class_exists($class))
        {
            throw new Exception("class Not Found ：{$class}");
        }
        return new $class($param);
    }
}

if (!function_exists('array_merge_multi')) {
    /**
     * 多维数组合并（支持多数组）
     *
     * @return array
     */
    function array_merge_multi()
    {
        $args = func_get_args();
        $array = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $k => $v) {
                    if (is_array($v)) {
                        $array[$k] = isset($array[$k]) ? $array[$k] : [];
                        $array[$k] = array_merge_multi($array[$k], $v);
                    } else {
                        $array[$k] = $v;
                    }
                }
            }
        }
        return $array;
    }
}

if (!function_exists('safe_text')) {
    // 文本入库前的过滤工作
    function safe_text($text, $htmlspecialchars = true)
    {
        $text = trim(strip_tags($text));
        return $htmlspecialchars ? htmlspecialchars($text) : $text;
    }
}

if (!function_exists('curl')) {
    /**
     * curl操作封装
     *
     * @param string $url 要访问的网址，get方法的时候请在此网址上直接带上参数
     * @param string/array $params 要post的数据，如果是array，方法内部会转成string
     * @param int/bool $return 1代表返回 0代表直接输出
     * @param array $header 请求头
     * 示例：$header = [
     * 'Host: www.php.com',
     * 'Origin: http://joyboo.cn',
     * 'Referer: http://joyboo.cn/',
     * 'Connection: Keep-Alive',
     * 'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36',
     * 'Cookie: 你的COOKIE串'
     * ]
     * @param array $cookie 模拟cookie 格式须为关联数组， key 为 jar 时代表请求cookie, key 为 file 时代表发送cookie； value代表完整的cookie路径和文件，该文件可以通过 tempnam(string $dir , 'cookie') 获得
     * @param array $option 其它的选项，可参考http://php.net/manual/en/weixin_function.curl-getinfo.php
     * @return mixed 响应内容（如果需要获得响应头信息，可以在调用此函数后直接访问 全局变量 $_EVN['CURL_HEADER']）
     */
    function curl($url, $params = '', $return = 1, $header = array(), $cookie = array(), $option = array())
    {
        $ch = curl_init($url); // 初始化curl并设置链接
        // curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        // 设置是否为post传递
        curl_setopt($ch, CURLOPT_POST, (bool)$params);
        // 对于https 设定为不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);//设置是否返回信息

        if ($cookie) {
            $key = array_keys($cookie);
            curl_setopt($ch, $key[0] == 'jar' ? CURLOPT_COOKIEJAR : CURLOPT_COOKIEFILE, $cookie['file']);
        }

        if ($params) {
            if (is_array($params)) {
                $params = http_build_query($params);
            }
            // POST 数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //设置头信息的地方
        } else {
            PHP_SAPI != 'cli' && curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }

        foreach ($option as $key => $val) {
            curl_setopt($ch, $key, $val);
        }

        $response = curl_exec($ch); // 执行并接收返回信息

        if (curl_errno($ch)) {
            // 出错则显示错误信息
            throw new \Exception(curl_error($ch));
        }

        if (!empty($option[CURLOPT_HEADER])) {
            // 获得响应结果里的：头大小
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            // 根据头大小去获取头信息内容
            $_EVN['CURL_HEADER'] = substr($response, 0, $header_size);
            $response = substr($response, $header_size);
        }

        curl_close($ch); // 关闭curl链接
        return $response;
    }
}

if (!function_exists('config')) {
    /**
     * 获取和设置配置参数
     *
     * @param string|array $name 参数名（初始化时要传一个文件名）
     * @param mixed $value 参数值
     * @return mixed
     * @version 7.0.0 最后修改时间 2019年01月10日
     */
    function config($name = '', $value = null)
    {
        /** @var Config $config */
        static $config;

        if (!$config) {
            // 存取为对象的目的是为了让子进程在调本函数时引用的是同一个对象，节省内存
            $config = new Config($name);
            return $config->get();
        }

        // 读取
        if (is_null($value) && is_string($name)) {
            return 0 === strpos($name, '?') ? $config->has(substr($name, 1)) : $config->get($name);
        } // 写入
        else {
            return $config->set($name, $value);
        }
    }
}

if (!function_exists('trace')) {
    /**
     * 记录日志
     *
     * @param string|array $message 日志内容
     * @param string $type 日志级别 ，例如log或者error
     * @version 7.0.0 最后修改时间 2019年01月10日
     */
    function trace($message, $type = 'log')
    {
        // add by
        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        } else {
            $message = str_replace(["\n", "\r"], '', $message);
        }

        is_dir($path = config('log.path') . date('Ym') . '/') or mkdir($path, 0777, true);

        $now = date('H:i:s');
        // 后台运行
        $destination = $path . date('d') . ($type == 'log' ? '' : "_$type") . '.log';
        is_file($destination) or touch($destination);
        error_log("$now\t$message\r\n", 3, $destination, '');
    }
}

if (!function_exists('cache')) {
    /**
     * 读写缓存
     *
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param int $expire 有效时间 0为永久
     * @return mixed
     * @version 7.0.0 最后修改时间 2019年01月10日
     */
    function cache($name, $value = null, $expire = null)
    {
        static $Cache;

        if (!$Cache) {
            // 存取为对象的目的是为了让子进程在调本函数时引用的是同一个对象，节省内存
            $Cache = new Cache(['db' => config('redis.fdb')] + config('redis'));
        }

        // 读取
        if (is_null($value) && is_string($name)) {
            return $Cache->get($name);
        } // 写入
        else {
            return $Cache->set($name, $value, $expire);
        }
    }
}
