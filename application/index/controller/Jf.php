<?php
/**
 * 积分页面
 */

namespace app\index\controller;

use app\common\controller\Common;
use app\common\model\Admin;
use app\common\model\Domain;
use app\common\model\Users;
use app\common\model\PayShow;
use app\common\model\Video;
use app\common\model\SpreadView;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use think\facade\Cookie;
class Jf extends Common
{
    
   /**
    * 入口
    */
    public function index()
    {   

        $user = Admin::get($this->uid);
        if (empty($user) || $user->status != 1) {
            $jump = 'https://news.qq.com';
            header("location:{$jump}");
            exit;
        }       
        
        $domain = trim(getDomain(4, $this->uid)) . '/vtoken';
        $this->assign('url',$domain);
        $this->assign('ldk',$this->ldk);
        return $this->fetch('token');
    }
    
    public function token(){

        #获取来路
        $refererUrl = parse_url($_SERVER['HTTP_REFERER']);
        $refererHost = $refererUrl['host'];
        #匹配来路
        $domainInfo = Domain::where(['type' => 1,'domain'=>$refererHost])->cache(300)->find();
        if(empty($domainInfo)){
            exit('Hello World');
        }
        $form = $this->form;

        if(!empty($form) && isset($form['ua']) && !empty($form['ua'])){
            Cookie::forever('biaoshi', $form['ua']);
            $biaoshi = $form['ua'];

        }else{
            $biaoshi = Cookie::get('biaoshi');

            if(!isset($biaoshi) || empty($biaoshi)){
                $biaoshi = md5(time().substr(microtime(),2,6) . userAgent());              
                
                do{
                    $pwd = rand_string(6);
                    $res = Users::where('pwd',$pwd)->find();
                }while($res);
                
                $userData = [
                    'ua' =>$biaoshi,
                    'uid'=>$this->uid,
                    'ctime'=>time(),
                    'pwd'=>$pwd,
                    'jifen'=>10,
                    'ip'=>getIp()
                    ];
                $M = new Users();
                $res = $M->allowField(true)->save($userData);
                if(!$res){
                    exit("数据错误");
                }
                Cookie::forever('biaoshi', $biaoshi);
               
            }

            $form['ua'] = $biaoshi;

        }
        #检测用户UA是否在当前代理下是否存在
        $userInfo = Users::where(['ua'=>$biaoshi,'uid'=>$this->uid])->find();
        if(empty($userInfo)){
            do{
                $pwd = rand_string(6);
                $res = Users::where(['pwd'=>$pwd,'uid'=>$this->uid])->find();
            }while($res);
            
            $userData = [
                'ua' =>$biaoshi,
                'uid'=>$this->uid,
                'ctime'=>time(),
                'pwd'=>$pwd,
                'jifen'=>10,
                'ip'=>getIp()
                ];
            $M = new Users();
            $res = $M->allowField(true)->save($userData);
        }
        #添加访问日志    

        $view = SpreadView::where(['ua' => $form['ua'], 'uid' => $this->uid])->whereTime('ctime', 'today')->find();
        if(empty($view)) 
        {
            $res = SpreadView::create([
                'ip' => getIp(),
                'uid' => $this->uid,
                'ctime' => time()
            ]);
        }
        
        $url = trim(getDomain(2, $this->uid)) . '/fvlist';
        $ldk = encrypt(json_encode($form));
        $this->assign('url',$url);
        $this->assign('ldk',$ldk);
        return $this->fetch();
       
    }

    //落地页
    public function lists()
    {
        #获取来路
        $refererUrl = parse_url($_SERVER['HTTP_REFERER']);
        $refererHost = $refererUrl['host'];
        #匹配来路
        $domainInfo = Domain::where(['type' => 4,'domain'=>$refererHost])->cache(300)->find();
        if(empty($domainInfo)){
            exit('Hello World');
        }
        $form = $this->form;

        $userObj = new Users();

        $userInfo = $userObj->where(['ua'=>$form['ua'],'uid'=>$this->uid])->field('id,pwd,pid,jifen') ->find();

        if(empty($userInfo)){
            $this->error('用户不存在');
        }

        $kouling = $userInfo->pwd;

        $qrcode_url = Admin::where('id',$this->uid)->cache(300)->value('qrcode_url');

        # 检测是否是推广用户
        if(isset($form['tgm'])){

            $pid = $userObj->where(['ua'=>$form['tgm'],'uid'=>$this->uid])->value('id');


            #是否存在上级ID
            if(empty($userInfo->pid) && $userInfo['id'] != $pid){
                $userObj->where(['id'=>$pid,'uid'=>$this->uid])->setInc('jifen',10);
                $userObj->where(['ua'=>$form['ua'],'uid'=>$this->uid])->update(['pid'=>$pid]);
            }

        }

        $form['tgm'] = $form['ua'];
        $tgmData = $form;
        unset($tgmData['ua']);
        #我的推广地址
        $url = getDomain(1,$this->uid) . '/fhaokan?ldk=' . encrypt(json_encode($tgmData));
        $tgm = $this->qrcode($url);
        unset($form['tgm']);
        $jifen = $userInfo->jifen;

        $this->assign([
            'kouling' =>$kouling,            
            'qrcode_url'=>$qrcode_url,
            'qrcode_title'=>'长按识别观看精彩内容',
            'tgm'=>$tgm,
            'jifen'=>$jifen
            ]);
        
        
        return $this->fetch('index');
    }

    /**
     * 视频播放
     * @return mixed
     */
    public function video()
    {        
        #获取表单数据   
        $form = $this->form;          
        #视频ID
        $vid = $this->request->param('vid',0,'intval');
        if(empty($vid)){
            $this->error("参数错误!", '', '', 200);
        }
        #获取视频资源   
        $linkInfo = Video::where('id', $vid)->find();
        if (empty($linkInfo)) {
            $this->error("视频资源丢失!", '', '', 200);
        }      
        #获取已支付视频          
        $map = [           
            'ua'=>$form['ua'],
            'uid'=>$form['uid']
        ];
        $payShowObj = new PayShow();

        $payShowInfo = $payShowObj->where($map)->where('etime', '>', time())->select()->toArray();
        
        if(empty($payShowInfo)){
            $map = [
                'ip'=>getIp(),
                'uid'=>$form['uid']
            ];
            $payShowInfo = $payShowObj->where($map)->where('etime', '>', time())->select()->toArray();
            if(empty($payShowInfo)) {
                $this->error("VIP已过期!");
            }
        }
        $is_day = 0;
        $is_week = 0;
        $is_month = 0;
        foreach ($payShowInfo as $k => $item) {
            
            if ($item['is_day'] == 1 && $item['etime'] > time()) {
                $is_day = 1;
                break;
            }
            if ($item['is_week'] == 1 && $item['etime'] > time()) {
                $is_week = 1;
                break;
            }
            if ($item['is_month'] == 1 && $item['etime'] > time()) {
                $is_month = 1;
                break;
            }
        }
        
        $pay_ids = array_column($payShowInfo, 'v_id');
        
        #查找单片ID

        $is_dp = in_array($vid,$pay_ids);    

        if($is_day != 1 && $is_week !=1 && $is_month != 1 && $is_dp !=1){
            $this->error("VIP已过期!");
        }           

        $hezi = [
            'status'=>true,
            'url'=>$linkInfo->video_url,
            'img'=>$linkInfo->img,
            'title'=>$linkInfo->title
        ];
        #获取用户推广信息
        $qrcode_url = Admin::where('id',$this->uid)->cache(300)->value('qrcode_url');
        
        $qrcode = [
            'url'=>$qrcode_url,
            'title'=>'长按识别观看精彩内容'
        ];
        $kouling = Users::where('ua',$form['ua'])->value('pwd');
        $this->assign([
            'hezi'=>$hezi,
            'qrcode'=>$qrcode,
            'kouling'=>$kouling
        ]);       
        unset($form['rukou']);
        unset($form['type']);
        $ldk = encrypt(json_encode($form));
        $this->assign('ldk',$ldk);
        return $this->fetch();
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
        //生达BASE64
        $dataUri = $qrCode->writeDataUri();
        return $dataUri;
        // 直接输入出二维码
        // echo $qrCode->writeString();
        // exit;
    }
     /**
     * 输出头像二维码
     */
    public function qrcodeFace($text = '')
    {
        $qrCode = new QrCode($text);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setWriterByName('png');
        header('Content-Type: ' . $qrCode->getContentType());
        #头像地址
        $qrCode->setLogoPath('./demo.jpg');
        $qrCode->setLogoWidth(90); 

        //生达BASE64
        $dataUri = $qrCode->writeDataUri();
        return $dataUri;
        // echo $qrCode->writeString();
        // exit;
    }


  
}