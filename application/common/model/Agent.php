<?php

namespace app\common\model;
use think\Model;
use think\Validate;
class Agent extends Model
{

    protected $table='zp_admin';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'ctime_text',
        'logtime_text'
    ];
    //搜索器
    public function searchGroupIdAttr($query,$value,$data){
        $query->where('agent.group_id','in',$value);
    }
    //获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    //获取器
    public function getLogtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['logtime']) ? $data['logtime'] : '');
        return is_numeric($value) && !empty($value) ? date("Y-m-d H:i:s", $value) : ' - ';
    }
    public function groups()
    {
        return $this->belongsTo('AuthGroup', 'group_id')->setEagerlyType(0);
    }

    public function agents()
    {
        return $this->belongsTo('Admin', 'admin_id')->setEagerlyType(0);
    }
    //支付通道
    public function pay()
    {
        return $this->belongsTo('PaySetting', 'pay_id')->setEagerlyType(0)->joinType('left');
    }
    //短链接
    public function short()
    {
        return $this->belongsTo('Short', 'short_id')->setEagerlyType(0)->joinType('left');
    }
}