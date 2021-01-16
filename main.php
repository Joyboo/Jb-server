<?php

use library\Exception;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Process;

define('APP_DEBUG', true);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', realpath(dirname(__FILE__)) . DS);
define('APP_PATH', ROOT_PATH . 'application' . DS);
define('LIB_PATH', ROOT_PATH . 'library' . DS);
define('CONF_PATH', ROOT_PATH . 'config' . DS);
define('CACHE_PATH', ROOT_PATH . 'cache' . DS);
define('HTTP_PATH', APP_PATH . 'http' . DS);

class Main
{
    public static $checkFile;

    public static $pidFile;

    /** @var Server */
    protected static $HttpServer;

    public static function run()
    {
        // 加载项目函数库
        is_file($comfile = ROOT_PATH . 'extend' . DS . 'common.php') && include $comfile;

        /* 初始化机制 */
        require LIB_PATH . 'Loader.php';
        // 注册自动加载并启动
        \library\Loader::register([
            ROOT_PATH . 'extend',
            LIB_PATH . 'extend'
        ], [
            'library' => LIB_PATH,
            'app' => APP_PATH
        ]);

        // 注册错误和异常处理机制
        Exception::register();

        // 加载惯例配置文件
        config(CONF_PATH . 'config.php');

        self::$pidFile = HTTP_PATH . 'pid';
    }

    public static function init()
    {
        if (!empty(self::$HttpServer)) {
            return false;
        }

        $path = config('log.path');
        is_dir($monthDir = $path . date('Ym') . '/') or mkdir($monthDir, 0777, true);
        is_file($runFile = $path . 'http_server_run.log') or touch($runFile);
        self::$checkFile = $path . 'check.php';

        $cfg = config('http_server');
        self::$HttpServer = new Server($cfg['host'], $cfg['port']);

        $default = [
            // 具体开启的进程数其实是n+2，另外两个分别是master进程和manager进程
            'worker_num' => 10,
            // 守护进程化
            'daemonize' => true,
            // 子进程处理多少个reques后重启,防止内存泄漏
            'max_request' => 50000,
            // 在Server启动时自动将master进程的PID写入到文件，在Server关闭时自动删除PID文件。
            'pid_file' => self::$pidFile,
            // 开启守护进程模式后, 标准输出将会被重定向到 log_file,不会自动切分文件，所以需要定期清理此文件
            'log_file' => $runFile,
            // 设置 Server 错误日志打印的等级，范围是 0-6。低于 log_level 设置的日志信息不会抛出
            'log_level' => SWOOLE_LOG_TRACE,
        ];
        self::$HttpServer->set(array_merge($default, $cfg['config_set'] ?? []));

        self::$HttpServer->on("start", function (\Swoole\Server $server) use ($cfg) {
            trace("httpServer已启动: {$cfg['host']}:{$cfg['port']}");
        });

        self::$HttpServer->on('shutdown', function (\Swoole\Server $server) use ($cfg) {
            trace("httpServer已停止: {$cfg['host']}:{$cfg['port']} 主进程id:{$server->master_pid}");
        });

        self::$HttpServer->on('request', function (Request $request, Response $response) {
            try {

                // 解析路由
                $uri = explode('/', trim($request->server['request_uri'], '/'));

                $controller = !empty($uri[0]) ? $uri[0] : 'Index';
                $action = $uri[1] ?? 'index';

                $className = "\\app\\http\\controller\\" . ucfirst($controller);
                if (!class_exists($className)) {
                    $response->status(404);
                    $response->end('controller not found: ' . $controller);
                }

                $class = new $className($request, $response);
                if (!method_exists($class, $action)) {
                    $response->end("Bad request: {$controller}.{$action}");
                }

                $class->$action();
            } catch (\Exception | Exception $e) {
                $err = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                    'trace' => json_encode($e->getTrace(), JSON_UNESCAPED_UNICODE)
                ];
                trace("[ERROR]: run " . json_encode($err, JSON_UNESCAPED_UNICODE), 'error');
                $response->status(500);
                $response->end($err['message']);
            }
        });
    }

    public static function stop($output = true)
    {
        is_file(self::$pidFile) && ($pid = file_get_contents(self::$pidFile));

        if (empty($pid)) {
            $output && trace('需要停止的httpServer进程未启动');
            return;
        }

        // 检测进程是否存在，不会发送信号
        if (Process::kill($pid, 0)) {
            // 发送 kill 15 $pid
            Process::kill($pid);
            Process::wait();
        } else {
            trace("httpServer进程{$pid}不存在,删除pid文件");
        }
    }

    public static function start()
    {
        self::init();
        self::$HttpServer->start();
    }

    public static function restart()
    {
        self::stop();
        sleep(1);
        self::start();
    }
}

Main::run();

$opt = getopt('s:');
switch ($opt['s']) {
    case 'start':
        Main::start();
        break;
    case 'stop':
        Main::stop();
        break;
    case 'restart':
        Main::restart();
        break;
    default:
        die('参数错误:[start|stop|restart]');
}