<?php

namespace app\http\controller;

use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * http控制器基类
 * Class Base
 * @author Joyboo
 * @date 2020-12-22
 * @package app\http\controller
 */
abstract class Base
{
    /** @var Request $request */
    protected $request;

    /** @var Response $response */
    protected $response;

    protected $time = 0;

    /**
     * Base constructor.
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;

        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_REQUEST = array_merge($_GET, $_POST);

        $_SERVER = $request->server;
        $_COOKIE = $request->cookie;

        // 转大写key
        $_SERVER = array_change_key_case($_SERVER, CASE_UPPER);

        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = $request->header['user-agent'] ?? '';
        }

        $this->time = time();
    }

    /**
     * 获取客户端ip
     * @return string|bool
     */
    protected function getClientIp()
    {
        if ($this->request->header['x-real-ip']) {
            return $this->request->header['x-real-ip'];
        }
        if ($this->request->header['x-forwarded-for']) {
            return $this->request->header['x-forwarded-for'];
        }
        if ($this->request->server['remote_addr']) {
            return $this->request->server['remote_addr'];
        }
        return false;
    }

    /**
     * 响应请求
     * @param string $msg
     * @param int $code
     */
    protected function ajaxReturn($msg = '', $code = 200)
    {
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        $this->response->header('Content-Type', 'application/json; charset=utf-8');
        $this->response->status($code);
        $this->response->end($msg);
    }
}