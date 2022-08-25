<?php

namespace app\index\controller;

use app\common\controller\Common;
use app\common\model\Admin;
use app\common\model\Users;
use app\common\model\Agent;
use app\common\model\PayShow;
use app\common\model\PaySetting;
use app\common\model\WxPay;
use think\Exception;

class Trade extends Common
{

    /**
     * 积分下单
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function jfplay(){

        $from = $this->form;
        $ua = $from['ua'];
        $userModel = new Users();
        $users = $userModel->where(['ua'=>$ua,'uid'=>$this->uid])->find();

        if(empty($users)){

            $this->error('用户不存在');

        }

        #片库积分价格
        $jf = Admin::where('id',$this->uid)->value("jf");

        if(empty($jf) || $jf == 0){

           $jf = 10;

        }

        if($jf > $users->jifen){

            $this->error('积分不足');

        }
        $users->setDec('jifen',$jf);
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $v_id = $this->request->param('vid/d',0);
        $etime = time() + 86400;
        $data = [
            'v_id' =>$v_id,
            'uid' => $this->uid,
            'ip' => getIp(),
            'ordno' => $ordno,
            'ua' => $ua,
            'etime' =>$etime,
            'is_month' => 0,
            'is_week' => 0,
            'is_day' => 0,
            'is_jf'=>1,
            'ctime' => time()
        ];

        $res = (new PayShow())->save($data);

        $url = "/fvideo";
        $list = ['vid'=>$v_id,'ldk'=>$this->ldk];
        $this->assign('url',$url);
        $this->assign('list',$list);
        return $this->fetch("/common/jump");
    }

    /**
     * 支付通道入口
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        #支付选择类型
        $type = $this->request->param('type','');
        #支付ID
        $pay_id = $this->request->param('pay_id/d', 0);
        $user = Admin::where('id', $this->uid)->find();
        if(empty($user)){
            $this->error("参数错误");
        }
        if (empty($pay_id)) {
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

            case 'ksPay':#todo 凯撒支付
                $this->ksPay($payInfo, $user, $payInfo->id);
                break;
            case 'glsPay':#todo G63云支付
                $this->glsPay($payInfo, $user, $payInfo->id);
                break;
            case 'gxbPay':#todo 高希霸支付
                $this->gxbPay($payInfo, $user, $payInfo->id);
                break;
            case 'jdPay':#todo 金蝶支付     【原冈本支付】
                $this->jdPay($payInfo, $user, $payInfo->id);
                break;
            case 'fjPay':#todo 飞机H5通道   【原约德尔】
                return $this->fjPay($payInfo, $user, $payInfo->id);
                break;
            case 'dztzPay':#todo 大猪蹄子   【大菠浪】
                return $this->dztzPay($payInfo, $user, $payInfo->id);
                break;
            case 'xjpay':#todo 小鸡支付（支付宝）
                $this->xjpay($payInfo, $user, $payInfo->id);
                break;
            case 'lhPay':#todo 禄恒支付
                return $this->lhPay($payInfo, $user, $payInfo->id);
                break;
            case 'ycPay':#todo 永昌支付
                return $this->ycPay($payInfo, $user, $payInfo->id);
                break;
            case 'zhPay':#todo 纵横支付
                return $this->zhPay($payInfo, $user, $payInfo->id);
                break;
            case 'xyPay':#todo 逍遥支付宝支付
                return $this->xyPay($payInfo, $user, $payInfo->id);
                break;
            default:
                $this->error("未匹配到{$payInfo->label}支付渠道,请确认");
                break;
        }
    }

    /**
     * 纵横支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    public function zhPay($payInfo, $user, $pay_id)
    {
        #############################################################################################
        #todo 默认支付网关：#
        #############################################################################################
        #   字段名	    字段描述	    必需	    签名	    说明事项
        #   api_id	    商户号	    是	    是
        #   orderid	    订单号	    是	    是
        #   money	    交易金额	    是	    是	    单位：元
        #   notify_url	通知地址	    是	    是
        #   return_url	页面跳转地址	是	    是
        #   ip	        下单ip地址	是	    否
        #   type	    支付类型	    否	    否	    支付宝：alipay，微信：wxpay，QQ：qqpay（不传则随机）
        #   mid	        通道代码	    否	    否   	指定账户mid（不传则随机）
        #   gtype	    通道代码	    否	    否	    请查看商户后台-》通道列表（不传则随机）
        #   attch	    附加信息	    否	    否	    附加参数（原样返回）
        #   third	    承接页	    否	    否	    默认使用，传值0不使用三方承接页
        #   sign	    签名	        是	    否
        #################################################################
        #todo 响应字段：#
        #################################################################
        #   字段名	        字段描述
        #   code	        0为成功（其余值请查看msg错误信息）
        #   msg	            提示信息
        #   reallink	    原支付链接
        #   payUrl	        平台支付链接
        #################################################################
        #todo 异步回调：#
        #################################################################
        #   字段名	        字段描述	      是否必需	    签名
        #   api_id	        商户号	        是	        是
        #   orderid	        商户订单号	    是	        是
        #   api_orderid	    平台订单号	    是	        是
        #   money	        交易金额	        是	        是
        #   notify_url	    通知地址	        是	        是
        #   return_url	    页面跳转地址	    是	        是
        #   attch	        附加信息	        否	        否
        #   sign	        签名	            是	        否
        #################################################################
        #签名规则
        #   1)	将接口中的请求字段按照Ascii码方式进行升序排序
        #   2)	按照key1=val1&key2=val2&key3=val3....&key=md5秘钥生成加密字符串
        #   3)	将上一步生成的字符串进行MD5加密，并转换成大写

        #################################################################
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
        $payNotifyUrl = $this->getAsyNotifyUrl([], "zhPay");
        $data = [
            'api_id'=>$appId,
            'orderid'=>$ordno,
            'money'=>$payMoney,
            'notify_url'=>$payNotifyUrl,
            'return_url'=>$payCallBackUrl
        ];
        #todo 组装签名
        ksort($data);
        reset($data);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= $k . "=" . $v . '&';
        }

        $data['sign'] = strtoupper(md5(trim($str) . $appKey));//md5加密参数
        $data['ip'] = getIp();
        $data['type'] = 'wxpay';

        $res = httpRequest($payGateWayUrl,'POST',$data);
        $res = json_decode($res,true);
        if(empty($res)){
            $this->error('支付通道未开启,请联系客服');
        }
        if($res['code'] == 0)
        {
            $this->assign([
                'url'=>$res['payUrl'],
                'param'=>[]
            ]);
            return $this->fetch('common/pay');
        }

        $this->error($res['msg']);
    }

    /**
     * 永昌支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    public function ycPay($payInfo, $user, $pay_id)
    {
        #################################################################
                            #发起支付API（POST/GET）：#
        #################################################################
        // 字段名称    字段类型    必填         字段说明
        // api_id       string      是          商户PID（商户后台获取）
        // record       string      是          附加参数（可传入您网站的订单号或用户名等唯一参数）
        // money        float（2）  是          充值金额（注意：php使用 sprintf("%.2f",金额) 强制转换2位小数后提交）
        // refer        string      是          同步回调网址（当支付成功或支付超时后将自动跳转到指定网）
        // notify_url   string      是          异步回调网址
        // mid          string      否          收款账号MID（发起支付时可传入此参数指定收款账号，为空则随机轮询账号）
        // sign         string      是          数据签名（签名方法见下文）

        #################################################################
                            #异步通知API（POST/GET)#
        #################################################################
        // 字段名称         字段类型            字段说明
        // key              string              商户密匙KEY（由支付平台返回给回调地址判断）
        // money            float（2）          金额（注意：php使用 sprintf("%.2f",金额)  接收此参数）
        // order            string              支付平台创建的订单号
        // record           string              附加参数（发起支付传递的您网站的订单号或用户名等唯一参数）
        // sign             string              数据签名（签名方法见下文）
        #################################################################
                            #数据签名算法：#
        #################################################################
        //   $data = [
        //       'api_id' => $api_id,//商户ID
        //       'record' => $record,//附加参数
        //       'money' => $money//金额
        //   ];

        //   ksort($data);
        //   $str1 = '';
        //   foreach ($data as $k => $v) {//组装参数
        //       $str1 .= '&' . $k . "=" . $v;
        //   }

        //   $sign_ok = md5(trim($str1) . 您的商户密匙KEY);//md5加密参数
        #################################################################



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

        $this->assign([
            'url'=>$payGateWayUrl,
            'param'=>$data
        ]);

        return $this->fetch('common/pay');

    }

    /**
     * 禄恒支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function lhPay($payInfo, $user, $pay_id)
    {

        #########################################################
        // 商户ID	mchId	是	String(30)	20001222	支付中心分配的商户号
        // 应用ID	appId	否	String(30)	cbsgB1T0SL6tfflFYoBX	商户应用ID
        // 支付产品ID	productId	是	String(24)	8001 8007	支付宝H5支付	20% 8003	微信H5支付	25%
        // 商户订单号	mchOrderNo	是	String(30)	20160427210604000490	商户生成的订单号
        // 支付金额	amount	是	int	100	支付金额,单位分
        // 币种	currency	是	String(3)	cny	三位货币代码,人民币:cny
        // 客户端IP	clientIp	否	String(32)	210.73.10.148	客户端IP地址
        // 设备	device	否	String(64)	ios10.3.1	客户端设备
        // 异步回调地址	notifyUrl	是	String(128)	http://shop.xx.com/notify.htm	支付结果异步回调URL
        // 同步请求地址	returnUrl	否	String(128)	http://shop.xx.com/return.htm	支付结果同步请求URL
        // 商品主题	subject	是	String(64)	xxpay测试商品1	商品主题
        // 商品描述信息	body	是	String(256)	xxpay测试商品描述	商品描述信息
        // 支付通道子账户ID	payPassAccountId	否	String(256)	指定通道子账号ID	指定通道子账号ID
        // 附加参数	extra	否	String(512)	{“openId”:”o2RvowBf7sOVJf8kJksUEMceaDqo”}	特定渠道发起时额外参数
        // 扩展参数2	param2	否	String(64)		支付中心回调时会原样返回
        // 请求时间	reqTime	是	String(30)	20190723141000	请求接口时间， yyyyMMddHHmmss格式
        // 接口版本	version	是	String(3)	1.0	接口版本号，固定：1.0
        // 签名	sign	是	String(32)	C380BEC2BFD727A4B6845133519F3AD6	签名值，详见签名算法
        #########################################################
        // 字段名	变量名	必填	类型	示例值	描述
        // 返回状态码	retCode	是	String(16)	0	0-处理成功，其他-处理有误，详见错误码
        // 返回信息	retMsg	否	String(128)	签名失败	具体错误原因，例如：签名失败、参数格式校验错误
        // {
        //     "payJumpUrl": "http://170.33.8.116:8182/payurl?mchOrderId=Pnull202011171714049724732&amount=686.00",
        //     "payMethod": "formJump",
        //     "payUrl": "",
        //     "retCode": "0",
        //     "sign": "3251661E4B84AAB6BAA1A6A025F40036"
        // }
        #########################################################
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
        $payNotifyUrl = $this->getAsyNotifyUrl([], "lhPay");

        $data = [
            'mchId'=>$appId,
            'productId'=>8003,
            'mchOrderNo'=>$ordno,
            'amount'=>$payMoney*100,
            'currency'=>'cny',
            'notifyUrl'=> $payNotifyUrl,
            'returnUrl'=> $payCallBackUrl,
            'subject'=>'叮当猫商城',
            'body'=>'VIP会员',
            'reqTime'=>date("YmdHis"),
            'version'=>'1.0'
        ];
        ksort($data);  //字典排序
		reset($data);

		$str = "";
		foreach ($data as $k => $v) {
			if( strlen($k)  && strlen($v) ){
				$str = $str . $k . "=" . $v . "&";
			}
		}
		$data['sign'] = strtoupper(md5($str . "key=" . $appKey));  //签名

        $curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $payGateWayUrl,
		  CURLOPT_RETURNTRANSFER => 1,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => http_build_query($data),
		  CURLOPT_HTTPHEADER => array(
			"cache-control: no-cache",
			"content-type: application/x-www-form-urlencoded"
		  ),
		));
		$res = curl_exec($curl);

		curl_close($curl);

        $res = json_decode($res,true);

        if(empty($res)){
            $this->error('支付通道未开启,请联系客服');
        }

        if($res['retCode'] == 0){
            $url = $res['payJumpUrl'];
            $param = [];
            $this->assign([
                'url'=>$url,
                'param'=>$param
                ]);

            return $this->fetch('common/pay');
            // exit("<script>window.top.location.href='{$url}'</script>");
        }else{
            $this->error($res['retMsg']);
        }



    }

    /**
     * 金蝶支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function jdPay($payInfo, $user, $pay_id){

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
        $payNotifyUrl = $this->getAsyNotifyUrl([], "jdPay");

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

        $data = [
            'merchantCode' => $appId,
            'sign' => md5($signStr),
            'body' => $bodyJson
        ];


        $res = $this->curl_post($payGateWayUrl,$data);
        $res = json_decode($res,true);

        if(empty($res)){
            $this->error('支付通道未开启,请联系客服');
        }

        if($res['code'] == '200'){
            $url = $res['body']['paymentUrl'];
            exit("<script>window.top.location.href='{$url}'</script>");
        }else{
            $this->error($res['message']);
        }



    }

    /**
     * 飞机H5通道【约德尔子通道】
     * @param $payInfo
     * @param $user
     * @param $pay_id
     * @return void
     */
    protected function fjPay($payInfo, $user, $pay_id){
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
        $payNotifyUrl = $this->getAsyNotifyUrl([], "fjPay");
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

        $res = httpRequest($payGateWayUrl,'POST',$data);

        $res = json_decode($res,true);
        if(empty($res))
        {
            $this->error('支付通道未开启,请联系客服');
        }
        if($res['code']==0){
            $url = $res['data']['payUrl'];
            echo("<script>window.top.location.href='{$url}'</script>");exit;

        }else{
            $this->error($res['msg']);
        }

    }

#####todo ============================易支付类================================================#####
    /**
     * 逍遥支付宝支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function xyPay($payInfo, $user, $pay_id){
        ##########################################################################
                        #请求API下单支付 POST#
        ##########################################################################
        #字段名	        变量名	        必填	    类型	    示例值	                            描述
        #商户ID	        pid	            是	    Int	    1001
        #支付方式	    type	        是	    String	alipay	                            支付方式列表
        #商户订单号	    out_trade_no	是	    String	20160806151343349
        #异步通知地址	    notify_url	    是	    String	http://www.pay.com/notify_url.php	服务器异步通知地址
        #跳转通知地址	    return_url	    否	    String	http://www.pay.com/return_url.php	页面跳转通知地址
        #商品名称	    name	        是	    String	VIP会员
        #商品金额	    money	        是	    String	1.00
        #用户IP地址	    clientip	    是	    String	192.168.1.100	                    用户发起支付的IP地址
        #设备类型	    device	        否	    String	pc	                                根据当前用户浏览器的UA判断，传入用户所使用的浏览器或设备类型，默认为pc
        #业务扩展参数	    param	        否	    String	没有请留空	                        支付后原样返回
        #签名字符串	    sign	        是	    String	202cb962ac59075b964b07152d234b70	签名算法点此查看
        #签名类型	    sign_type	    是	    String	MD5	默认为MD5  #todo  不参与签名
        ##########################################################################
                            #返回数据 JSON#
        ##########################################################################
        #字段名	        变量名	        必填	    类型	    示例值
        #返回状态码	    code	        Int	    1	    1为成功，其它值为失败
        #返回信息	    msg	            String		    失败时返回原因
        #订单号	        trade_no	    String	20160806151343349	支付订单号
        #支付跳转url	    payurl	        String	http://1-cdvip.wsopkf.cn/pay/wxpay/202010903/	如果返回该字段，则直接跳转到该url支付
        #二维码链接	    qrcode	        String	weixin://wxpay/bizpayurl?pr=04IPMKM	如果返回该字段，则根据该url生成二维码
        #小程序跳转url	urlscheme	    String	weixin://dl/business/?ticket=xxx	如果返回该字段，则使用js跳转该url，可发起微信小程序支付

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
        $payNotifyUrl = $this->getAsyNotifyUrl([], "xyPay");
        $data = [
            'pid' => $appId,
            'type'=>'alipay',
            'out_trade_no' => $ordno,
            'name' => 'VIP会员',
            'money' => $payMoney,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
            'clientip'=>getIp()
        ];
        $data['sign']=$this->getSign($data, $appKey);
        $data['sign_type']='MD5';
        $res = httpRequest($payGateWayUrl,'POST',$data);
        $res = json_decode($res,true);

        if(empty($res))
        {
            $this->error('支付通道未开启,请联系客服');
        }
        if($res['code'] == 1)
        {
            $this->assign([
                'url'=>$res['payurl'],
                'param'=>[]
            ]);
            return $this->fetch('common/pay');
        }
        $this->error($res['msg']);
    }

    /**
     * 小鸡支付（支付宝）
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function xjpay($payInfo, $user, $pay_id){
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
        $payNotifyUrl = $this->getAsyNotifyUrl([], "xjpay");
        $data = [
            'pid' => $appId,
            'type'=>'alipay',
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
     * 大猪蹄子【大波浪】支付  G63一致
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function dztzPay($payInfo, $user, $pay_id){
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
        $payNotifyUrl = $this->getAsyNotifyUrl([], "dztzPay");
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

        $htmls = "<form id='dztzPay' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['dztzPay'].submit();</script>";
        exit($htmls);
    }

    /**
     * 高希霸支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function gxbPay($payInfo, $user, $pay_id)
    {
        $ordno = date("YmdHis") . rand(1000000, 9999999);
        $res = $this->createOrder($user, $ordno, $pay_id);
        $appId = $payInfo->app_id;
        $appKey = $payInfo->app_key;
        $payGateWayUrl = $payInfo->pay_url;
        $payMoney = $res['data']['money'];
        if ($res['code'] == 0) {
            $this->error('下单失败');
        }
        #todo 同步通知
        $payCallBackUrl = $this->getSynNotifyUrl([], $ordno, $user->id);
        #todo 异步通知
        $payNotifyUrl = $this->getAsyNotifyUrl([], "gxbPay");

        #### todo 下单信息
        // mchid	    是	string	1001	商户id
        // type	        是	string	wechat	通道类型，wechat-微信公众号支付，wechat_personal-微信个码，wechat_applets-微信小程序支付
        // out_trade_no	是	string	2021102710000	商户订单号
        // total_fee	是	integer	100	订单金额，单位分
        // notify_url	是	string	http://www.domain.com/notify	异步接收支付结果通知的回调地址
        // callback_url	是	string	http://www.domain.com/return	订单支付后跳转的地址
        // error_url	否	string	http://www.domain.com/return	取消支付跳转的地址，仅部分支付类型有效
        // attach	    否	string	zhangwuji	附加数据，在查询API和支付通知中原样返回，可作为自定义参数使用
        // description	否	string	维他奶	商品描述
        // sign	        是	string	UMJIHEAOWSRCYBDPZXGQFTVKNLTFUULC	下单数据签名，详看签名规则

        $data = [
            'mchid'=>$appId,
            'type'=>'wechat',
            'out_trade_no'=>$ordno,
            'total_fee'=>$payMoney*100,//订单金额，单位分
            'notify_url'=>$payNotifyUrl,
            'callback_url'=>$payCallBackUrl,
            'error_url'=>$payCallBackUrl
        ];

        # todo 组合签名
        $str = '';
        ksort($data);
        reset($data);
        foreach ($data as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $str = rtrim($str, '&');
        $str .= $appKey;
        $data['sign'] = strtoupper(hash('md5', $str));

        $res = httpRequest($payGateWayUrl,'POST',$data);
        $res = json_decode($res,true);

        # todo 下单返回信息
        // code	integer	0	0-下单成功，其他均为失败
        // msg	string	success	提示信息
        // data.payUrl	string	http://pay.abc.com/pay?osn=12345678910	支付地址
        // data.qrcode	string	weixin://wxpay/bizpayurl?sr=123456	支付二维码，扫码类型的支付才有此参数返回
        if(empty($res)){
            $this->error('gxbPAY支付通道未开启,请联系客服');
        }
        if(isset($res['code']) && $res['code']==0){
            $url =$res['data']['payUrl'];
            exit("<script>window.top.location.href='{$url}'</script>");
        }else{
            $this->error($res['msg']);
        }

    }

    /**
     * G63云支付支付
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function glsPay($payInfo, $user, $pay_id){
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
        $payNotifyUrl = $this->getAsyNotifyUrl([], "glsPay");
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

        $htmls = "<form id='glspay' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['glspay'].submit();</script>";
        exit($htmls);
    }

    /**
     * 凯撒支付 type类型不一样
     * @param $payInfo
     * @param $user
     * @param $pay_id
     */
    protected function ksPay($payInfo, $user, $pay_id){
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
        $payNotifyUrl = $this->getAsyNotifyUrl([], "ksPay");
        $data = [
            'pid' => $appId,
            'type'=>'jsapi',
            'out_trade_no' => $ordno,
            'name' => 'VIP会员',
            'money' => $payMoney*100,
            'notify_url' => $payNotifyUrl,
            'return_url' => $payCallBackUrl,
        ];
        ksort($data);
        $str ="";
        foreach ($data as $k=>$v){
            if ($k != "" && $v != "") {
                $str .= $k . "=" . $v . "&";
            }
        }

        $data['sign'] = strtoupper(md5($str."key=".$appKey));

        $htmls = "<form id='ksPAY' name='aicaipay' action='" . $payGateWayUrl . "' target='_top' method='post'>";
        foreach ($data as $key => $val) {
            $htmls .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $htmls .= "</form>";
        $htmls .= "<script>document.forms['ksPAY'].submit();</script>";
        exit($htmls);
    }

#####todo ============================易支付类结束================================================#####

    /**
     * 通用POST json
     * @param $url
     * @param $data
     * @return bool|string
     */
    public function curl_post($url , $data=array()){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json; charset=utf-8'
				// 'Content-Length: ' . strlen($data)
			));

        // POST数据

        curl_setopt($ch, CURLOPT_POST, 1);

        // 把post的变量加上

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $output = curl_exec($ch);

        curl_close($ch);

        return $output;

    }

    /**
     * 通用 签名
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

        //获取Ua
        $form = $this->form;

        $agentInfo = Agent::where('id',$uid)->find();

        $payMoney = $agentInfo->money;
        $payDesc = '支付';
        if ($is_type == 2) {
            $payMoney = $agentInfo->money1;
        } elseif ($is_type == 3) {
            $payMoney = $agentInfo->money2;
        } elseif ($is_type == 4) {
            $payMoney = $agentInfo->money3;
        }

        //统一下单
        $data = [
            'v_id' => $vid,
            'uid' => $uid,
            'ordno' => $ordno,
            'money' => $payMoney,
            'pid' => $user->admin_id,
            'ua' => $form['ua'],
            'ip' => getIp(),
            'pid_top' => 1,//todo 获取总代ID
            'type' => $is_type,
            'pay_id' => $pay_id,
            'ptime'=>0,
            'remark' => $payDesc,
            'ctime'=> time()
        ];

        #保存订单

        $res = (new \app\common\model\Order())->save($data);
        if ($res) {
            return ['code' => 1, 'data' => $data];
        }
        return ['code' => 0, 'data' => []];
    }

    /**
     * 获取同步回调地址
     * @param $params
     * @param $order
     * @param $id
     * @return string
     */
    protected function getSynNotifyUrl($params, $order = '', $id = 0)
    {
        $rkType = $this->request->param('rkType');
        $form = $this->form;
        $scheme = $this->request->scheme() . "://";
        #获取支付域名
        $pay_domain = getDomain(2,$id);
        if ($pay_domain) {
            $scheme = $pay_domain;
        }

        switch ($rkType)
        {
            case 1;
                $rukou = 'video';
                break;
            case 2:
                $rukou = 'fvideo';
        }

        $form['rukou'] = $rukou;

        //同步通知默认 params 为空
        if ($params) {
            $url = $scheme . "/return?" . http_build_query($params);
        } else {
            $url = $scheme . "/return/ordno/" . encrypt($order) . "/ldk/" . encrypt(json_encode($form));
        }

        return $url;


    }

    /**
     * 获取异步回调地址
     */
    protected function getAsyNotifyUrl($param = [], $action = "notify")
    {
        $host = $this->request->host(true);
        $scheme = $this->request->scheme();
        if ($param) {
            return $scheme . "://" . $host . "/notify/$action?" . http_build_query($param);
        }
        return $scheme . "://" . $host . "/notify/$action";
    }

    /**
     * 檢測订单状态
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
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

    /**
     * 同步回调跳转
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function synNotify()
    {
        $ordno = decrypt($this->request->param('ordno'));
        $orderInfo = \app\common\model\Order::where('ordno', $ordno)->find();
        if (empty($orderInfo)) {
            $this->error('订单不存在,请重试!', '', '', 333);
        }
        $ldk = $this->ldk;
        $form = $this->form;
        $main = $form['rukou'];
        if(empty($main))
        {
            $main = 'video';
        }
        $this->assign('v', $orderInfo->v_id);
        $this->assign('ldk', $ldk);
        $this->assign('ordno', $orderInfo->ordno);
        $this->assign('order', $orderInfo);
        $this->assign('main', $main);
        return $this->fetch('/common/callback');

    }
}
