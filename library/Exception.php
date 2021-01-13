<?php

namespace library;

/**
 * 错误和异常处理类
 */
class Exception extends \ErrorException
{
    public static $_extend = [];

    public function __construct($e, $array = [])
    {
        self::$_extend = $array;

        ($e instanceof \Exception) or parent::__construct($e);
    }

    /**
     * 注册异常或错误处理
     * @return void
     */
    public static function register()
    {
        set_error_handler([__CLASS__, 'appError']);
        set_exception_handler([__CLASS__, 'appException']);
        register_shutdown_function([__CLASS__, 'appShutdown']);
    }

    /**
     * 处理异常（如语法解析错误、自定义抛出异常）
     * @param Exception|ParseError $e
     */
    public static function appException($e)
    {
        self::report($e);
    }

    /**
     * 处理错误（运行时的错误、警告、通知）
     * @param integer $errno 错误编号
     * @param integer $errstr 详细错误信息
     * @param string $errfile 出错的文件
     * @param integer $errline 出错行号
     * @param array $errcontext
     * @throws ErrorException
     */
    public static function appError($errno, $errstr, $errfile = '', $errline = 0)
    {
        // notice类型错误类型
        if (in_array($errno, [E_NOTICE])) {
            trace([
                'file' => $errfile,
                'line' => $errline,
                'msg' => $errstr,
            ], 'notice');

            return true;
        }

        $e = new \ErrorException($errstr, $errno, 1, $errfile, $errline);
        self::report($e);
    }

    /**
     * 脚本执行结束时（不管是正常结束，运行超时，异常或出错而中断）会自动调用该方法
     */
    public static function appShutdown()
    {
        $error = error_get_last();

        if (isset($error['type']) && self::_isFatal($error['type'])) {
            $e = new \ErrorException($error['message'], 0, 1, $error['file'], $error['line']);
            self::report($e);
        }
    }

    /**
     * Report or log an exception.
     *
     * @param \Exception $e
     * @param array $data
     * @return void
     */
    public static function report($e, $data = [])
    {
        if (!$e instanceof \Exception) {
            $e = new \Exception($e);
        }

        $log = [];

        $log['file'] = $e->getFile();
        $log['line'] = $e->getLine();

        // 错误消息
        $log['msg'] = $e->getMessage();

        // 扩展信息
        $log['extend'] = $data ?: self::$_extend;

        if (!empty($log['extend']['err_info'])) {
            $log['msg'] .= $log['extend']['err_info'];
            unset($log['extend']['err_info']);
        }

        // 代码追踪信息
        $traces = $e->getTrace();

        // 去除每个成员的头尾空白字符
        $log = array_map(function ($v) {
            return is_scalar($v) ? trim($v) : $v;
        }, $log);

        // 错误栈记录
        if (APP_DEBUG) {
            $log['trace'] = json_encode($traces, JSON_UNESCAPED_SLASHES );
        }

        if (self::checktime(md5($log['file']), $log)) {
            // 发警报
            self::alarm($log);
        }

        self::$_extend = [];
    }


    /**
     * 检查警报时间间隔
     * @param string $error_name 错误文件名
     * @param array|json $log 日志
     * @param int $interval 警报间隔（分钟）
     * @return bool true:可以发送   false：无须发送
     */
    public static function checktime($error_name, $log = [], $interval = 5)
    {
        /* 记录和警报 */
        $flag = true; // 是否可以发送警报

        // 检测上次发送警报的时间
        $now_time = time();

        $check = file_get_contents(\Main::$checkFile);
        $check = $check ? json_decode($check, true) : [];
        empty($check[$error_name]) && $check[$error_name] = ['time' => 0];
        // N分钟内不发送重复错误消息
        if ($now_time - $check[$error_name]['time'] <= $interval * 60) {
            $flag = false;
        }

        if ($flag) {
            // 登记本次处理时间
            $check[$error_name]['time'] = $now_time;
            file_put_contents(\Main::$checkFile, json_encode($check, JSON_UNESCAPED_UNICODE));
        } else {
            // 写入日志
            trace($log, 'error');
        }

        return $flag;
    }

    /**
     * 发出警报
     * @param array|string $content 警报的内容，格式为： basename,file,line,msg的数组或json格式
     */
    public static function alarm($content = [])
    {
        // todo 报警实现
        trace($content, 'error');
    }

    /**
     * 确定错误类型是否致命
     *
     * @param int $type
     * @return bool
     */
    protected static function _isFatal($type)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    /**
     * 获取出错文件内容
     * 获取错误的前9行和后9行
     * @param string $file 绝对路径及文件
     * @param int $line 发生错误的行数
     * @return array 错误文件内容
     */
    protected static function _getSourceCode($file, $line)
    {
        if (is_file($file)) {
            // 读取前9行和后9行
            $first = ($line - 9 > 0) ? $line - 9 : 1;

            try {
                $contents = file($file);
                $source = [
                    'first' => $first,
                    'source' => array_slice($contents, $first - 1, 19),
                ];
            } catch (\Exception $e) {
                $source = [];
            }
        }
        return $source;
    }
}
