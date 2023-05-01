<?php

// 登录验证器
namespace app\validate\admin;

use think\Validate;

class AdminVa extends Validate
{
    protected $rule = [
        'nickname' => 'require|max:30',
        'account' => 'require',
        'password' => ['require', 'regex' => "/^[\w]{6,12}$/"],
        'sex' => 'require|in:男,女,保密',
        'id' => 'require',
        'state' => 'require|number'
    ];

    protected $message = [
        'nickname.require' => '名称不能为空',
        'nickname.max' => '名称最多不能超过30个字符',
        'account.require' => '账户不能为空',
        'password.require' => '密码不能为空',
        'password.regex' => '密码是6-12为的字母或者数字',
        'sex.require' => '性别不能为空',
    ];

    protected $scene = [
        'edit' => ['nickname', 'sex', 'id'],
        'create' => ['nickname', 'account', 'password', 'sex'],
        'disable' => ['id', 'state'],
        'rest' => ['id', 'password']
    ];
}