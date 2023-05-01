<?php

namespace authentication\oAuht2;


use authentication\oAuht2\request\AuthWebWechatRequest;

class OAuth2Factory
{

    /**
     * 获取实例
     *
     * @param $config
     * @return OAuth2
     */
    public static function GetInstance($config): OAuth2
    {
        return  new AuthWebWechatRequest($config);
    }
}