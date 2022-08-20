<?php

namespace app\manage\controller;
use zp\FormList;
use app\common\model\Setting as SettingModel;
use app\common\model\Config;
class Setting extends Common
{
    protected $groups,$config;
    public function initialize(){
        $this->groups=[];
        $group=Config::where('name','group')->value('default');
        if(!empty($group)){
            $options=explode("\n",$group);
            foreach($options as $option){
                if(stripos($option,'|')!==false){
                    $option=explode('|',$option);
                    if($option[1]!=='system'){
                        $this->groups[trim($option[1])]=trim($option[0]);
                    }
                }
            }
        }
    }
    //站点设置
    public function index(){
        $setting=new SettingModel();
        if(request()->isPost()){
            $datas = input('post.');
            foreach($datas as $k=>$v){
                list($res,$msg)=$setting->setValue($k,$v);
                if(!$res){
                    return callback(400,$msg);
                }
            }
            cache('setting',null);
            return callback(200,'设置保存成功', url('setting/index'));
        }else{
            #获取配置项
            foreach($this->groups as $k=>$v){
                $config[$k]=Config::where('group',$k)->order('indexid asc')->select()->toArray();
            }
            #获取已设置数据
            $data=$setting->getAll();
            $form=new FormList($config);
            
            $this->assign('groups',$this->groups);
       
            foreach ($config as $k => $v){
               foreach ($v as $kk => $vv){
                    foreach ($data as $kkk => $vvv){
                       
                        if($vv['name']==$kkk){
                            $config[$k][$kk]['default'] = $vvv;
                        }
                    }
                } 
            }
            
            $this->assign('form',$form);
            $this->assign('config',$config);
            $this->assign('setting',$data);
            return $this->fetch();
        }
    }

}
