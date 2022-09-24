<?php
error_reporting(0);
//载入签名算法库
include ('sign.php');
//当前界面是进行网关参数获取以及发起POST请求
//下面参数均为商户自定义，可自行修改

//请求支付地址
$api = 'http://api.rocroad.cn/api/gateway/create_order';
//商户appid->到平台首页自行复制粘贴
$appid = 1;

//商户密钥，到平台首页自行复制粘贴，该参数无需上传，用来做签名验证和回调验证，请勿泄露
$app_key = 'ZRPW6QPGZCMP1AHMSAII4ZDVG1JL9U1JCYT6LK97LRAJRYU4HILD0Y7AH7XLTIBTYGW8YNDLQWRXLJGYOMTZT1T3TFJEHNIG6R6GMDUDADKTYAQQM2SIZD2HJPROF3ER';

//订单号码，发起订单时带的订单信息，一般为用户名，交易号，等字段信息
$out_trade_no = date("YmdHis") . mt_rand(10000, 99999);
//支付类型
$pay_type = '8003'; //微信h5支付
$time = time();
//支付金额
$amount =  1;//sprintf("%.2f",0.01);
//异步通知接口url->用作于接收成功支付后回调请求
$callback_url = 'http://demo.somutech.cn/callback_demo.php';
//支付成功后自动跳转url
$success_url = 'http://demo.somutech.cn/';
//支付失败或者超时后跳转url
$error_url = 'http://demo.somutech.cn/';
//版本号
$version = 'v1.0';
//用户网站的请求支付的额外信息，请严格对接文档填写参数
$extend = '';

$data = [
    'mchId'        => $appid,
    'productId'     => $pay_type,
    'orderNo' => $out_trade_no,
	'currency'	=> 'cny',
	'time' => $time,
	'clientIp' => '127.0.0.1',
    'amount'       => $amount,
    'notifyUrl' => $callback_url,
	'subject' => 'subject',
	'body' => 'body'
];

//拿APPKEY与请求参数进行签名
$sign = getSign($app_key, $data);

?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>接口调用测试</title>
</head>
<body>
	<span>请求支付中...</span>
	<form action="<?php echo $api;?>" method="post" id="frmSubmit">
		<input type="hidden" name="mchId" value="<?php echo $appid;?>" />
		<input type="hidden" name="productId" value="<?php echo $pay_type;?>"/>
		<input type="hidden" name="orderNo" value="<?php echo $out_trade_no;?>"/>
		<input type="hidden" name="time" value="<?php echo $time;?>"/>
		<input type="hidden" name="currency" value="cny"/>
		<input type="hidden" name="subject" value="subject"/>
		<input type="hidden" name="body" value="body"/>
		<input type="hidden" name="sign" value="<?php echo $sign;?>"/>
		<input type="hidden" name="notifyUrl" value="<?php echo $callback_url;?>" />
		<input type="hidden" name="amount" value="<?php echo $amount;?>" />
	</form>
	<script type="text/javascript">
		document.getElementById("frmSubmit").submit();
	</script>
</body>
</html>