<?php

namespace Cache;

class RedisOp
{
//     临时数据使用的库：验证码之类的。
//    const TEMP_TABLE = 0;

    // 登录token所使用的库
    const TOKEN_TABLE = 1;

    // 会话密钥所使用的库
//    const SESSION_TABLE = 2;

    // token有效期
    const TOKEN_EXPIRE = 3600;

//    // 二维码登录
//    const QR_LOGIN = 'qrcode_';
//    const QR_LOGIN_EXPIRE = 600;
//
//    // 验证码key
//    const CODE_BIND_PHONE = 'bindPhone';                   # 绑定手机号
//    const CODE_BIND_EMAIL = 'bindEmail';                   # 绑定邮箱
//    const CODE_FORGET_PASS = 'forgetPwd';                  # 忘记密码
//    const CODE_REGISTER_EMAIL = 'loginRegisterEmail';      # 邮箱登录注册
//    const CODE_REGISTER_PHONE = 'loginRegister';           # 手机登录注册
//    const CODE_REGISTER_ORG = 'org_register';              # 企业注册
//    const CODE_EXPIRE = 60;                                # 验证码有效期
//
//    // 微信access_token key
//    const WECHAT_ACCESS_TOKEN = 'wechat_access_token';            # 普通令牌
//    const COMPONENT_ACCESS_TOKEN = 'component_access_token';      # 第三方平令牌
//    const AUTHORIZER_ACCESS_TOKEN = 'authorizer_access_token';    # 授权令牌
//    const AUTHORIZER_REFRESH_TOKEN = 'authorizer_refresh_token';  # 刷新令牌
//    const PRE_AUTH_CODE = 'pre_auth_code';                        # 预授权码
//    // 我方设置微信令牌过期时间
//    const ACCESS_TOKEN_EXPIRE = 7000;


    /**
     * @param $db
     * @return \Redis
     * @throws \RedisException
     */
    public static function GetRedis($db): \Redis
    {
        $config = config("cache")['redis'];
        $redis = new \Redis();

        if ($config["pconnect"]) {
            $redis->pconnect($config['host'], $config['port']);
        } else {
            $redis->connect($config['host'], $config['port']);
        }

        //设置连接密码
        if ($config["password"]) {
            $redis->auth($config["password"]);
        }

        $redis->select($db);
        return $redis;
    }
}

