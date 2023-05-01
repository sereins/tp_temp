<?php

namespace app\validate\admin;

use think\Validate;

class AuthVa extends Validate
{
    protected $rule = [
        'name' => 'require',
        'type' => 'require|in:0,1',
        'path' => 'requireIf:type,0',
        'url' => 'requireIf:type,1',
        'id' => 'require',
        'p_id' => 'require',
        'state' => 'require'
    ];

    protected $message = [
        'name.require' => '权限名称不能为空',
        'type.require' => '权限类型不能为空',
        'path.requireIf' => '当为菜单权限时,路径不能为空',
        'url.requireIf' => '当为操作权限时,请求url不能为空',
        'id.require' => 'id不能为空',
        'p_id.require' => '性别不能为空',
        'state.require' => '状态不能为空',
    ];

    protected $scene = [
        'edit' => ['id', 'name'],
        'create' => ['name', 'type', 'path', 'url', 'p_id'],
        'disable' => ['id', 'state'],
    ];
}