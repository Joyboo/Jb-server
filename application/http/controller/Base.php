<?php

namespace app\http\controller;

use Redis;
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

    /** @var Redis $redis */
    protected $redis;

    protected $time = 0;

    /**
     * Base constructor.
     * @param Request $request
     * @param Response $response
     * @param Redis $redis
     */
    public function __construct(Request $request, Response $response, Redis $redis)
    {
        $this->redis = $redis;
        $this->request = $request;
        $this->response = $response;

        $this->time = time();
    }

    protected function request() : Request
    {
        return $this->request;
    }

    protected function response() : Response
    {
        return $this->response;
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