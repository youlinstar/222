<?php

namespace app\manage\controller;
use app\manage\model\AdminLog;
use app\common\model\Admin;
use think\facade\Config;
use think\facade\Hook;
use think\Validate;
class Login extends Common
{

    protected $noNeedLogin = ['index','check'];
    public function initialize()
    {
        parent::initialize();
    }

    public function index(){
        $url = $this->request->get('url', 'index/index');
        if($this->auth->isLogin()){
            $this->success('您已经登录，不需重复登录', $url);
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $captcha =  $this->request->post('captcha');
            $keeplogin = $this->request->post('keeplogin');
            $rule = [
                'username'  => 'require',
                'password'  => 'require',
                'captcha'   => 'require|captcha',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                'captcha'   => $captcha,
            ];
            $validate = new Validate($rule, [], ['username' =>'请输入登录用户名', 'password' =>'请输入登录密码', 'captcha' =>'请输入验证码']);
            $result = $validate->check($data);
            if (!$result){
                return callback(400,$validate->getError());
            }
            AdminLog::setTitle('登陆操作');
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result === true){
                return callback(200,'登录成功',url($url),['id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : '用户名或密码错误';
                return callback(400,$msg);
            }
        }
        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin()){
            $this->redirect($url);
        }
        return $this->view->fetch();
    }

    public function check($code){
       return captcha_check($code);
    }
    public function reg(){
		 $data = input("post.");
		  $config = cache('db_config_data');
		if(!$config){            
            $config = api('Config/lists');                          
            cache('db_config_data',$config);
        }
        config($config); 

        if(config('web_site_close') == 0  ){
            $this->error('站点已经关闭，请稍后访问~');
        }
		 if(config('user_allow_register')==0){
			 return json(['code' => 0, 'url' => '', 'msg' => "注册已关闭"]);
		 }
		 
		 $member=Db::name('member')->where('account', $data['account'])->find();
		 if(!empty($member)){
			 return json(['code' => 0, 'url' => '', 'msg' => '用户名已存在']);
		 }
		 
		 
		 $yqm=Db::name('yqm')->where(['yqm'=>$data['yqm'],"zt"=>'未使用'])->find();
		 if(empty($yqm)){
			return json(['code' => 0, 'url' => '', 'msg' => '邀请码不存在']); 
			 
		 }
		 
		 Db::name('yqm')->where('yqm', $data['yqm'])->update(['name'=>$data['account'],'zt'=>'已使用']);
		 
		 $param=[];
		 $param['account']=$data['account'];
		 $param['nickname']=$data['nickname'];
		 $param['head_img']="/static/admin/images/head_default.gif";
         $param['password']=$data['password'];
		 $param['create_time']=time();
		 $param['update_time']=time();
		 $param['status']=1;
		 $param['txfeilv']=config('yhtxfl');
		 $param['pid']=$yqm['userid']=="admin"?0:$yqm['userid'];
		 $param['syyqm']=$yqm['yqm'];

		 $shangji = Db::name('member')->where('id',$yqm['userid'])->find();
		 $param['level'] = $shangji['level'] + 1;
		 $userid=Db::name('member')->insertGetId($param);
		 
		  $user = new UserType();
		 $info = $user->getRoleInfo(1);
        
        session('dailiuid', $userid);         //用户ID
        session('dailiname', $param['nickname']);  //用户名
        session('dlportrait', $param['head_img']); //用户头像
        session('dlrolename', $info['title']);    //角色名
        session('dlrule', $info['rules']);        //角色节点
        session('dlname', $info['name']);         //角色权限
  
        //更新管理员状态
        $param = [
            'login_num' =>  1,
            'last_login_ip' => request()->ip(),
            'last_login_time' => time()
        ];

        Db::name('member')->where('id',$userid)->update($param);
		 
		 return json(['code' => 1, 'url' => url('index/index'), 'msg' => '注册成功！']); 
	}
}