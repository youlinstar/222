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
use think\facade\Session;
use think\facade\Cookie;
use think\Db;


class Index extends Common
{
   
    public function index()
    {
        
        $url = $this->request->url();
        $main = 'http://'.$this->request->host().$url;
        $isFrame = config('setting.isFrame');
        $url = str_replace("/haokan", "/token", $url);
        // if ($isFrame == 1 && is_weixin() == false) {
        //     $link = getDomain(2, $this->uid);
        //     $params = $this->request->param();
        //     unset($params['qiantao']);
        //     $link = $link . "/vlist?" . http_build_query($params);
        //     $this->view->engine->layout(false);
        //     $this->assign('url', $link);
        //     return $this->tpl_fetch('/iframe');
        // }
        $user = Admin::get($this->uid);
        if ($user->status != 1) {
            $this->error("该用户已经被禁用!");
        }
        // $douyin = config('setting.douyin');
         
        $domain = trim(getDomain(4, $this->uid)) . urldecode($url);
        // if ($douyin == 1) {
        //     $domain = getDomain(2, $this->uid) . urldecode($url);
        //     $this->assign('url', $domain);
        //     return $this->tpl_fetch('/jump');
        // }
        header("location:{$domain}");
        die;
    }
    
    public function token(){
        
        $from = $this->request->param('ldk');
        $ldk = json_decode(decrypt($from), true);
        $biaoshi = Cookie::get('biaoshi');
        if(!isset($biaoshi) || empty($biaoshi)){
            $biaoshi = md5(time().substr(microtime(),2,6) . userAgent());
            Cookie::forever('biaoshi', $biaoshi);
        }
        
    
        $ldk['ua'] = $biaoshi;
        
        $url = '/vlist?ldk=' . encrypt(json_encode($ldk));
        $domain = trim(getDomain(2, $this->uid)) . urldecode($url);
        header("location:{$domain}");
        die;
    }

    //落地页
    public function lists()
    {
        $url = $this->request->url();
       
        $payed = $this->request->param('payed/d', 0);
        
        $qiantao = $this->request->param('qiantao');
        if ($qiantao == 1) {
            $domain = getDomain(2, $this->uid);
            $params = $this->request->param();
            unset($params['qiantao']);
            $url = $domain . "/vlist?" . http_build_query($params);
            $this->view->engine->layout(false);
            $this->assign('url', $url);
            return $this->tpl_fetch('/iframe');
        }
        $hezi = 0;
        $from = $this->request->param('ldk');
       
        if (!empty($from)) {
            $ldk = decrypt($from);
            $ldk = json_decode($ldk, true);
            $hezi = empty($ldk['hezi']) ? 0 : $ldk['hezi'];
        }
        
        $this->setLog($this->uid, $this->request->ip(), 0);
        $domain = $this->request->host() . $this->request->url();
        $this->assign('fav', $domain);
        $heziInfo = ['video_url' => ''];
        if ($hezi) {
            $heziInfo = Hezi::where(['id' => $hezi, 'status' => 1])->find();
            if (empty($heziInfo)) {
                $heziInfo = ['video_url' => ''];
            }
        }
     
        $this->assign('hezi', $heziInfo);
        #轮播图
        $swiper = Swiper::where('status', 1)->select();
        #公告
        $notice = Article::where(['status' => 1, 'sort_id' => 2])->find();
        $sortList = VideoSort::where('status', 1)->order('indexid desc')->select();
        $this->view->assign('notice', $notice);
        $this->view->assign('payed', $payed);
        $this->view->assign('sortList', $sortList);
        $this->view->assign('swiperList', $swiper);
        $this->assign('cookieip', '');
        $this->assign('userinfo', $GLOBALS['user']);
        $this->assign('domain', $this->request->domain());
        return $this->tpl_fetch('/index');
    }

    /**
     * 视频播放
     * @return mixed
     */
    public function video()
    {
        
        
        $ldk = $this->request->param('ldk');
        $ip = $this->request->ip();
        $form = json_decode(decrypt($this->request->param('ldk')),true);
        $id = $form['uid'];
        $ua = $form['ua'];
        $vid = $this->request->param('vid');
        $linkInfo = Spread::where('id', $vid)->find();
        if (empty($linkInfo)) {
            $this->error("视频资源丢失!", '', '', 200);
        }
        $linkInfo->views = ['inc', 1];
        $linkInfo->ctime = time();
        $linkInfo->save();

        $payedVid = $this->getPayVideo();
        $vidArr = $payedVid['vid'];
        $pay = 0;
        $vid = array_intersect($vidArr, [$vid]);
        $payed = false;
        $isjf = 0;
        if ($vid) {
            $payed = (new PayShow())
                ->where('v_id', 'in', $vid)
                ->where('etime', '>', time())
                ->where('ua',$ua)->find();
            if ($payed) {
                $pay = 1;
            }
        }
        if ($payedVid['is_day'] == 1 || $payedVid['is_week'] == 1 || $payedVid['is_month'] == 1) {
            $pay = 1;
            $payed = true;
        }
        if ($payed == false && $linkInfo['try_see'] == 0) {
            $this->error('视频不存在!或者已过期!', '', '', 200);
        }
        #记录访问记录
        $this->setLog($linkInfo->uid, $ip, $linkInfo->id);
        
        
        $admin = Admin::where('id',$id)->find();
        
        $domain = $admin->vipurl;
        #m3u8
        $m3u8='';
        if(stripos($linkInfo->video_url,'.m3u8')==false && stripos($linkInfo->video_url,'.mp4')==false){
            header('Location:'.$linkInfo->video_url);
            exit;
        }
        
        $this->assign('m3u8', $m3u8);
        //vip推广二维码
        $this->assign('fav', $domain);
        $this->assign('payed', $payed);
        $this->assign('pay', $pay);
        $this->assign('link', $linkInfo);
        return $this->tpl_fetch('/video');
    }

    /**
     * 输出二维码
     */
    public function qrcode($text = '')
    {
        $qrCode = new QrCode($text);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setWriterByName('png');
        header('Content-Type: ' . $qrCode->getContentType());
        echo $qrCode->writeString();
        exit;
    }
    
    /**
     * 输出二维码地址
     */
    public function getqrcode($text = '')
    {
        $qrCode = new QrCode($text);
        $qrCode->setSize(300);
        $qrCode->setWriterByName('png');
        $qrCode->setMargin(10);
        $time = time();
        $url = 'uploads/qrocde/'. $time . '.png';
        $qrCode->writeFile($url);
        return $url;
    }

    /**
     * 设置记录
     */
    protected function setLog($uid, $ip, $v_id)
    {
        
        $view = SpreadView::where(['ip' => $ip, 'uid' => $uid])->find();
        $res = '';
        if (!empty($view)) {
            // $view->v_id = $v_id;
            // $view->num = ['inc', 1];
            // $view->ctime = time();
            // $res = $view->save();
        } else {
            $res = SpreadView::create([
                'v_id' => $v_id,
                'num' => 1,
                'ip' => $ip,
                'uid' => $uid,
                'ctime' => time()
            ]);
        }
        if (!$res) {
            return [false, '记录错误'];
        }
        return [true, '记录成功'];
    }

    /**
     * 获取已经支付的视频ID
     */
    protected function getPayVideo()
    {
        $ldk = json_decode(decrypt($this->request->param('ldk')),true);
        $uid = $ldk['uid'];
        $ua = $ldk['ua'];
       
        $pay = (new PayShow())->where('etime', '>', time())->where('uid', $uid)->where('ua',$ua)->select()->toArray();
       
        $is_day = 0;
        $is_week = 0;
        $is_month = 0;
        foreach ($pay as $k => $item) {
            //是否有包天
            if ($item['is_day'] == 1 && $item['etime'] > time()) {
                $is_day = 1;
            }
            //是否有包天
            if ($item['is_week'] == 1 && $item['etime'] > time()) {
                $is_week = 1;
            }
            if ($item['is_month'] == 1 && $item['etime'] > time()) {
                $is_month = 1;
            }
        }
        $pay_ids = [];
        if (!empty($pay)) {
            $pay_ids = array_column($pay, 'v_id');
        }
        return ['vid' => $pay_ids, 'is_day' => $is_day, 'is_week' => $is_week, 'is_month' => $is_month];
    }

    /**
     * 获取分类
     */
    public function sorts()
    {
        return $this->tpl_fetch('/sort');
    }
    
    public function pay(){
        halt($this->request());
    }
    
}