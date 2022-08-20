<?php

namespace app\common\model;
use think\Model;
class Bill extends Model
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
     * 所属推广用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Admin', 'uid')->setEagerlyType(0);
    }

    /**
     * 变更会员余额
     * @param int    $mode   1增加或2减少
     * @param int    $type   类型
     * @param float  $money  余额
     * @param int    $uid    会员ID
     * @param string $remark 备注
     * @param int $work_id 业务ID
     */
    public static function money($mode,$type,$money,$uid,$remark,$work_id)
    {
        $user = Admin::where('id',$uid)->find();
        if ($user && $money != 0) {
            $before = $user->balance;
            if($mode==1){#增加
                $after = $user->balance + $money;
            }else{#减少
                $after = $user->balance - $money;
            }
            $user->balance=$after;
            $user->save();
            //写入日志
            self::create(['mode'=>$mode,'type'=>$type,'uid' => $uid, 'money' => $money, 'before' => $before, 'after' => $after, 'remark' => $remark, 'work_id' => $work_id]);
            return [true,'SUCCESS'];
        }
        return [false,'操作失败'];
    }
}