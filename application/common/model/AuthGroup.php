<?php

namespace app\common\model;
use think\validate;
class AuthGroup extends Common
{
    protected $rule = [
        'title' => 'require|length:3,10|chsAlpha',
    ];
    protected $msg = [
        'title.require' => '请输入用户组名称',
        'title.length' => '用户组名称长度6~20位',
        'title.chsAlpha' => '用户组名称只能是汉字或字母',
    ];

    /**添加或更新
     * @param $params
     * @return array
     */
    public function addOrUpdate($params){
        $validate = new Validate($this->rule, $this->msg);
        if(!$validate->check($params)){
            return [false,$validate->getError()];
        }
        if(!empty($params['id'])){
            $this->allowField(true)->save($params,['id'=>$params['id']]);
        }else {
            $params['status']=1;
            $params['ctime']=time();
            $this->data($params)->allowField(true)->save();
        }
        return [true,'操作成功'];
    }
}