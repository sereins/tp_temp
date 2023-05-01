<?php

namespace app\controller;

use app\RespVo;

class Error
{
    public function __call($method, $args)
    {
        return RespVo::Fail(RespVo::CODE_DANGER, "控制器未定义");
    }
}