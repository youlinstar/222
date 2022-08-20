<?php

namespace app\manage\controller;
use think\Db;
use app\common\model\Config as configModel;
class Config extends Common
{
    protected $model,$types_option,$groups;
    function initialize()
    {
        parent::initialize();
        $this->model=new configModel();
        $this->types_option = [
            'text'=>'输入框',
            'textarea'=>'多行文本',
            'checkbox'=>'复选框',
            'radio'=>'单选框',
            'switchs'=>'开关',
            'select'=>'下拉框',
            'image'=>'单图上传',
            'images'=>'多图上传',
            'number'=>'数字输入框',
            'datetime'=>'日期选择器',
            'ueditor'=>'百度编辑器',
            'file'=>'单文件上传',
            'files'=>'多文件上传',
            'color'=>'颜色选择器',
            'array'=>'数组参数',
        ];
        $this->assign('types',$this->types_option);
        $this->groups=[];
        $group=$this->model->where('name','group')->value('default');
        if(!empty($group)){
            $options=explode("\n",$group);
            foreach($options as $option){
                if(stripos($option,'|')!==false){
                    $option=explode('|',$option);
                    $this->groups[trim($option[1])]=trim($option[0]);
                }
            }
        }
        $this->assign('groups',$this->groups);
    }


}