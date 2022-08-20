<?php

namespace app\common\model;
use think\Model;
class AdminBill extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'ctime';

    protected $updateTime = false;

    // 追加属性
    protected $append = [
        'ctime_text'
    ];
    //获取器
    public function getCtimeTextAttr($value,$data){
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    /**
     * 用户
     * @return \think\model\relation\BelongsTo
     */
    public function agent()
    {
        return $this->belongsTo('Agent', 'admin_id')->setEagerlyType(0);
    }

    /**
     * 变更会员余额
     * @param int    $mode   1增加或2减少
     * @param int    $type   类型
     * @param float  $money  余额
     * @param int    $uid    会员ID
     * @param string $remark 备注
     */
    public static function money($mode,$type,$money,$uid,$remark)
    {
        $admin = Admin::where('id',$uid)->find();
        if ($admin && $money != 0) {
            $before = $admin->balance;
            if($mode==1){
                $after = $admin->balance + $money;
            }else{
                $after = $admin->balance - $money;
            }
            $admin->balance=$after;
            $admin->save();
            //写入日志
            self::create(['mode'=>$mode,'type'=>$type,'admin_id' => $uid, 'money' => $money, 'before' => $before, 'after' => $after, 'remark' => $remark]);
            return [true,'success'];
        }
        return [false,'操作失败'];
    }
}