<?php

namespace app\model;

use think\Model;

class AdminModel extends Model
{
    /**
     * @var mixed
     */
    protected $name = 'admin';

    protected $schema = [
        'id' => 'int',
        'nickname' => 'string',
        'account' => 'string',
        'phone' => 'string',
        'email' => 'string',
        'sex' => 'string',
        'state' => 'string',
        'password' => 'int',
        'department' => 'string',
        'auth_group' => 'float',
        'admin' => 'int',
        'time_add' => 'datetime',
        'time_put' => 'datetime',
    ];

    /**
     * 根据id获取admin的基本信息
     *
     * @param $id
     * @return AdminModel|array|mixed|Model
     */
    public static function FindById($id)
    {
        return AdminModel::where('id',$id)
            ->field("id,nickname,account,phone,email,auth_group,admin,state")
            ->findOrEmpty();
    }
}