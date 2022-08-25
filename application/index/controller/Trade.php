<?php


namespace app\index\controller;

use app\common\controller\Common;
use app\common\model\Admin;
use app\common\model\Users;
use app\common\model\Agent;
use app\common\model\PayShow;
use app\common\model\PaySetting;
use app\common\model\Spread;
use app\common\model\VideoSort;
use app\common\model\WxPay;
use think\Exception;

class Trade extends Common
{
    
    
    public function jfplay(){
      
       $from = $this->request->param('ldk');
       $ldk = json_decode(decrypt($from), true);
       $uabs = $ldk['ua'];
       $userModel = new Users();
       $users = $userModel->where(['uabs'=>$uabs,'uid'=>$this->uid])->find();
       
       $jf = Admin::where('id',$this->uid)->value("jf");
       if(empty($jf) || empty($users)){
           $this->error("用户不存在");
       }
       if($jf>$users->jifen){
           $this->error("积分不足");
       }
       $users->setDec('jifen',$jf);
       $ordno = date("YmdHis") . rand(1000000, 9999999);
       $v_id = $this->request->param('vid/d',0);
       $etime = time() + 86400;
       $res = (new PayShow())->save([
                'v_id' =>$v_id,
                'uid' => $this->uid,
                'ip' => getIp(),
                'ordno' => $ordno,
                'ua' => $uabs,
                'etime' =>$etime,
                'is_month' => 0,
                'is_week' => 0,
                'is_day' => 0,
                'is_jf'=>1,
                'ctime' => time()
            ]);
        if ($res) {
            $url = "/fvideo?vid={$v_id}&ldk={$from}";
            header("Location:".$url);
            exit;
        }else{
            $this->error("积分支付失败");
        }
    }
    
    //入口
    public function index()
    {
        #支付选择类型
        $type = $this->request->param('type','');
        #支付ID
        $pay_id = $this->request->param('pay_id/d', 0);
       
        if (empty($pay_id)) {
            $user = $GLOBALS['user'];
            if ($user->pay_id == 0) {
                #默认支付渠道--云尔易付
                $pay_id = 28;
            }else{
                $pay_id = $user->pay_id;
            }
        }
       
        if(!empty($type)){
            
            $payInfo = PaySetting::where(['pay_channel'=>$type,'status'=>1])->find();
        }else{
            
            $payInfo = PaySetting::where(['id'=>$pay_id,'status'=>1])->find();
        }
      
        if(empty($payInfo)){
            $this->error("没有可用的支付渠道,请确认");
        }
        switch ($payInfo->label) {
            case 'MZFWX':#码支付（支付宝）
                $this->codePay($payInfo, $user, $payInfo->id);
                 break;
            case 'ZFNWX':#码支付（微信）
                $this->zfnPay($payInfo, $user, $payInfo->id);
                break;
            case 'ZCMPAY':#码支付(招财猫)
                $this->zcmPay($payInfo, $user, $payInfo->id);
                break;
            case 'YRYF':#云尔易付
                $this->yuanerPay($payInfo, $user,$payInfo->id);
                break;
            case 'XSSWX':#嘻唰唰
                $this->xssPay($payInfo, $user, $payInfo->id);
                break;
            case 'WECHAT':#微信官方
                $this->wechatPay($payInfo, $user, $payInfo->id);
                break;
            case 'WECHATJS':#微信官方JS
                $this->wechatjsPay($payInfo, $user, $payInfo->id);
                break;
            case 'WWXPAY':#码支付(未知)
                $this->wwxPay($payInfo, $user, $payInfo->id);
                break;
            case 'QTWPAY':#码支付(招财猫)
                $this->qtwPay($payInfo, $user, $payInfo->id);
                break;
            case 'XHYPAY':#星火易支付
                $this->xhyPay($payInfo, $user, $payInfo->id);
                break;
            case 'GBPAY':#冈本支付
                $this->gbPay($payInfo, $user, $payInfo->id);
                
                break;
            case 'DBLPAY':#大波浪
                $this->dblPay($payInfo, $user, $payInfo->id);
                break;
            case 'YCPAY':#永昌
                return $this->ycPay($payInfo, $user, $payInfo->id);
                break;
            case 'YDEPAY':#约德尔
                $this->ydePay($payInfo, $user, $payInfo->id);
                break;
            case 'BHPAY':#百合支付
                $this->bhPay($payInfo, $user, $payInfo->id);
                break;
            case 'SYPAY':#SY支付
                $this->syPay($payInfo, $user, $payInfo->id);
                break;
            case 'MHYPAY':#马化云支付
                return $this->mhyPay($payInfo, $user, $payInfo->id);
                break;
            case 'WMPAY':#无名支付
                $this->wmPay($payInfo, $user, $payInfo->id);
                break;
            case 'LYBPAY':#618支付
                $this->lybPay($payInfo, $user, $payInfo->id);
                break;
            case 'XJPAY':#小鸡支付
                $this->xjPAY($payInfo, $user, $payInfo->id);
                break;
            case 'TMIAOPAY':#天淼支付
                $this->tmiaoPAY($payInfo, $user, $payInfo->id);
                break;
            default:
                $this->error("未匹配到{$payInfo->label}支付渠道,请确认");
                break;
        }
    }
    /**
     * 天淼支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
    protected function tmiaoPAY($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "tmiaoPay");
        $data = [
            'out_trade_no'=>$ordno,
            'mchid'=>$appId,
            'total_fee'=>$payMoney*100,
            'notify_url'=>$payNotifyUrl,
            'callback_url'=>$payCallBackUrl,
            'error_url'=>$payCallBackUrl
        ];
        ksort($data); 
        reset($data); 
        $fieldString = [];
        foreach ($data as $key => $value) {
            if(!empty($value)){
                $fieldString [] = $key . "=" . $value . "";
             }
        }
        $fieldString = implode('&', $fieldString);
        $data['sign'] = strtoupper(md5($fieldString.$appKey));
        $payGateWayUrl = $payGateWayUrl.'/toPay';
        $res = httpRequest($payGateWayUrl,'POST',$data);
        $res = json_decode($res,true);
        // array(3) { ["code"]=> int(0) ["msg"]=> string(0) "" ["data"]=> array(2) { ["orderId"]=> string(32) "2c918084812cc88801812dd36e1c48f0" ["payUrl"]=> string(108) "http://w.kyq8.cn.w.kunlunea.com/2c918084812cc88801812dd36e1c48f0/d24b4f59adb2c524a320a653c7f9e496/711gz.html" } }
       
        if($res['code']==0){
            $url =$res['data']['payUrl'];
            echo("<script language='javascript'>window.top.location.href='{$url}'</script>");exit;
          
        }else{
            $this->error($res['msg']);
        }
       
    }
    /**
     * 小鸡支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
    protected function xjPAY($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "xjPAY");
        $data = [
            "notice_url" => $payNotifyUrl,  //支付通知地址
            "notify" => $payCallBackUrl,    //支付后跳转地址
            "randStr" => $ordno,
            "order" => $ordno,
            "body" => $ordno,
            "key" => $appId,
            "fee" => $payMoney * 100
        ];
        ksort($data);
        //签名
       
        $str = http_build_query($data);
        $old_str = urldecode($str) . '&cert=' . $appKey;
        $data['sign'] = strtoupper(md5($old_str));
        
        $ch = curl_init();
        //设置要请求的地址
        curl_setopt($ch, CURLOPT_URL, $payGateWayUrl);
        //设置请求头信息
        //设置请求体
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //设置
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        //发送请求
        $res = curl_exec($ch);
       
        curl_close($ch);
        $result = json_decode($res, 1);
       
        if($result['code'] != 'success'){
             //记录下单失败
            die($result['msg']);
        }
        $url = $result['data']['payUrl'];
        echo("<script language='javascript'>window.top.location.href='{$url}'</script>");exit;
        // header("Content-Type:text/html ; charset=utf-8");
        // header("HTTP/1.1 302 Moved Permanently");
        // header("Location:".$result['data']['payUrl']);
        // exit;
       
    }
    /**
     * 618支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
    protected function lybPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        
        $app_secret = $payInfo->app_secret;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "lybPay");
        $time = time();
        // $data = [
        //     'api_key'=>$appId,
        //     'sign_type'=>'RSA2',
        //     'req_str'=>'VIP会员',//随机串
        //     'timestamp'=>"$time",
        //     'pay_type'=>4,//1⽀付宝2微信3银联4其他
        //     'out_trade_no'=>$ordno,
        //     'notify_url'=>$payNotifyUrl,
        //     'return_url'=>$payCallBackUrl,
        //     'money'=>$payMoney
        //     ];
        $data = [
            'api_key'=>$appId,
            'sign_type'=>'MD5',
            'req_str'=>'VIP会员',//随机串
            'timestamp'=>"$time",
            'pay_type'=>2,//1⽀付宝2微信3银联4其他
            'out_trade_no'=>$ordno,
            'notify_url'=>$payNotifyUrl,
            'return_url'=>$payCallBackUrl,
            'money'=>$payMoney
            ];
        ksort($data); 
        $fieldString = [];
        foreach ($data as $key => $value) {
            if(!empty($value)){
                $fieldString [] = $key . "=" . $value . "";
             }
        }
        
        $fieldString = implode('&', $fieldString);
        $data['sign'] = md5(mb_strtoupper($fieldString . '&secret=caee5d70d0e5bef3a763060b34236418'));
        $headers = [
            'User-Agent: test/1.0',
            'Content-Type: application/json;charset=utf-8'
            ];
        // $datastr = mb_strtoupper($this->joinMapValue($data));
        
        // $data['sign'] = $this->lyb_sign($appKey,$datastr,'RSA2');
        
        $payGateWayUrl = $payGateWayUrl . '/openapi/v1/order';
        $data = json_encode($data);
        $ch = curl_init($payGateWayUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response,true);
        if($response['code'] !=0){
            $this->error($response['msg']);
        }else{
            $url =$response['data']['url'];
            echo("<script language='javascript'>window.top.location.href='{$url}'</script>");exit;
           
        }
        
       
       
    }
    private function joinMapValue($sign_params)
    {
        ksort($sign_params);
        $sign_str = "";
        foreach ($sign_params as $key => $val) {
            if (!empty($val)) {
                $sign_str .= sprintf("%s=%s&", $key, $val);
            }
        }
        return substr($sign_str, 0, -1);
    }
    public function lyb_sign($privateKey, $data, $signType = "RSA2")
    {
        
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        if (!openssl_pkey_get_private($res)) {
            throw new \Exception('您使用的私钥格式错误，请检查RSA私钥配置');
        }

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }

        $sign = base64_encode($sign);
        return $sign;
    }
    /**
     * 9亿支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
    protected function wmPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "wmPay");
        $data = [
            'pid' => $appId,
            'type'=>'wxpay',
            'out_trade_no' => $ordno,
            'name' => 'VIP会员',
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
        ];
        $data['sign']=$this->getSign($data, $appKey);
        $data['sign_type']='MD5';
        $htmls = "<form id='dblPay' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['dblPay'].submit();</script>";
        exit($htmls);
    }
    /**
     * 马化云支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
    protected function mhyPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "mhyPay");
       
        $data = [
            'pid'          => $appId,
            'name'         => 'JI微信',
            'type'         => 'tiger',
            'money'        => $payMoney,
            'out_trade_no' => $ordno,
            'notify_url'   => $payNotifyUrl,
            'return_url'   => $payCallBackUrl,
        ];
        // $data = array_filter($data);
        ksort($data);
        $str1 = '';
        foreach ($data as $k => $v) {
            $str1 .= $k . "=" . $v . '&';
        }
        $sign = rtrim($str1,'&');
        $sign .=$appKey;
    
        $sign = md5($sign);
        $data['sign']      = $sign;
        $data['is_wx_browser']      = '0'; // 不参与签名
       
        $headers = array('Content-Type: application/x-www-form-urlencoded');
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $payGateWayUrl); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data)); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        $result = json_decode($result, true);
       
        if ($result['code'] != 200) {
            die($result['msg']);
        }
        $wxUrl = $result['data']['wxUrl'];
        // echo $wxUrl;die;
         $this->assign('url',$wxUrl);
         
         return $this->tpl_fetch('/mhypay');
        // echo ("<script>window.location.href='".$wxUrl."'</script>");//$wxUrl;//
    }
    /**
     * SY支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
    protected function syPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "syPay");
        $data = [
            'pid' => $appId,
            'type'=>'wxpay',
            'out_trade_no' => $ordno,
            'name' => 'VIP会员',
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
        ];
        $data['sign']=$this->getSign($data, $appKey);
        $data['sign_type']='MD5';
        $htmls = "<form id='syPay' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['syPay'].submit();</script>";
        exit($htmls);
    }
    /**
     * 百合支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
    protected function bhPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "bhPay");
        
        $data = [
            'price'=>sprintf("%.2f",$payMoney),
            'id'=>$appId,
            'on_order'=>$ordno,
            'ret'=>urlencode($payCallBackUrl),
            'notify_url'=>urlencode($payNotifyUrl),
            'tid'=>1,
            ];
            
          
            // $res = httpRequest($payGateWayUrl,'POST',$data);
            // halt($res);
        $fieldString = '/?';
        foreach ($data as $key => $value) {
            $fieldString .= $key . "=" . $value . "&";
        }
        $fieldString = rtrim($fieldString, '&');
        $url = $payGateWayUrl.$fieldString;
       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,5);
        $content = curl_exec($ch);
        curl_close($ch);
        $url = $content;
        // halt($url);
        // echo("<script language='javascript'>window.top.location.href='{$url}'</script>");exit;
        echo $content;
        exit;
      
    }
    
    /**
     * 永昌支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
     public function ycPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
           
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "ycPay");
        $data = [
                    'api_id' => $appId,
                    'record' => $ordno,
                    'money' => sprintf("%.2f",$payMoney),
                    'refer' => $payCallBackUrl,
                    'notify_url' => $payNotifyUrl
                ];
                
        $sdata = [
            'api_id' => $appId,
            'record' => $ordno,
            'money' => sprintf("%.2f",$payMoney)
        ];
    
       ksort($sdata);
       $str = '';
       foreach ($sdata as $k => $v) {//组装参数
          $str .= '&' . $k . "=" . $v;
       }
     
      $data['sign'] = md5(trim($str) . $appKey);//md5加密参数
      $this->assign('apiurl',$payGateWayUrl);
      $this->assign('res',$data);
      return $this->tpl_fetch('/pay');
    }
    
    /**
     * 约德尔支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
    protected function ydePay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "ydePay");
        $data = [
            'out_trade_no'=>$ordno,
            'mchid'=>$appId,
            'total_fee'=>$payMoney*100,
            'notify_url'=>$payNotifyUrl,
            'callback_url'=>$payCallBackUrl,
            'error_url'=>$payCallBackUrl
        ];
        ksort($data); 
        reset($data); 
        $fieldString = [];
        foreach ($data as $key => $value) {
            if(!empty($value)){
                $fieldString [] = $key . "=" . $value . "";
             }
        }
        $fieldString = implode('&', $fieldString);
        $data['sign'] = strtoupper(md5($fieldString.$appKey));
        $payGateWayUrl = $payGateWayUrl.'/toPay';
        $res = httpRequest($payGateWayUrl,'POST',$data);
        $res = json_decode($res,true);
        // array(3) { ["code"]=> int(0) ["msg"]=> string(0) "" ["data"]=> array(2) { ["orderId"]=> string(32) "2c918084812cc88801812dd36e1c48f0" ["payUrl"]=> string(108) "http://w.kyq8.cn.w.kunlunea.com/2c918084812cc88801812dd36e1c48f0/d24b4f59adb2c524a320a653c7f9e496/711gz.html" } }
       
        if($res['code']==0){
            $url =$res['data']['payUrl'];
            echo("<script language='javascript'>window.top.location.href='{$url}'</script>");exit;
          
        }else{
            $this->error($res['msg']);
        }
       
    }
    
    /**
     * 大波浪支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
    protected function dblPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "dblPay");
        $data = [
            'pid' => $appId,
            'type'=>'wxpay',
            'out_trade_no' => $ordno,
            'name' => 'VIP会员',
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
        ];
        $data['sign']=$this->getSign($data, $appKey);
        $data['sign_type']='MD5';
        $htmls = "<form id='dblPay' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['dblPay'].submit();</script>";
        exit($htmls);
    }
    /**
     * 冈本
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function gbPay($payInfo, $user, $pay_id){
        
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "gbwPay");
       
        $bodyJson = [
            'amount'            => $payMoney*100,
            'channelType'       => '2:1',
            'merchantOrderCode' => $ordno,
            'noticeUrl'         => $payNotifyUrl,
            'returnUrl'         => $payCallBackUrl
            ];
        $signStr = '';
        foreach ($bodyJson as $key => $value){
          $signStr .= $key . '=' . $value;
        }
        $signStr .= 'key=' . $appKey;
       
        $requestJson = [
            'merchantCode' => $appId,
            'sign' => md5($signStr),
            'body' => $bodyJson
            ];
        
       
        // $res = $this->curl_post($payGateWayUrl,json_encode($requestJson));
        // halt(json_encode($requestJson));
       
        $ch = curl_init($payGateWayUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestJson));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response,true);  
        if(is_null($response)){
            $this->error('暂无可支付渠道'); 
        }else{
            if($response['code'] == '200'){
                $url = $response['body']['paymentUrl'];
                echo("<script language='javascript'>window.top.location.href='{$url}'</script>");exit;
            }else{
                $this->error($response['message']); 
            }
        }
        
      
    }
    //提交json
    public function curl_post($url , $data=array()){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json; charset=utf-8',
				'Content-Length: ' . strlen($data)
			));

        // POST数据

        curl_setopt($ch, CURLOPT_POST, 1);

        // 把post的变量加上

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($ch);

        curl_close($ch);

        return $output;

    }
    /**
     * 七淘支付(微信)
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function qtwPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "qtwPay");
        $data = [
            'pid' => $appId,
            'type'=>'wxpay',
            'out_trade_no' => $ordno,
            'name' => 'VIP会员',
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
        ];
        $data['sign']=$this->getSign($data, $appKey);
        $data['sign_type']='MD5';
        $htmls = "<form id='aicaipay' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['aicaipay'].submit();</script>";
        exit($htmls);

    }
    
    /**
     * 星火易支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    
    protected function xhyPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "xhyPay");
        $data = [
            'pid' => $appId,
            'type'=>'wxpay',
            'out_trade_no' => $ordno,
            'name' => 'VIP会员',
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
        ];
        $data['sign']=$this->getSign($data, $appKey);
        $data['sign_type']='MD5';
        $htmls = "<form id='aicaipay' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['aicaipay'].submit();</script>";
        exit($htmls);
    }

    /**
     * 码支付（小白）
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function codePay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "codePay");
        $data = [
            'mid' => $appId,
            'type'=>2,
            'payId' => $ordno,
            'price' => $payMoney,
            'param' => mt_rand(1111,9999),
            'notifyUrl' => $payNotifyUrl,
            'returnUrl' => $payCallBackUrl,
            'isHtml'=>1
        ];
        $data['sign']=md5($data['mid'].$data['payId'].$data['param'].$data['type'].$data['price'].$appKey);
        $htmls = "<form id='aicaipay' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['aicaipay'].submit();</script>";
        exit($htmls);
    }
    /**
     *码支付（个码）微信
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function zfnPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "zfnPay");
        $data = [
            'mid' => $appId,
            'type'=>1,
            'payId' => $ordno,
            'price' => $payMoney,
            'param' => mt_rand(1111,9999),
            'notifyUrl' => $payNotifyUrl,
            'returnUrl' => $payCallBackUrl,
            'isHtml'=>1
        ];
        $data['sign']=md5($data['mid'].$data['payId'].$data['param'].$data['type'].$data['price'].$appKey);
        $htmls = "<form id='aicaipay' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['aicaipay'].submit();</script>";
        exit($htmls);
    }
    /**
     * 码支付(招财猫)
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function zcmPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "zcmPay");
        $data = [
            'pid' => $appId,
            'type'=>'wxpay',
            'out_trade_no' => $ordno,
            'name' => 'VIP会员',
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
        ];
        $data['sign']=$this->getSign($data, $appKey);
        $data['sign_type']='MD5';
        $htmls = "<form id='aicaipay' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['aicaipay'].submit();</script>";
        exit($htmls);

    }
    /**
     * 码支付(热血4支付)
    * @param $payInfo
    * @param $user
    * @param $pay_id
    */
    protected function wwxPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appSecret = $payInfo->app_secret;
        $appChannel= $payInfo->pay_channel;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "wwxPay");
        $data = [
            'app_id' => $appId,
            'app_secret' => $appSecret,
            'out_trade_no' => $ordno,
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
        ];
        $data['sign']='a'.time();
        $htmls = "<form id='aicaipay' name='aicaipay' action='" . $payGateWayUrl . "' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['aicaipay'].submit();</script>";
        exit($htmls);
    }
    /**
     * 微信官方JSAPI支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function wechatjsPay($payInfo, $user, $pay_id){
        $appId = $payInfo->app_id;
        $appSecret= $payInfo->app_secret;
        $mch_id = $payInfo->mch_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $domain = getDomain(2,$user->id);
        $payDomain = getDomain(3,$user->id);
        $wechat=new WxPay(['app_id'=>$appId,'app_secret'=>$appSecret]);
        $openId = $wechat->getOpenId($domain,$payDomain);//获取openid
        if(!$openId) exit('获取openid失败');
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);

        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "wechatPay");
        $payInfo = [
            'app_id' => $appId,
            'mch_id' => $mch_id,
            'body' => '资源购买',
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'ip' => $this->request->ip(),
            'pay_url'=>$payGateWayUrl,
            'app_key'=>$appKey,
            'openid'=>$openId,
        ];
        $result=$wechat->onCreatePayJs($ordno,$payInfo);
        if($result->return_code=='FAIL'){
            $this->error($result->return_msg);
        }
        $timestamp=time();
        $params = [
            "appId" => $appId,
            "timeStamp" => "$timestamp",        //这里是字符串的时间戳，不是int，所以需加引号
            "nonceStr" => $wechat->getNonceStr(),
            "package" => "prepay_id=" . $result->prepay_id,
            "signType" => 'MD5',
        ];
        $params['paySign'] = $wechat->getSign($params,$appKey);
        $jsApiParameters = json_encode($params);
        $html = "<html>
                <head>
                    <meta charset=\"utf-8\" />
                    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"/>
                    <title>微信支付中-支付</title>
                    <script type=\"text/javascript\">
                        //调用微信JS api 支付
                        function jsApiCall()
                        {
                            WeixinJSBridge.invoke(
                                'getBrandWCPayRequest',
                                $jsApiParameters,
                                function(res){
                                    WeixinJSBridge.log(res.err_msg);
                                    if(res.err_msg=='get_brand_wcpay_request:ok'){
                                        alert('支付成功！');
                                        location.href = '{$payCallBackUrl}'
                                    }else{
                                        alert('支付失败：'+res.err_code+res.err_desc+res.err_msg);
                                    }
                                }
                            );
                        }
                        function callpay()
                        {
                            if (typeof WeixinJSBridge == \"undefined\"){
                                if( document.addEventListener ){
                                    document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                                }else if (document.attachEvent){
                                    document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                                    document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                                }
                            }else{
                                jsApiCall();
                            }
                        }
                        callpay();
                    </script>
                </head>
            <body>    
            </body>
            </html>";
        echo $html;
        exit;
    }
    /**
     * 微信官方支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function wechatPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $mch_id = $payInfo->mch_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "wechatPay");
        $wechat=new WxPay();
        $payInfo = [
            'app_id' => $appId,
            'mch_id' => $mch_id,
            'body' => '资源购买',
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
            'ip' => $this->request->ip(),
            'wap_url'=>$this->request->domain(),
            'pay_url'=>$payGateWayUrl,
            'app_key'=>$appKey
        ];
        $result=$wechat->onCreatePayid($ordno,$payInfo);
        if($result->return_code=='FAIL'){
            $this->error($result->return_msg);
        }
        header("Location:{$result->mweb_url}&redirect_url=".$payCallBackUrl); //跳转到支付页面
        exit;
    }
    /**
     * 嘻唰唰
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function xssPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $appChannel= $payInfo->pay_channel;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "xssPay");
        $data = [
            'pid' => $appId,
            'out_trade_no' => $ordno,
            'name' => '资源购买',
            'type'=>$appChannel,
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
        ];
        $data['sign']=$this->getSign($data, $appKey);
        $data['sign_type']='MD5';
        $htmls = "<form id='aicaipay' name='aicaipay' action='" . $payGateWayUrl . "' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['aicaipay'].submit();</script>";
        exit($htmls);
    }

    
    /**
     * 云尔易付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function yuanerPay($payInfo, $user, $pay_id){
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payMchid = $payInfo->mch_id;
        $payChannel = $payInfo->pay_channel;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        $payDesc = $res['data']['remark'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "yuanerPay");
        $data = [
            'appid' => $appId,
            'name' => "test",
            'type' => $payChannel,
            'money' => $payMoney,
            'out_trade_no' => $ordno,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
            'mchid' => $payMchid,
        ];
        $data['sign'] = $this->getSign($data, $appKey);
        $htmls = "<form id='aicaipay' name='aicaipay' action='" . $payGateWayUrl . "' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['aicaipay'].submit();</script>";
        exit($htmls);
    }

    /**
     * 云尔易付 签名
     * @param $param
     * @param $key
     * @return string
     */
    protected function getSign($param, $key)
    {
        $signPars = "";
        ksort($param);
        foreach ($param as $k => $v) {
            if ("sign" != $k && "" != $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars = rtrim($signPars, '&');
        $signPars .= $key;
        $sign = md5($signPars);
        return $sign;
    }

    /**
     * 创建订单
     * @param $user
     * @param $ordno
     * @param $pay_id
     */
    public function createOrder($user, $ordno, $pay_id)
    {
        $uid = $user->id;
        $vid = $this->request->param('vid');
        $is_type = $this->request->param('is_type', 0);
        $ip = $this->request->ip();
        //获取Ua
        $form = json_decode(decrypt($this->request->param('ldk')),true);
        $ua = $form['ua'];
        
        $spreadInfo = (new Spread())->where(['id' => $vid])->find();
        $userInfo = Agent::where('id',$uid)->find();
        $spreadInfo->money = $userInfo->money;
        $spreadInfo->money1 = $userInfo->money1;
        $spreadInfo->money2 = $userInfo->money2;
        $spreadInfo->money3 = $userInfo->money3;
        $payMoney = $spreadInfo->money;
        $payDesc = '支付';
        if ($is_type == 2) {
            $payMoney = $spreadInfo->money1;
        } elseif ($is_type == 3) {
            $payMoney = $spreadInfo->money2;
        } elseif ($is_type == 4) {
            $payMoney = $spreadInfo->money3;
        }
        $payInfo=PaySetting::where(['id'=>$pay_id,'status'=>1])->find();
        if($payInfo->label == 'TMPAY' && $payMoney < 3 ){
            $this->error('下单失败,最低支付3元');
        }
        //统一下单
        $data = [
            'v_id' => $vid,
            'uid' => $uid,
            'ordno' => $ordno,
            'money' => $payMoney,
            'pid' => $user->admin_id,
            'ua' => $ua,
            'ip' => $ip,
            'pid_top' => 1,//todo 获取总代ID
            'type' => $is_type,
            'pay_id' => $pay_id,
            'ptime'=>0,
            'remark' => $payDesc,
            'ctime'=> time()
        ];
        #设置订单缓存标识
        $key = "order_{$uid}_" . date('Y-m-d');
        cache($key, [time(), $ordno]);
        cache(md5($ip),$ordno,3600);
        #保存订单
        
        $res = (new \app\common\model\Order())->save($data);
        if ($res) {
            return ['code' => 1, 'data' => $data, 'link' => $spreadInfo];
        }
        return ['code' => 0, 'data' => []];
    }

    /**
     * 获取同步回调地址
     */
    protected function getSynNotifyUrl($params, $order = '', $id = '', $domain = '',$isEncode=false)
    {
        
        $is_jf = $this->request->param('isjf');
        
                            // $from = isset($params['ldk']) ? $params['ldk'] : encrypt(json_encode(['uid'=>$id,'t'=>time()]));
        
        $host = $this->request->host(true);
        
                            // $p = ['ordno' => encrypt($order), 'ldk' => encrypt(json_encode(['uid'=>$id,'t'=>time()]))];
        $scheme = $this->request->scheme() . "://";
        $pay_domain = getDomain(2,$id);
        if ($pay_domain) {
            $domain = $pay_domain;
        }
        if (!empty($domain)) {
            $host = $domain;
            $scheme = '';
        }
        $port = $this->request->port();
        
        $ldk = json_decode(decrypt($this->request->param('ldk')),true);
        if(!isset($is_jf) || empty($is_jf)){
            $is_jf = 0;
        }
        $ldk['isjf'] = $is_jf;
        
        //同步通知默认 params 为空
        if ($params) {
            $url = $scheme . $host . ":$port" . "/return?" . http_build_query($params);
        } else {
            $url = $scheme . $host . ":$port" . "/return/ordno/" . encrypt($order) . "/ldk/" . encrypt(json_encode($ldk));
        }
        // $douyin = config('setting.douyin');
        // if ($douyin == 1) {
        //     $host = getDomain(3,$id);
        //     $url = $host . "/dreturn/ldk/" . $from . "/params/" . urlencode(http_build_query($p));
        // }
        
        //获取回调防封链接
        // $antiUrl=getAntiUrl(2);
        // if($isEncode && $antiUrl){
        //     return $antiUrl.base64_encode(urlencode($url));
        // }
        // $newUrl = $antiUrl.$url;
        
        /*$short_id = Admin::where('id',$id)->value('short_id');
        $res = getDwz($short_id,$url);
        
        if(!isset($res) || empty($res)){
            return $url;
        }
        
        if (isset($res['status']) && $res['status'] == 200) {
            $url = $res['data'];
            return $url;
        }*/
        
        return $url;
        
        
    }

    /**
     * 获取异步回调地址
     */
    protected function getAsyNotifyUrl($param = [], $action = "notify")
    {
        $host = $this->request->host(true);
        $scheme = $this->request->scheme();
        $port = $this->request->port();
        if ($param) {
            return $scheme . "://" . $host . ":$port" . "/notify/$action?" . http_build_query($param);
        }
        return $scheme . "://" . $host . ":$port" . "/notify/$action";
    }
    /**
     * 檢測订单状态
     */
    public function checkOrder()
    {
        $ordno = $this->request->param('ordno');
        $orderInfo = \app\common\model\Order::where('ordno', $ordno)->find();
        if ($orderInfo['status'] == 1) {
            return json(['code' => 1, 'msg' => 'success', 'data' => $orderInfo]);
        }
        return json(['code' => 0, 'msg' => 'notPay', 'data' => $orderInfo]);
    }

    //同步回调跳转
    public function synNotify()
    {
        
        // $ip = $this->request->ip();
        $ordno = decrypt($this->request->param('ordno'));
        
        // halt($ordno);
        // $ordno = cache(md5($ip));
        $orderInfo = \app\common\model\Order::where('ordno', $ordno)->find();
        if (empty($orderInfo)) {
            $this->error('订单不存在,请重试!', '', '', 333);
        }
        $ldk = $this->request->param('ldk');
        $form = json_decode(decrypt($ldk),true);
        $is_jf = $form['isjf'];
        //前端跳转类型   普通video  积分fvideo
        $main = 'video';
       
        if($is_jf == 1){
            $main = 'fvideo';
        }
        $this->assign('v', $orderInfo->v_id);
        $this->assign('ldk', $ldk);
        $this->assign('ordno', $orderInfo->ordno);
        $this->assign('order', $orderInfo);
        $this->assign('main', $main);
        return $this->tpl_fetch('/callback');
    }

    //抖音同步回调跳转
    public function dyNotify()
    {
        $params = $this->request->param('params');
        $f = $this->request->param('ldk');
        $ldk=json_decode(decrypt($f),true);
        $payDomain = getDomain(2,$ldk['uid']);
        $domain = $payDomain . "/return?" . $params . "&ldk={$f}";
        $this->assign('url', $domain);
        return $this->tpl_fetch('/jump');
    }
}