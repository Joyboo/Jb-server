<?php

namespace app\http\controller;

class Index extends Base
{
    public function index()
    {
        $this->ajaxReturn("index ok");
    }

    public function heartbeat()
    {
        $this->ajaxReturn('ok');
    }
}