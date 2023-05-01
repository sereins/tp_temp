<?php

namespace authentication\oAuht2\request;

use app\RespVo;
use app\utils\Https;
use authentication\oAuht2\OAuth2;

class AuthWebWechatRequest extends OAuth2
{
    /**
     * 获取网页授权
     *
     * @param $code string 授权码
     * @return bool|mixed|string
     */
    public function getAccessToken($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token";

        $data['appid'] = $this->config['appid'];
        $data['secret'] = $this->config['appsecret'];
        $data['code'] = $code;
        $data['grant_type'] = 'authorization_code';

        $data = Https::Url($url)->get($data)->json();

        $resp = new RespVo();
        if (isset($data['errcode'])) {
            return $resp->warn_msg($data['errmsg']);
        }

        return $resp->success($data);
    }

    /**
     * 获取用户信息
     *
     * @param $accessToken
     * @param $openid
     * @return mixed
     */
    public function getUserInfo($accessToken, $openid)
    {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$accessToken}&openid={$openid}";
        $res = Https::Url($url)->get()->json();

        $resp = new RespVo();
        if (isset($res['errcode'])) {
            return $resp->warn_msg($res['errmsg']);
        }
        return $resp->success($res);
    }

    public function getUserPhone($code)
    {
        d('不被允许获取');
    }
}
