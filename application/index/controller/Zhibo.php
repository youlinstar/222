<?php


namespace app\index\controller;

use app\common\controller\Common;
use app\common\model\Admin;
use app\common\model\Users;
use app\common\model\Article;
use app\common\model\Hezi;
use app\common\model\PayShow;
use app\common\model\Spread;
use app\common\model\SpreadView;
use app\common\model\Swiper;
use app\common\model\VideoSort;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use think\Session;
use think\facade\Cookie;
use think\Db;

class Zhibo extends Common
{
    
    //入口
    public function index()
    {
        $url = $this->request->url();
        $from = $this->request->param('ldk');
        $ldk = decrypt($from);
        $ldk = json_decode($ldk, true);
        $ldk['open']='haokan';
        $url = '/zhibo?ldk='.encrypt(json_encode($ldk));
        $domain = getDomain(2, $this->uid);
        
        $params = $this->request->param();
        unset($params['qiantao']);
        $zhibo = $domain.'/zvideo?'.http_build_query($params);
        $res = Db::name('zhibo')->where(['status'=>1,'sortid'=>2])->find();
        if($res){
            header("location:{$zhibo}"); exit;
        }
        //去掉返回主页的数据库  改open
        // $url1 = 'http://'.$this->request->host().$url;
        $isFrame = config('setting.isFrame');
        $url = str_replace("/zhibo", "/zlist", $url);

        if ($isFrame == 1 && is_weixin() == false) {
            $link = getDomain(2, $this->uid);
            $params = $this->request->param();
            // unset($params['qiantao']);
            $link = $link . "/vlist?" . http_build_query($params);
            $this->view->engine->layout(false);
            $this->assign('url', $link);
            return $this->tpl_fetch('/iframe');
        }
        $user = Admin::get($this->uid);
        if ($user->status != 1) {
            $this->error("该用户已经被禁用!");
        }
        $douyin = config('setting.douyin');
        $domain = trim(getDomain(2, $this->uid)) . urldecode($url);
        if ($douyin == 1) {
            $domain = getDomain(2, $this->uid) . urldecode($url);
            $this->assign('url', $domain);
            return $this->tpl_fetch('/jump');
        }
        header("location:{$domain}");
        die;
    }
    //免费视频
    public function lists()
    {
       
       $params = $this->request->param();
       $domain = getDomain(1);
       unset($params['qiantao']);
       $zhibo = $domain.'/zvideo?'.http_build_query($params);
       
       $vip = $domain.'/haokan?'.http_build_query($params);
       $res = Db::name('zhibo')->where(['status'=>1,'sortid'=>2])->find();
       $status = 0;
       if($res){
           $zburl = $res['link'];
           $status = 1;
       }
       $this->view->assign('zhibo', $zhibo);
       $this->view->assign('vip', $vip);
       $this->view->assign('status', $status);
       return $this->tpl_fetch('/zhibo/index');
    
    }
    //直播
    public function video()
    {
       $res = Db::name('zhibo')->where(['status'=>1,'sortid'=>2])->find();
       $zburl = '';
       $status = 0;
       if($res){
           $zburl = $res['link'];
           $status = 1;
       }
       $params = $this->request->param();
       $domain = getDomain(1);
       
      
       $mianfei = $domain.'/zlist?'.http_build_query($params);
       $vip = $domain.'/haokan?'.http_build_query($params);
       $this->view->assign('vip', $vip);
       $this->view->assign('mianfei', $mianfei);
       
       $this->view->assign('status', $status);
       $this->view->assign('zburl', $zburl);
       return $this->tpl_fetch('/zhibo/zhibo');
    
    }
    
}