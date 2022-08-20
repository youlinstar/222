<?php

namespace app\common\model;
use think\Validate;
class AuthRule extends Common
{
    protected $rule = [
        'title' => 'require',
        'href' => 'require'
    ];
    protected $msg = [
        'title.require' => '请输入权限名称',
        'href.require' => '请输入权限控制器/方法',
    ];
    /**
     * 添加或修改
     * @param $params
     */
    public function addOrUpdate($params){
        //校验数据
        $validate = new Validate($this->rule, $this->msg);
        if(!$validate->check($params)){
            return [false,$validate->getError()];
        }
        #判断是新增还是修改
        if(!empty($params['id'])){
            $rule = $this->where(['id'=>$params['id']])->find();
            if(!$rule){
                return [false,'操作错误'];
            }
            #更新数据库
            $this->allowField(true)->save($params,['id'=>$params['id']]);
        }else{
            $params['ctime'] = time();
            #写入数据库
            $this->data($params)->allowField(true)->save();
        }
        return [true,'添加成功'];
    }
}