<?php

namespace app\common\model;
use think\Model;
class Spread extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 追加属性
    protected $append = [
        'ctime_text',
        'otime_text'
    ];

    //获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    //获取器
    public function getOtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['otime']) ? $data['otime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    public function sort()
    {
        return $this->belongsTo('VideoSort', 'sortid')->setEagerlyType(0);
    }


}