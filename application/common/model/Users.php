<?php

namespace app\common\model;
use think\Model;
use think\Validate;
class Users extends Model
{

    protected $table='zp_users';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
   
}