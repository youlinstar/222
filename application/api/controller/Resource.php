<?php


namespace app\api\controller;

// use app\common\controller\Common;
use app\common\model\PaySetting;
use app\common\model\Agent;
use app\common\model\Users;
use app\common\model\PayShow;
use app\common\model\Spread;
use app\common\model\VideoSort;
use think\Db;
use think\Exception;
use think\facade\Log;
use think\facade\Session;

class Resource extends Common
{

    public function initialize()
    {
        parent::initialize();
        $this->model = new Spread();
    }

    /**
     * 我的分类列表
     */
    public function getSort()
    {
        $limit = $this->request->param('limit/d', 30);
        $encode = $this->request->param('encode/d',0);
        $list = VideoSort::where('status', 1)->limit($limit)->order('indexid asc')->select();
        if($encode==1){
            $list = base64_encode(json_encode($list));
        }
        $this->success('success', $list);
    }

    /**
     * 我的推广资源列表
     */
    public function getList()
    {
        $from = $this->request->param('ldk');
        $user = $GLOBALS['user'];
        $domain = getDomain(2, $user->id);
        $pay_domain = getDomain(3, $user->id);
        $pay_ids = $this->getPayVideo();
        $where = [];
        // $where[] = ['spread.uid', '=', $user->id];
        $sortid = $this->request->param('cid/d', 0);
        $encode = $this->request->param('encode/d',0);
        if (!empty($sortid)) {
            $where[] = ['spread.sortid', '=', $sortid];
        }
        $key = $this->request->param('key/s', '', 'trim');
        if (!empty($key)) {
            $where[] = ['spread.title', 'like', '%' . $key . '%'];
        }
        $payed = $this->request->param('payed/d', 0);

        if ($payed && ($pay_ids['is_day'] == 0 && $pay_ids['is_week'] == 0 && $pay_ids['is_month'] == 0)) {
            $where[] = ['spread.id', 'in', $pay_ids['vid']];
        }
        $limit = $this->request->param('limit/d', 30);
        $page = $this->request->param('page/d', 1);
        $page = $page - 1;
        $pageSize = $page * $limit;
        $adminInfo = Agent::where('id',$user->id)->find();
       
        $list = $this->model->withJoin(['sort' => ['name']])
            ->where($where)
            ->order('sorts desc')
            ->order('ctime desc')
            ->limit($pageSize, $limit)
            ->select();
      
        $total = $this->model->withJoin(['sort' => ['name']])
            ->where($where)
            ->count();
        $data = [];  
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                if($v['sorts']>0){
                     $data[$k] = $v; 
                    unset($list[$k]);
                    $data[$k]['h'] = mt_rand(90, 99);
                    $data[$k]['rand'] = mt_rand(1212, 9083);
                    if (in_array($v['id'], $pay_ids['vid']) || ($pay_ids['is_day'] == 1 || $pay_ids['is_week'] == 1 || $pay_ids['is_month'] == 1)) {
                        $data[$k]['pay'] = 1;
                        $data[$k]['url'] = $domain . "/video?vid={$v['id']}&ldk={$from}";
                    } else {
                        if (!empty($pay_domain)) {
                            $domain = $pay_domain;
                        }
                        $data[$k]['pay'] = 0;
                        $data[$k]['url'] = $domain . "/play?vid={$v['id']}&ldk={$from}";
                    }
                     $data[$k]['money'] = $adminInfo->money;
                     $data[$k]['money1'] = $adminInfo->money1;
                     $data[$k]['money2'] = $adminInfo->money2;
                     $data[$k]['money3'] = $adminInfo->money3;
                }else{
                    $list[$k]['h'] = mt_rand(90, 99);
                    $list[$k]['rand'] = mt_rand(1212, 9083);
                    if (in_array($v['id'], $pay_ids['vid']) || ($pay_ids['is_day'] == 1 || $pay_ids['is_week'] == 1 || $pay_ids['is_month'] == 1)) {
                        $list[$k]['pay'] = 1;
                        $list[$k]['url'] = $domain . "/video?vid={$v['id']}&ldk={$from}";
                    } else {
                        if (!empty($pay_domain)) {
                            $domain = $pay_domain;
                        }
                        $list[$k]['pay'] = 0;
                        $list[$k]['url'] = $domain . "/play?vid={$v['id']}&ldk={$from}";
                    }
                     $list[$k]['money'] = $adminInfo->money;
                     $list[$k]['money1'] = $adminInfo->money1;
                     $list[$k]['money2'] = $adminInfo->money2;
                     $list[$k]['money3'] = $adminInfo->money3;
                }
                
            }
            shuffle($list);
            $list = array_merge($data,$list);
            
            if($encode==1){
                $list = base64_encode(json_encode($list));
            }
            
        }
        $this->success('success', ['list' => $list, 'total' => $total]);
    }
    
    
    /**
     * 我的积分资源列表
     */
    public function getJfList()
    {
        $from = $this->request->param('ldk');
        $user = $GLOBALS['user'];
        $domain = getDomain(2, $user->id);
        $pay_domain = getDomain(3, $user->id);
        $pay_ids = $this->getPayVideo();
        $where = [];
        // $where[] = ['spread.uid', '=', $user->id];
        $sortid = $this->request->param('cid/d', 0);
        $encode = $this->request->param('encode/d',0);
        if (!empty($sortid)) {
            $where[] = ['spread.sortid', '=', $sortid];
        }
        $key = $this->request->param('key/s', '', 'trim');
        if (!empty($key)) {
            $where[] = ['spread.title', 'like', '%' . $key . '%'];
        }
        $payed = $this->request->param('payed/d', 0);

        if ($payed && ($pay_ids['is_day'] == 0 && $pay_ids['is_week'] == 0 && $pay_ids['is_month'] == 0)) {
            $where[] = ['spread.id', 'in', $pay_ids['vid']];
        }
        $limit = $this->request->param('limit/d', 30);
        $page = $this->request->param('page/d', 1);
        $page = $page - 1;
        $pageSize = $page * $limit;
        $adminInfo = Agent::where('id',$user->id)->find();
       
        $list = $this->model->withJoin(['sort' => ['name']])
            ->where($where)
            ->order('sorts desc')
            ->order('ctime desc')
            ->limit($pageSize, $limit)
            ->select();
      
        $total = $this->model->withJoin(['sort' => ['name']])
            ->where($where)
            ->count();
        $data = [];  
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                //视频排序
                if($v['sorts']>0){
                     $data[$k] = $v; 
                    unset($list[$k]);
                    $data[$k]['h'] = mt_rand(90, 99);
                    $data[$k]['rand'] = mt_rand(1212, 9083);
                    if (in_array($v['id'], $pay_ids['vid']) || ($pay_ids['is_day'] == 1 || $pay_ids['is_week'] == 1 || $pay_ids['is_month'] == 1)) {
                        $data[$k]['pay'] = 1;
                        $data[$k]['url'] = $domain . "/fvideo?vid={$v['id']}&ldk={$from}";
                    } else {
                        if (!empty($pay_domain)) {
                            $domain = $pay_domain;
                        }
                        $data[$k]['pay'] = 0;
                        $data[$k]['url'] = $domain . "/play?vid={$v['id']}&ldk={$from}";
                    }
                     $data[$k]['money'] = $adminInfo->money;
                     $data[$k]['money1'] = $adminInfo->money1;
                     $data[$k]['money2'] = $adminInfo->money2;
                     $data[$k]['money3'] = $adminInfo->money3;
                }else{
                    $list[$k]['h'] = mt_rand(90, 99);
                    $list[$k]['rand'] = mt_rand(1212, 9083);
                    if (in_array($v['id'], $pay_ids['vid']) || ($pay_ids['is_day'] == 1 || $pay_ids['is_week'] == 1 || $pay_ids['is_month'] == 1)) {
                        $list[$k]['pay'] = 1;
                        $list[$k]['url'] = $domain . "/fvideo?vid={$v['id']}&ldk={$from}";
                    } else {
                        if (!empty($pay_domain)) {
                            $domain = $pay_domain;
                        }
                        $list[$k]['pay'] = 0;
                        $list[$k]['url'] = $domain . "/play?vid={$v['id']}&ldk={$from}";
                    }
                     $list[$k]['money'] = $adminInfo->money;
                     $list[$k]['money1'] = $adminInfo->money1;
                     $list[$k]['money2'] = $adminInfo->money2;
                     $list[$k]['money3'] = $adminInfo->money3;
                }
                
            }
            shuffle($list);
            $list = array_merge($data,$list);
            
            if($encode==1){
                $list = base64_encode(json_encode($list));
            }
            
        }
        $uabs = userAgent();
        $userObj = Users::where(['uabs'=>$uabs,'uid'=>$user->id])->find();
        if(empty($userObj)){
            $jf = 0;
        }else{
            $jf = $userObj->jifen;
        }
        $this->success('success', ['list' => $list, 'total' => $total,'jf'=>$jf]);
    }
    
    
    
    
    
     /**
     * 我的直播列表
     */
    public function getZbList()
    { 
    
        $list = \app\common\model\Zhibo::
            order('ctime desc')
            ->find();
          
        $list = $list->toArray();
       
        return json(['code'=>1,'url'=>$list['link']]);
        
    }

    /**
     * 播放影片
     */
    public function play()
    {
        $from = $this->request->param('ldk');
        $video_id = $this->request->param('vid/d', 0);
        $money = $this->request->param('money/f', 0);
        $user = $GLOBALS['user'];
        #落地域名
        $land_domain = getDomain(2, $user->id);
        #支付域名
        $pay_domain = getDomain(3, $user->id);
        if ($pay_domain) {
            $land_domain = $pay_domain;
        }
        #查询资源价格
        $spread = $this->model->where('id', $video_id)->find();
        if (empty($spread)) {
            $this->error('资源已经下架了');
        }
        //改为调用统一设置用户的价格
        $one_money = $user->money;
        $day_money = $user->money1;
        $week_money = $user->money2;
        $month_money = $user->money3;
        $is_day = $user->is_day;
        $is_week = $user->is_week;
        $is_month = $user->is_month;
      
        #查询支付通道
        if ($user->pay_id == 0) {
            $user->pay_id = 15;
        }
        $pay_list = [];
        $pay_list = [
            [
                'name' => "单片购买 {$one_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=1&vid={$video_id}",
                'flg' => 'dan_fee',
                'money' => $one_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]
        ];
        if ($day_money > 0 && $is_day == 1) {
            array_push($pay_list, [
                'name' => "包日观看全部 {$day_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=2&vid={$video_id}",
                'flg' => 'day_fee',
                'money' => $day_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]);
        }
        if ($week_money > 0 && $is_week == 1) {
            array_push($pay_list, [
                'name' => "包周观看全部 {$week_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=3&vid={$video_id}",
                'flg' => 'day_fee',
                'money' => $week_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]);
        }
        if ($month_money > 0 && $is_month == 1) {
            array_push($pay_list, [
                'name' => "包月观看全部 {$month_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=4&vid={$video_id}",
                'flg' => 'month_fee',
                'money' => $month_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]);
        }
        
        $this->success('success', $pay_list);
    }
    /**
     * 积分播放影片
     */
    public function jfplay()
    {
        $from = $this->request->param('ldk');
        $video_id = $this->request->param('vid/d', 0);
        $money = $this->request->param('money/f', 0);
        $is_jf = $this->request->param('isjf/d', 0);
        $user = $GLOBALS['user'];
        #落地域名
        $land_domain = getDomain(2, $user->id);
        #支付域名
        $pay_domain = getDomain(3, $user->id);
        if ($pay_domain) {
            $land_domain = $pay_domain;
        }
        #查询资源价格
        $spread = $this->model->where('id', $video_id)->find();
        if (empty($spread)) {
            $this->error('资源已经下架了');
        }
        //改为调用统一设置用户的价格
        $one_money = $user->money;
        $day_money = $user->money1;
        $week_money = $user->money2;
        $month_money = $user->money3;
        $is_day = $user->is_day;
        $is_week = $user->is_week;
        $is_month = $user->is_month;
        $jf = $user->jf;
        #查询支付通道
        if ($user->pay_id == 0) {
            $user->pay_id = 15;
        }
        $pay_list = [];
        $pay_list = [
            [
                'name' => "单片购买 {$one_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=1&vid={$video_id}&isjf={$is_jf}",
                'flg' => 'dan_fee',
                'money' => $one_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]
        ];
        if ($day_money > 0 && $is_day == 1) {
            array_push($pay_list, [
                'name' => "包日观看全部 {$day_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=2&vid={$video_id}&isjf={$is_jf}",
                'flg' => 'day_fee',
                'money' => $day_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]);
        }
        if ($week_money > 0 && $is_week == 1) {
            array_push($pay_list, [
                'name' => "包周观看全部 {$week_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=3&vid={$video_id}&isjf={$is_jf}",
                'flg' => 'day_fee',
                'money' => $week_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]);
        }
        if ($month_money > 0 && $is_month == 1) {
            array_push($pay_list, [
                'name' => "包月观看全部 {$month_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=4&vid={$video_id}&isjf={$is_jf}",
                'flg' => 'month_fee',
                'money' => $month_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]);
        }
        $pay_list[] = [
             'name' => "单片购买{$jf}积分",
              'url' => "$land_domain/jfplay?ldk={$from}&jf={$jf}&vid={$video_id}&uid={$user->id}&isjf={$is_jf}",
              'flg' => 'jf',
              'money' => $jf,
              'img' => "/default/img/vipicon.png"  //图标地址
            ];
        $this->success('success', $pay_list);
    }
    /**
     * 获取已经支付的视频ID
     */
    public function getPayVideo()
    {
        $ldk = json_decode(decrypt($this->request->param('ldk')),true);
        $ua = $ldk['ua'];
        $user = $GLOBALS['user'];
        $is_day = 0;
        $is_week = 0;
        $is_month = 0;
        $pay_ids = [];
        $pay = (new PayShow())->where('etime', '>', time())->where('uid', $user->id)->where('ua',$ua)->select()->toArray();
        
        foreach ($pay as $k => $item) {
            
            if ($item['is_day'] == 1 && $item['etime'] > time()) {
                $is_day = 1;
            }
            if ($item['is_week'] == 1 && $item['etime'] > time()) {
                $is_week = 1;
            }
            if ($item['is_month'] == 1 && $item['etime'] > time()) {
                $is_month = 1;
            }
        }
        
        if (!empty($pay)) {
            $pay_ids = array_column($pay, 'v_id');
        }
        return ['vid' => $pay_ids, 'is_day' => $is_day, 'is_week' => $is_week, 'is_month' => $is_month];
    }
  
    public function getVideolist(){
        $type = $this->request->param('type/s',1);
        $list = Db::name('zhibo')->where('sortid',$type)->orderRand()->limit(50)->column('link');
        if($list){
            $url = $list[array_rand($list)];
            return json(['code'=>200,'url'=>base64_encode(json_encode($url))]);
        }
        
    }
    public function getStatus(){
       
        $res = Db::name('zhibo')->where(['status'=>1,'sortid'=>2])->find();
        if($res){
            return json(['code'=>200,'status'=>1]);
        }else{
            return json(['code'=>0,'status'=>0]);
        }
        
    }
    public function getOrderStatus(){
        $count = Db::name('setting')->where('skey', 'orderTime')->value('value');
        $count = (int) $count;
        $now = time();
        $ltime = $now - $count*60;
        $res = Db::name('order')->where('ctime', 'between time', [$ltime, $now])->find();
        
        if($res){
            
            return json(['code'=>200]);
        }else{
            return json(['code'=>0]);
        }
        
    }
    public function login(){
        
        if($this->request->isPost()){
            
            $card = $this->request->param('pwd','','trim');
            if(empty($card)){
                return json(['code'=>0,'msg'=>'参数不正确']);
            }
            $user = $GLOBALS['user'];
            $payModel = new PayShow();
            
            $payObj = $payModel->where('uid', $user->id)->where('card',$card)->find();
            $user = $GLOBALS['user'];
            if($payObj){
                if($payObj->etime < time()){
                    return json(['code'=>0,'msg'=>'会员已到期！']);
                }
                Session::set('card',$card);
                return json(['code'=>200,'msg'=>'登录成功']);
            } 
            return json(['code'=>0,'msg'=>'会员不存在']);
        }
        
        return json(['code'=>0,'非法提交!']);
    }
}