<?php
namespace app\manage\controller;
use think\Validate;
use think\Controller;
use app\common\model\Yqm;
use app\common\model\Agent;
use app\manage\lib\Auth;
use think\Db;


class Reg extends controller
{
     public function initialize()
    {
        $this->auth = Auth::instance();
        
       
    }
    public function index(){
        
        if($this->auth->isLogin()){
            $url = $this->request->get('url', 'index/index');
            $this->error('您已经登录,请退出后在注册', $url);
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $captcha =  $this->request->post('captcha');
            $yqm =  $this->request->post('yqm');
            $rule = [
                'username'  => 'require',
                'password'  => 'require',
                'captcha'   => 'require|captcha',
                'yqm'       => 'require',
                
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                'captcha'   => $captcha,
                'yqm'       => $yqm,
            ];
            $validate = new Validate($rule, [], ['username' =>'请输入登录用户名', 'password' =>'请输入登录密码', 'captcha' =>'请输入验证码','yqm'=>"邀请码"]);
            $result = $validate->check($data);
            
            if (!$result){
                return callback(400,$validate->getError());
            }
            
            $YqmObj = new Yqm();
            $yqmRes = $YqmObj->where(['yqm'=>$yqm,'status'=>0])
                   ->find();
            if(!$yqmRes){
                return callback(400,'邀请码已过期');
            }
            $userObj = new Agent();
            $res=$userObj->where('username',$username)->find();
            if(!empty($res)){
                return callback(404,'用户名已经存在');
            }
            $result = false;
            Db::startTrans();
            try {
                    $params = [];
                    $params['admin_id'] = $yqmRes->uid;
                    $params['username'] = $username;
                    $params['group_id'] = 2;
                    $params['salt']=mt_rand(111111,999999);
                    $params['password']=md5($password.$params['salt']);
                    $params['money']=5;
                    $params['is_yqm']=1;
                    $result = $userObj->create($params,true);
                 Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                return callback(404,$e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                return callback(404,$e->getMessage());
            }
            if ($result !== false) {
                $YqmObj->where('yqm',$yqm)->update(['status'=>1]);
                return callback(200,'注册成功');
            } else {
                return callback(404,'注册失败');
            }
          
        }
        return $this->view->fetch('/login/reg');
    }
}