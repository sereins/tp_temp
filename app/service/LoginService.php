<?php

namespace app\service;

use app\model\AdminModel;
use app\RespVo;
use app\utils\Str;
use authentication\token\JwtAuth;
use authentication\token\Token;
use Cache\RedisOp;

class LoginService
{
    /**
     * 登录
     * 1. 获取账户信息
     * 2. 将账户信息存入redis中
     * 3. 生成jwt
     *
     * @param $data
     * @return RespVo
     */
    public function admin_login($data): RespVo
    {
        $res = new RespVo();

        // 判断账号类型
        $type = $this->account_type($data['account']);

        // 获取管理员信息
        $admin = AdminModel::where($type, $data['account'])
            ->field('id,nickname,account,phone,email,sex,state,auth_group,admin,password,state')
            ->findOrEmpty();
        if ($admin->isEmpty()) {
            return $res->fail_info("账号或者密码错误");
        }

        // 验证账号密码是否正确
        if ($admin->password != self::md5_pass($data['password'])) {
            return $res->fail_info("账号或者密码错误");
        }

        // 创建token
        $token = new Token();
        try {

            $res->success(['token' => $token->createToken($admin->toArray())]);
        } catch (\Exception $exception) {
            $res->set_code(RespVo::CODE_DANGER);
            $res->set_msg($exception->getMessage());
        }
        return $res;
    }

    /**
     * 用户密码进行md5
     *
     * @param $password
     * @return string
     */
    public static function md5_pass($password): string
    {
        return md5($password . "rent");
    }

    /**
     * 判断账号的类型
     *
     * @param $account
     * @return string
     */
    public static function account_type($account): string
    {
        if (Str::IsEmail($account)) return "email";

        if (Str::IsPhone($account)) return "phone";

        return "account";
    }

    /**
     * 退出登录
     *
     * @param $token
     * @return void
     */
    public function logout($token)
    {
        // 1.解析token中的key
        $payload = JwtAuth::verifyToken($token);

        // 2.从redis中删除用户信息
        try {

            $redis = RedisOp::GetRedis(RedisOp::TOKEN_TABLE);
            $redis->del($payload['redis_key']);
        } catch (\RedisException $e) {

        }
    }

}