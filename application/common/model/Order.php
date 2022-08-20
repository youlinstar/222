<?php

namespace app\common\model;
use think\Model;
class Order extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    // 追加属性
    protected $append = [
        'ctime_text',
        'utime_text',
        'ptime_text'
    ];
    //获取器
    public function getUtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['utime']) ? $data['utime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : '-';
    }
    //获取器
    public function getPtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ptime']) ? $data['ptime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : '-';
    }
    //获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    //所属上级代理
    public function agent()
    {
        return $this->belongsTo('Admin', 'pid')->setEagerlyType(0)->joinType('left');
    }

    public function pay()
    {
        return $this->belongsTo('PaySetting', 'pay_id')->setEagerlyType(0);
    }
    //推广视频
    public function spread()
    {
        return $this->belongsTo('Spread', 'v_id')->setEagerlyType(0);
    }
    //推广用户ID
    public function user()
    {
        return $this->belongsTo('Agent', 'uid')->setEagerlyType(0);
    }

}