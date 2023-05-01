<?php

namespace app\service\admin;

use app\model\AdminModel;
use app\RespVo;
use app\service\LoginService;

class AdminService
{
    /**
     * 新增账号
     *
     * @param $data
     * @return RespVo
     */
    public function create($data): RespVo
    {
        $resp = new RespVo();

        $type = LoginService::account_type($data['account']);

        $model = new AdminModel();
        if ($type == 'email') {
            $model->email = $data['account'];
        } elseif ($type == 'phone') {
            $model->phone = $data['account'];
        } else {
            return $resp->fail_info("账号只支持手机号或者邮箱!");
        }

        // 账号是否存在
        $exits = $model->where($type, $data['account'])->field('id')->findOrEmpty();
        if (!$exits->isEmpty()) return $resp->fail_info("账号[{$data['account']}]已经存在");

        $model->nickname = $data['nickname'];
        $model->sex = $data['sex'];
        $model->password = LoginService::md5_pass($data['password']);
        $model->department = $data['department'];
        $model->account = '';
        $model->time_add = date("Y-m-d H:i:s");
        $model->time_put = date("Y-m-d H:i:s");

        if (!$model->save()) return $resp->fail_info("新增账号失败");
        return $resp->success('');
    }
}