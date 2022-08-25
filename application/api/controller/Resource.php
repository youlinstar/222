<?php

namespace app\api\controller;

use app\common\model\Agent;
use app\common\model\Users;
use app\common\model\PayShow;
use app\common\model\Video;
use app\common\model\VideoSort;
use think\Db;
use think\Exception;
use think\facade\Log;
use think\facade\Session;
use think\Request;
use think\facade\Cache;

class Resource extends Common
{

    public function initialize()
    {
        parent::initialize();
        $this->model = new Video();
    }

    /**视频分类
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSort()
    {                
        $list = VideoSort::where('status', 1)->order('indexid asc')->cache(300)->select();
        
        $list = base64_encode(json_encode($list));
        
        $this->success('success', $list);
    }

    /**获取资源列表
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList()
    {
        $from = $this->ldk;
        #入口Type
        $rkType = $this->request->param('rkType/d', 0);
        if(empty($rkType)){
            $this->error('参数错误');
        }
        switch($rkType)
        {
            case 1:
                $rukou = 'video';
                break;
            case 2:
                $rukou = 'fvideo';
                break;
        }
        $domain = getDomain(2, $this->uid);
        $pay_domain = getDomain(3, $this->uid);
        $pay_ids = $this->getPayVideo();
        $where = [];
        $sortid = $this->request->param('cid/d', 0);
        $encode = $this->request->param('encode/d',0);
        if (!empty($sortid)) {
            $where[] = ['sortid', '=', $sortid];
        }
        $key = $this->request->param('key/s', '', 'trim');
        if (!empty($key)) {
            $where[] = ['title', 'like', '%' . $key . '%'];
        }
        #查询已购
        $payed = $this->request->param('payed/d', 0);
        if ($payed && ($pay_ids['is_day'] == 0 && $pay_ids['is_week'] == 0 && $pay_ids['is_month'] == 0)) {
            $where[] = ['id', 'in', $pay_ids['vid']];
        }
        $limit = $this->request->param('limit/d', 30);
        $page = $this->request->param('page/d', 1);
        $page = $page - 1;
        $pageSize = $page * $limit;
        $agentInfo = Agent::where('id',$this->uid)->field('money,money1,money2,money3')->find();
        if(empty($agentInfo)){
            $this->error('获取失败',null,500);
        }
        $list = $this->model->where($where)
                ->order('sorts desc')
                ->order('ctime desc')
                ->orderRand()
                ->limit($pageSize, $limit)
                ->field('id,img,title,times,sorts,video_url')
                ->select();
        $total = $this->model->where($where)->count();

        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['rand'] = mt_rand(1212, 9083);
                if (in_array($v['id'], $pay_ids['vid']) || ($pay_ids['is_day'] == 1 || $pay_ids['is_week'] == 1 || $pay_ids['is_month'] == 1)) {
                    $list[$k]['pay'] = 1;
                    $list[$k]['url'] = $domain . "/{$rukou}?vid={$v['id']}&ldk={$from}";
                } else {
                    if (!empty($pay_domain)) {
                        $domain = $pay_domain;
                    }
                    $list[$k]['pay'] = 0;

                }
                $list[$k]['money'] = $agentInfo->money;
            }
            
            if($encode==1){
                $list = base64_encode(json_encode($list));
            }
            
        }
        $this->success('success', ['list' => $list, 'total' => $total]);
    }

    /**
     * 获取视频支付地址
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function play()
    {
        $from = $this->ldk;
        #视频ID
        $video_id = $this->request->param('vid/d', 0);        
        #入口Type
        $rkType = $this->request->param('rkType/d', 0);
        #落地域名
        $land_domain = getDomain(2, $this->uid);
        #支付域名
        $pay_domain = getDomain(3, $this->uid);
        if ($pay_domain) {
            $land_domain = $pay_domain;
        }
        #查询资源价格
        $spread = $this->model->where('id', $video_id)->find();
        if (empty($spread)) {
            $this->error('资源已经下架了');
        }
        $agentInfo = Agent::where('id',$this->uid)->field('money,money1,money2,money3,jf,is_day,is_week,is_month,pay_id')->find();

        //改为调用统一设置用户的价格
        $one_money = $agentInfo->money;
        $day_money = $agentInfo->money1;
        $week_money = $agentInfo->money2;
        $month_money = $agentInfo->money3;
        $is_day = $agentInfo->is_day;
        $is_week = $agentInfo->is_week;
        $is_month = $agentInfo->is_month;
        
        #查询支付通道
        if ($agentInfo->pay_id == 0) {
            $agentInfo->pay_id = 15;
        }
        $pay_list = [
            [
                'name' => "单片购买 {$one_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=1&vid={$video_id}&rkType={$rkType}",
                'flg' => 'dan_fee',
                'money' => $one_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]
        ];
        if ($day_money > 0 && $is_day == 1) {
            array_push($pay_list, [
                'name' => "包日观看全部 {$day_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=2&vid={$video_id}&rkType={$rkType}",
                'flg' => 'day_fee',
                'money' => $day_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]);
        }
        if ($week_money > 0 && $is_week == 1) {
            array_push($pay_list, [
                'name' => "包周观看全部 {$week_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=3&vid={$video_id}&rkType={$rkType}",
                'flg' => 'day_fee',
                'money' => $week_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]);
        }
        if ($month_money > 0 && $is_month == 1) {
            array_push($pay_list, [
                'name' => "包月观看全部 {$month_money} 元",
                'url' => "$land_domain/play?ldk={$from}&is_type=4&vid={$video_id}&rkType={$rkType}",
                'flg' => 'month_fee',
                'money' => $month_money,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]);
        }     

        if($rkType == 2){

            if(empty($agentInfo->jf)){
                $jf = 10;
            }else{
                $jf = $agentInfo->jf;
            }
            array_push($pay_list, [
                'name' => "{$jf}积分免费观看",
                'url' => "$land_domain/jfplay?ldk={$from}&vid={$video_id}&uid={$this->uid}",
                'flg' => 'jf',
                'money' => $jf,
                'img' => "/default/img/vipicon.png"  //图标地址
            ]);
        }
        
        $this->success('success', $pay_list);
    }

    /**
     * 获取已支付视频
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPayVideo()
    {

        $is_day = 0;
        $is_week = 0;
        $is_month = 0;
        $pay_ids = [];
        
        $pay = (new PayShow())->where('etime', '>', time())
                ->where('uid', $this->uid)
                ->where('ua',$this->form['ua'])
                ->field('v_id,etime,is_day,is_week,is_month')
                ->select()
                ->toArray();

        foreach ($pay as $k => $item) {
            
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
        
        if (!empty($pay)) {
            $pay_ids = array_column($pay, 'v_id');
        }
        return ['vid' => $pay_ids, 'is_day' => $is_day, 'is_week' => $is_week, 'is_month' => $is_month];
    }

    /**
     * 用户登录
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login(){
        
        if($this->request->isPost()){
            
            $pwd = $this->request->param('pwd','','trim');
            $type = $this->request->param('type',0,'intval');
            
            if(empty($pwd) || empty($type) || empty($this->ldk)){
                return json(['code'=>0,'msg'=>'参数不正确']);
            }
            
            switch ($type) {
                case '1':
                    $rukou = 'haokan';
                    break;
                case '2':
                    $rukou = 'fhaokan';
                    break;
                case '3':
                    $rukou = 'zhibo';
                    break;
                default:
                    // code...
                    break;
            }
            
            #通过密码查找当前代理下用户ua
            $ua = Users::where(['pwd'=>$pwd,'uid'=>$this->uid])->value('ua');
            
            if(empty($ua))
            {
                 return json(['code'=>0,'msg'=>'口令错误']);
            }

            $token = md5(encrypt(json_encode($this->form))).time();
//            旧ua与新ua
            $data = ['old_ua'=>$this->form['ua'],'new_ua'=>$ua];
            Cache::set($token,$data,60);
            #请求url
            $domain = trim(getDomain(1, $this->uid)) . '/' . $rukou . '?ldk=' . $this->ldk . '&token=' . $token;

            return json(['code'=>200,'msg'=>'登录成功','url'=>$domain]);
            
           
        }
        
        return json(['code'=>0,'非法提交!']);
    }

    /**
     * 检测用户积分
     * @return \think\response\Json
     */
    public function checkJfPay()
    {
        $from = $this->form;
        
        $money = Users::where(['ua'=>$from['ua'],'uid'=>$from['uid']])->value('jifen');

        if(empty($money)){
            return json(['code'=>0,'msg'=>'用户积分不足']);
        }

        $payMoney = Agent::where(['id'=>$from['uid']])->value('jf');

        if(empty($money)){
            $payMoney = 10;
        }

        if($money < $payMoney){
            return json(['code'=>0,'msg'=>'用户积分不足']);
        }

        return json(['code'=>200,'msg'=>'获取成功']);

    }

    /**获取入口地址
     * @return \think\response\Json
     */
    public function getDomain()
    {
        $domain = getDomain(1,$this->uid);
        if(empty($domain)){
            $this->error('入口不存在');
        }

        return json(['code'=>200,'msg'=>'获取成功','url'=>$domain]);

    }

    /**
     * 我的直播列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getZbList()
    {

        $list = \app\common\model\Zhibo::order('ctime desc')->find();

        $list = $list->toArray();

        return json(['code'=>1,'url'=>$list['link']]);

    }
    /**直播地址
     * @return \think\response\Json|void
     */
    public function getVideolist(){
        $type = $this->request->param('type/s',1);
        $list = Db::name('zhibo')->where('sortid',$type)->orderRand()->limit(50)->column('link');
        if($list){
            $url = $list[array_rand($list)];
            return json(['code'=>200,'url'=>base64_encode(json_encode($url))]);
        }

    }

    /**直播状态
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStatus(){

        $res = Db::name('zhibo')->where(['status'=>1,'sortid'=>2])->find();
        if($res){
            return json(['code'=>200,'status'=>1]);
        }else{
            return json(['code'=>0,'status'=>0]);
        }

    }

}