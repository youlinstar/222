<?php

namespace app\manage\controller;
use app\common\model\AdminBill;
use app\common\model\Short;
use think\Db;
use think\Exception;
use zp\Leftnav;
use app\common\model\Admin;
use app\common\model\AuthGroup;
use app\common\model\AuthRule;
class Auth extends Common
{
    //管理员列表
    public function adminList(){
        if(request()->isPost()){
            $admin=new Admin();
            $map=[];
            if(!$this->auth->isSuperAdmin()){
                $map['a.id']=$this->auth->id;
            }else{
                $map['b.is_auth']=0;
            }
            $list=$admin->getList(['where'=>$map,'order'=>'a.id asc']);
            return callback(200,'success','',$list);
        }
        return $this->fetch('adminlist');
    }
    /**
     * 添加管理
     * @return array
     */
    public function adminAdd(){
        if(request()->isPost()){
            $data = $this->request->param('row/a');
            if(empty($data['username'])){
                return callback(400,'请输入登录用户名');
            }
            if(empty($data['password'])){
                return callback(400,'请输入登录密码');
            }
            if(empty($data['group_id'])){
                return callback(400,'请选择所属用户组');
            }
            $admin=new Admin();
            list($res,$msg)=$admin->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            return callback(200,'添加成功',url('auth/adminlist'));
        }else{
            $payList=\app\common\model\PaySetting::where(['status'=>1])->select();
            $shortList=Short::where('status',1)->select();
            $this->view->assign('payList',$payList);
            $this->view->assign('shortList',$shortList);
            $groups=AuthGroup::where('is_auth',0)->select();
            $this->assign('groups',$groups);
            $this->assign('title',lang('add').lang('admin'));
            $this->assign('info',json_encode(['group_id'=>0]));
            return view('adminadd');
        }
    }
    //更新管理员信息
    public function adminEdit($ids=0){
        if(request()->isPost()){
            $data = $this->request->param('row/a');
            $admin=new Admin();
            list($res,$msg)=$admin->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            return callback(200,'更新成功',url('auth/adminlist'));
        }else{
            $groups = AuthGroup::where('is_auth',0)->select();
            $info = Admin::where('id',$ids)->find();
            $payList=\app\common\model\PaySetting::where(['status'=>1])->select();
            $shortList=Short::where('status',1)->select();
            $this->view->assign('payList',$payList);
            $this->view->assign('shortList',$shortList);
            $this->assign('row',$info);
            $this->assign('groups',$groups);
            $this->assign('title',lang('edit').lang('admin'));
            return view('adminedit');
        }
    }


    //删除管理员
    public function adminDel(){
        $admin_id=input('post.id/d');
        $admin=new Admin();
        if($this->auth->isSuperAdmin()){
            $admin::destroy(['id'=>$admin_id]);
            return callback(200,'删除成功!');
        }else{
            return callback(400,'您没有删除管理员的权限');
        }
    }
    //修改数据权限
    /*public function setAuth(){
        $id=input('post.id');
        if(empty($id)){
            return callback(400,'操作错误');
        }
        $group=AuthGroup::where('id',$id)->find();//判断当前状态情况
        if($group){
            $group->is_auth=input('post.is_auth');
            $res=$group->save();
            if(!$res){
                return callback(404,'设置错误');
            }
            return callback(200,'sucess');
        }
        return callback(400,'操作错误');
    }*/
    //修改管理员状态
    public function adminState(){
        $id=input('post.id');
        if(empty($id)){
            return callback(400,'操作错误');
        }
        $admin=Admin::where('id',$id)->find();//判断当前状态情况
        if($admin){
            if($admin->status==1){
                $admin->status=0;
                $admin->save();
                return callback(200,'sucess','',['status'=>0]);
            }else{
                $admin->status=1;
                $admin->save();
                return callback(200,'sucess','',['status'=>1]);
            }
        }
        return callback(400,'操作错误');
    }

    /*-----------------------用户组管理----------------------*/
    //用户组管理
    public function adminGroup(){
        if(request()->isPost()){
            $list = AuthGroup::select();
            foreach($list as $k =>$v){
                $list[$k]['ctime']=date('Y-m-d H:i:s',$v['ctime']);
            }
            return callback(200,'success','',$list);
        }
        return $this->fetch('admingroup');
    }
    //删除管理员分组
    public function groupDel(){
        $res=AuthGroup::destroy(['id'=>input('id/d')]);
        if(!$res){
            return callback(400,'删除失败');
        }
        return callback(200,'删除成功');
    }
    //添加分组
    public function groupAdd(){
        if(request()->isPost()){
            $data=input('post.');
            $group=new AuthGroup();
            list($res,$msg)=$group->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            return callback(200,'添加成功',url('auth/admingroup'));
        }else{
            $this->assign('title','添加用户组');
            $this->assign('info','null');
            return $this->fetch('groupform');
        }
    }
    //修改分组
    public function groupEdit(){
        if(request()->isPost()) {
            $data=input('post.');
            $group=new AuthGroup();
            list($res,$msg)=$group->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            return callback(200,'更新成功',url('auth/admingroup'));
        }else{
            $id = input('id/d',0);
            $info = AuthGroup::get(['id'=>$id]);
            $this->assign('info', json_encode($info,true));
            $this->assign('title','编辑用户组');
            return $this->fetch('groupform');
        }
    }
    //分组配置规则
    public function groupAccess(){
        $group_id=input('id/d',0);
        if(empty($group_id)){
            return callback(400,'操作错误');
        }
        $nav = new Leftnav();
        $admin_rule=AuthRule::field('id,pid,title')->order('sort asc')->select();
        $groups = AuthGroup::where('id',$group_id)->field('id,title,rules')->find();
        $arr = $nav->auth($admin_rule,$pid=0,$groups->rules);
        $this->assign('title',$groups->title);
        $this->assign('rules',$groups->rules);
        $this->assign('id',$groups->id);
        $this->assign('data',json_encode($arr,true));
        return $this->fetch('groupaccess');
    }
    public function groupSetAccess(){
        $rules = input('post.rules');
        $id=input('post.id/d');
        if(empty($id)){
            return callback(400,'操作错误1');
        }
        if(empty($rules)){
            return callback(400,'请选择权限');
        }
        $rules=implode(',',$rules);
        $res=AuthGroup::where('id',$id)->update(['rules'=>$rules]);
        if($res){
            return callback(200,'权限配置成功',url('admingroup'));
        }else{
            return callback(400,'操作错误2');
        }
    }

    /********************************权限管理*******************************/
    public function adminRule(){
        if(request()->isPost()){
            $nav = new Leftnav();
            $data = cache('rulelist');
            if(!$data){
                $authRule = authRule::order('sort asc')->select();
                $data = $nav->menu($authRule);
                cache('rulelist',$data,3600);
            }
            return callback(200,'success','',$data);
        }
        return $this->fetch('adminrule');
    }

    public function ruleAdd(){
        if(request()->isPost()){
            $data = input('post.');
            $rule=new AuthRule();
            list($res,$msg)=$rule->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            cache('rulemenu',null);
            cache('rulelist',null);
            return callback(200,'权限添加成功',url('ruleadd'));
        }else{
            $rule=new AuthRule();
            $nav = new Leftnav();
            $arr = cache('rulemenu');
            if(!$arr){
                $authRule = $rule->order('sort asc')->select();
                $arr = $nav->menu($authRule);
                cache('rulemenu', $arr, 3600);
            }
            $this->assign('rule_menu',$arr);//权限列表
            return $this->fetch('ruleadd');
        }
    }
    public function ruleEdit(){
        if(request()->isPost()) {
            $data = input('post.');
            $rule=new AuthRule();
            list($res,$msg)=$rule->addOrUpdate($data);
            if(!$res){
                return callback(400,$msg);
            }
            cache('rulemenu',null);
            cache('rulelist',null);
            return callback(200,'保存成功',url('adminrule'));
        }else{
            $id=input('id/d',0);
            $rule = authRule::where('id',$id)->find();
            $this->assign('rule',json_encode($rule,true));
            return $this->fetch('ruleedit');
        }
    }

    /**
     * 更新排序
     * @return array
     * @throws \Exception
     */
    public function ruleOrder(){
        $id = input('post.id/d',0);
        if(empty($id)){
            return callback(400,'操作失败');
        }
        $sort=input('post.sort/d',0);
        $res=AuthRule::where('id',$id)->update(['sort'=>$sort]);
        if(!$res){
            return callback(400,'更新失败');
        }
        cache('rulemenu',null);
        cache('rulelist',null);
        return callback(200,'更新成功',url('adminrule'));
    }

    public function ruleStatus(){
        $id=input('post.id/d',0);
        $is_menu=AuthRule::where('id',$id)->value('is_menu');//判断当前状态情况
        cache('rulemenu',null);
        cache('rulelist',null);
        if($is_menu==1){
            $res=AuthRule::where('id',$id)->setField(['is_menu'=>0]);
            if(!$res){
                return callback(400,'更新失败');
            }
            return callback(200,'success','',['is_menu'=>0]);
        }else{
            $res=AuthRule::where('id',$id)->setField(['is_menu'=>1]);
            if(!$res){
                return callback(400,'更新失败');
            }
            return callback(200,'success','',['is_menu'=>1]);
        }
    }
    public function ruleAuth(){
        $id=input('post.id/d',0);
        $is_open=AuthRule::where('id',$id)->value('open');//判断当前状态情况
        cache('rulemenu',null);
        cache('rulelist',null);
        if($is_open==1){
            $res=AuthRule::where('id',$id)->setField(['open'=>0]);
            if(!$res){
                return callback(400,'更新失败');
            }
            return callback(200,'success','',['open'=>0]);
        }else{
            $res=AuthRule::where('id',$id)->setField(['open'=>1]);
            if(!$res){
                return callback(400,'更新失败');
            }
            return callback(200,'success','',['open'=>1]);
        }
    }

    public function ruleDel(){
        $id=input('param.id/d',0);
        $res=authRule::destroy($id);
        if(!$res){
            return callback(400,'删除失败');
        }
        cache('rulemenu',null);
        cache('rulelist',null);
        return callback(200,'删除成功');
    }
}