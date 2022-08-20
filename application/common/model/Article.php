<?php

namespace app\common\model;

use think\Model;

class Article extends Model
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
     * 分类
     * @return \think\model\relation\BelongsTo
     */
    public function sort()
    {
        return $this->belongsTo('ArticleSort', 'sort_id')->setEagerlyType(0);
    }
    /**
     * 发布人
     * @return \think\model\relation\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id')->setEagerlyType(0);
    }
}