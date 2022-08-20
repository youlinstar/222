<?php

namespace app\common\model;
use app\common\model\Common;
use think\Validate;
use think\Db;
class Config extends Common
{
    protected $rule = [
        'group' => 'require',
        'types' => 'require',
        'title' => 'require|length:2,30',
        'name' => 'require|length:2,30|alphaDash',
    ];
    protected $msg = [
        'title.require' => '请输入配置标题',
        'title.length' => '配置标题长度2~30位',
        'name.require' => '请输入配置名',
        'name.length' => '配置名长度2~30位',
        'name.alphaDash' => '配置名由字母、下划线、数字组成',
        'group.require' => '请选择所属分组',
        'types.require' => '请选择配置类型',
    ];
    protected $scene = [
        'add'  =>  ['group','types','title','name'],
    ];

    /**
     * 数据验证
     */
    public function validate($data,$path){
        //校验数据
        $validate = new Validate($this->rule, $this->msg);
        if(!$validate->check($data)){
            return [false,$validate->getError()];
        }
        return [true,'success'];
    }
    /**
     * 获取数据
     * @param $params
     */
    public function getList($params){
        if(empty($params['limit'])){
            $params['limit']=config('paginate.list_rows');
        }
        $list =$this->where('groups',$params['group'])->order($params['order'])->page($params['page'],$params['limit'])->select()->toArray();
        $total=$this->where('groups',$params['group'])->count('id');
        return ['list'=>$list,'total'=>$total];
    }
    /**
     * 添加或修改
     * @param $params
     */
    public function addOrUpdate($params){

        $params['status']=1;
        $params['ctime']=time();
        #判断是新增还是修改
        if(!empty($params['id'])){
            $module = $this->where('id',$params['id'])->find();
            if(!$module){
                return [false,'配置更新失败'];
            }
            #更新数据库
            $res=$this->allowField(true)->save($params,['id'=>$params['id']]);
            if(!$res){
                return [false,'配置更新失败'];
            }
            return [true,'配置更新成功'];
        }else{
            $id=$this->where('name',$params['name'])->value('id');
            if($id){
                return [false,'配置名称已经存在'];
            }
            #写入数据库
            $res=self::create($params);
            if(!$res){
                return [false,'添加失败'];
            }
            return [true,'配置添加成功'];
        }
    }
}