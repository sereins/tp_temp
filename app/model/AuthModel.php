<?php

namespace app\model;

use think\Model;

class AuthModel extends Model
{
    protected $name = 'auth';

    protected $schema = [
        'id' => 'int',
        'name' => 'string',
        'icon' => 'string',
        'path' => 'string',
        'url' => 'string',
        'type' => 'int',
        'state' => 'int',
        'p_id' => 'int',
        'time_add' => 'datetime',
    ];
}