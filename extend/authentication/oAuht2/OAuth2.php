<?php

namespace authentication\oAuht2;

abstract class OAuth2
{
    protected $config = null;

    public function __construct($config)
    {
        $this->config = $config;
    }

    abstract public function getAccessToken($code);

    abstract public function getUserInfo($accessToken, $openid);

    abstract public function getUserPhone($code);
}