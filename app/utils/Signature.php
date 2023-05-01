<?php
// 前端请求签名类
namespace app\utils;

use app\RespVo;

class Signature
{
    // 签名使用的key
    private $secret = "c4ca4238a0b923820dcc509a6f75849b";

    /**
     * 验证签名
     *
     * @param $data
     * @param $url string 当前请求的路由 /admin/admin/login
     * @return RespVo
     */
    public function check_sign($data, string $url): RespVo
    {
        $resp = new RespVo();

        // 没有参数不验证
        if (empty($data)) {
            return $resp->set_code(RespVo::CODE_SUCCESS);
        }

        $appConfig = config("app");
        // 签名的白名单
        if (in_array($url, $appConfig['sign_white'])) {
            return $resp->set_code(RespVo::CODE_SUCCESS);
        }

        if (!isset($data['sign']) || !$data['sign']) {
            return $resp->warn_msg("签名不存在!");
        }
        if (!isset($data['timestamp']) || !$data['timestamp']) {
            return $resp->warn_msg("发送的数据参数不合法");
        }

        // 签名是否失效
        if (time() - $data['timestamp'] > $appConfig['sign_expire_time']) {
            return $resp->warn_msg('验证失效， 请重新发送请求');
        }

        $sign = $data['sign'];
        unset($data['sign']);

        if ($sign != $this->_sign($data)) {
            return $resp->warn_msg("签名验证失败!");
        }

        return $resp->success([]);
    }

    /**
     * 生成签名
     *
     * @param $data
     * @return string
     */
    private function _sign($data): string
    {
        // 对数组的值按key排序
        ksort($data);
        // 生成url的形式
        $params = http_build_query($data);
        // 生成sign
        return md5($params . $this->secret);
    }
}