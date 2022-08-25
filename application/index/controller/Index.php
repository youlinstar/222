<?php


namespace app\index\controller;

use app\common\controller\Common;
use app\common\model\Admin;
use app\common\model\Users;
use app\common\model\Hezi;
use app\common\model\PayShow;
use app\common\model\Domain;
use app\common\model\Video;
use app\common\model\SpreadView;
use think\facade\Cookie;
use think\facade\Cache;


class Index extends Common
{
    
    public function index()
    {   
        $user = Admin::get($this->uid);
        if (empty($user) || $user->status != 1) {
            $jump = 'https://news.qq.com';
            header("location:{$jump}");
            exit;
        }
        
        $domain = trim(getDomain(4, $this->uid)) . '/token';
        $token = $this->request->param('token','','trim');

        $list = [
            'ldk'=>$this->ldk,
            'token'=>$token
        ];
        $this->assign(['url'=>$domain,'list'=>$list]);
        return $this->fetch('/common/jump');
        
    }

    /**用户标识UA
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function token(){
        
        #获取来路
        $refererUrl = parse_url($_SERVER['HTTP_REFERER']);
        $refererHost = $refererUrl['host'];
        #匹配来路
        $domainInfo = Domain::where(['type' => 1,'domain'=>$refererHost])->cache(300)->find();
        if(empty($domainInfo)){
            exit('Hello World');
        }
        #获取form
        $form = $this->form;
        $token = $this->request->param('token');

        if(isset($form['ua']) && !empty($token)){
//            验证token
            $chack = Cache::pull($token);

            if(empty($chack)){
                $this->error('口令失效,请重新登录!');
            }
            //验证用户的ua是不是提交过来的ua
            if($chack['old_ua'] != $form['ua']){
                $this->error('非法用户!');
            }
//            更新新的UA
            Cookie::forever('biaoshi', $chack['new_ua']);
            $biaoshi = $chack['new_ua'];

        }else{
            $biaoshi = Cookie::get('biaoshi');
            #没有标识 生成标识  并添加数据库
            if(!isset($biaoshi) || empty($biaoshi)){
                $biaoshi = md5(time().substr(microtime(),2,6) . userAgent());              
                
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
                'ip' => $this->request->ip(),
                'uid' => $this->uid,
                'ctime' => time()
            ]);
        }
        
        $url = trim(getDomain(2, $this->uid)) . '/vlist';
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
        #解密
        $form = $this->form;      
       
        $heziUrl = Hezi::where(['id' => $form['t'], 'status' => 1 ,'is_hz'=>1])->cache(3600)->value('video_url');       
        
        $hezi = [
            'status'=>false,
            'url'=>$heziUrl,
        ];

        if($heziUrl){
            
            $hezi['status'] = true;
        }        
        
        $kouling = Users::where('ua',$form['ua'])->cache(3600)->value('pwd'); 
        
        $qrcode_url = Admin::where('id',$this->uid)->cache(300)->value('qrcode_url');
        
        $this->assign([
            'kouling' =>$kouling,
            'hezi'=>$hezi,            
            'qrcode_url'=>$qrcode_url,
            'qrcode_title'=>'长按识别观看精彩内容'
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
        
        // if(empty($payShowInfo)){
        //     $this->error("VIP已过期!");
        // } 
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
       
        // if($is_day != 1 && $is_week !=1 && $is_month != 1 && $is_dp !=1){
        //     $this->error("VIP已过期!");
        // }           

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
  
}