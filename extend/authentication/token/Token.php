<?php

namespace authentication\token;

use app\RespVo;
use Cache\RedisOp;

class Token
{
    /**
     * 创建token 并存入到缓存中
     * @param $userInfo
     * @return string
     * @throws \Exception
     */
    public function createToken($userInfo)
    {
        // 用户基础信息放入存入缓存
        $key = $this->getKey($userInfo['id']);

        $redis = RedisOp::GetRedis(RedisOp::TOKEN_TABLE);
        $redis->setex($key, RedisOp::TOKEN_EXPIRE, json_encode($userInfo));

        return $this->getJwt($userInfo, $key);
    }


    /**
     * 验证token
     *
     * @throws \Exception
     */
    public function checkToken($url, $token)
    {
        $config = config('app');
        // 是否需要验证登录
        if (in_array($url, $config['login_white'])) return [];
        if (empty($token)) throw new \Exception('请登录', RespVo::NOT_LOGIN);

        // 解密jwt
        $payload = JwtAuth::verifyToken($token);

        $key = $payload['redis_key'];
        $redis = RedisOp::GetRedis(RedisOp::TOKEN_TABLE);
        // 获取主用户信息
        $userInfo = $redis->get($key);
        $token_info = json_decode($userInfo, true);
        if (!$token_info) {
            throw new \Exception('登录失效', RespVo::LOGIN_EXPIRE);
        }

        // 每次登录后刷新redis持续时间
        $redis->setex($key, RedisOp::TOKEN_EXPIRE, $userInfo);

        return $token_info;
    }

    /**
     * 使用主账号的信息创建一个jwt
     * jwt中只存入了主账号的id其余信息存入到redis中
     *
     * @param $data
     * @param $key string 存入redis 的缓存key
     * @return bool|string
     * @throws \Exception
     */
    protected function getJwt($data, $key)
    {
        $tokenInfo = [];
        $tokenInfo['id'] = $data['id'];
//        $time = time();
//        // 请求ip
//        $tokenInfo['ip'] = System::GetClientIp();
//        // 签发时间
//        $tokenInfo['iat'] = $time;
//        // 过期时间
//        $tokenInfo['exp'] = $time + CacheConst::TOKEN_EXPIRE;
//        // 该时间之前不接收处理该Token
//        $tokenInfo['nbf'] = $time;

        $tokenInfo['redis_key'] = $key;

        $token = JwtAuth::getToken($tokenInfo);
        if (!$token) throw new \Exception('创建token失败');
        return $token;
    }

    /**
     * redis key
     *
     * @param $uid
     * @return string
     */
    protected function getKey($uid): string
    {
        $secret = 'uynlyUL34';
        return "token:" . md5($uid . $secret . time());
    }
}