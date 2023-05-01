<?php

namespace app\model;

use think\Model;

class AuthGroupModel extends Model
{
    /**
     * @var mixed
     */
    protected $name = 'auth_group';

    protected $schema = [
        'id' => 'int',
        'name' => 'string',
        'auth_id' => 'string',
        'notes' => 'string',
        'time_add' => 'datetime',
    ];
}