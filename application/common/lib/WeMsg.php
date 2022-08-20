<?php


namespace app\common\lib;
use app\common\model\WxUser;
use think\Controller;

class WeMsg extends Controller
{
    protected $app_id;
    protected $app_secret;
    protected $get_token_url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';#获取access_token
    protected $temp_send_url='https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=%s';#订阅消息发送模板
    protected $temp_list_url='https://api.weixin.qq.com/cgi-bin/wxopen/template/list';#获取模板列表
    protected $model=null;
    protected $wechat=null;
    public function __construct()
    {
        parent::__construct();
        $this->model=new WxUser();
        $this->wechat=$this->model->get(2);
        $this->app_id=$this->wechat->appid;
        $this->app_secret=$this->wechat->appsecret;
    }

    /**
     * 获取access_token
     */
    public function getToken(){
        if($this->wechat->access_expires<time()){
            $result=httpRequest(sprintf($this->get_token_url,$this->app_id,$this->app_secret),'get');
            $result=json_decode($result,true);
            if(isset($result['errcode'])){
                return $result['errmsg'];
            }
            $this->wechat->access_token=$result['access_token'];
            $this->wechat->access_expires=time()+$result['expires_in'];
            $this->wechat->utime=time();
            $this->wechat->save();
        }
        return $this->wechat->access_token;
    }
    /**
     * 发送模板消息
     */
    public function sendTempletMsg($type,$param){
        $params=$this->getParam($type,$param);
        $result=httpRequest(sprintf($this->temp_send_url,$this->getToken()),'post',json_encode($params));
        $result=json_decode($result,true);
        if($result['errcode']!==0){
            return [false,$result['errmsg']];
        }
        return [true,'success'];
    }

    protected function getParam($type,$param){
        switch($type){
            case 1:#订单审核模板
                $vlaues=[
                    'character_string1'=>['value'=>$param['ordno']],//订单号
                    'date2'=>['value'=>$param['ctime']],//提交时间
                    'phrase3'=>['value'=>$param['msg']],//审核结果
                    'date4'=>['value'=>$param['utime']],//审核时间
                    'thing5'=>['value'=>$param['remark']],//拒绝理由
                ];
                $template_id=config('setting.order_tempid');
                break;
            case 2:#认证通知模板
                $vlaues=[
                    'thing2'=>['value'=>$param['title']],
                    'date4'=>['value'=>$param['ctime']],
                    'phrase1'=>['value'=>$param['msg']],
                    'date3'=>['value'=>$param['utime']],
                    'thing5'=>['value'=>$param['remark']],
                ];
                $template_id=config('setting.rzbiz_tempid');
                break;
            case 3:#设备审核通知模板
                $vlaues=[
                    'thing2'=>['value'=>$param['title']],
                    'date4'=>['value'=>$param['ctime']],
                    'phrase1'=>['value'=>$param['msg']],
                    'date3'=>['value'=>$param['utime']],
                    'thing5'=>['value'=>$param['remark']],
                ];
                $template_id=config('setting.pay_tempid');
                break;

        }
        $data=[
            'touser'=>$param['openid'],//'oRr0R5XdIBGtdf6ZWR2kW1Tbz4c4',
            'template_id'=>$template_id,
            'page'=>$param['page'],
            'data'=>$vlaues
        ];
        return $data;
    }

    /**
     * 获取模板列表
     */
    public function getTempletList(){
        $params=[
            'access_token'=>$this->getToken(),
            'offset'=>2,
            'count'=>1
        ];
        $result=httpRequest($this->temp_list_url,'post',$params);
        var_dump($result);
    }
}