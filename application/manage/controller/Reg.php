<?php
namespace app\manage\controller;
use think\facade\Config;
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

        if($this->auth->isLogin() == 3){
            $url = $this->request->get('url', 'index/index');
            $this->error('您已经登录,请退出后在注册', $url);
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username','','trim');
            $password = $this->request->post('password','','trim');
            $captcha =  $this->request->post('captcha','','trim');
            $yqm =  $this->request->post('yqm','','trim');
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
            $yqmRes = $YqmObj->where('yqm',$yqm)->find();

            if(empty($yqmRes))
            {
                return callback(400,'邀请码不存在');
            }

            if(isset($yqmRes) && $yqmRes['status'] != 0)
            {
                return callback(400,'邀请码已被使用');
            }

            $userObj = new Agent();
            $res = $userObj->where('username',$username)->find();

            if(!empty($res)){
                return callback(404,'用户名已经存在');
            }
            $config = config::get('setting.');
            $result = false;
            Db::startTrans();
            try {
                    $params = [];
                    $params['admin_id'] = $yqmRes->uid;
                    $params['username'] = $username;
                    $params['group_id'] = 2;
                    $params['salt']=mt_rand(111111,999999);
                    $params['password']=md5($password.$params['salt']);
                    $params['min_take']=$config['agent_min_take'];
                    $params['take_num']=$config['agent_take_num'];
                    $params['cash_fee']=$config['agent_cash_fee'];
                    $params['min_cash']=$config['min_cash'];
                    $params['money']=$config['agent_dp_min'];
                    $params['money1']=$config['agent_day_min'];
                    $params['money2']=$config['agent_week_min'];
                    $params['money3']=$config['agent_month_min'];
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
