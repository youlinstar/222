<?php

namespace app\common\model;
use think\Model;
class Common extends Model
{

    const SUPER_ADMIN_ID=1;//超级管理员ID
    protected $prefix;

    public function initialize(){
        $this->prefix=config('database.prefix');
    }

}