<?php

namespace app\http\controller;

class Index extends Base
{
    public function index()
    {
        $this->ajaxReturn("index ok, ip=" . $this->getClientIp());
    }

    public function heartbeat()
    {
        $this->ajaxReturn('ok');
    }
}