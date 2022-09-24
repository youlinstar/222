<?php
//接收异步通知请求demo文件
//签名算法库
include ('sign.php');
// echo 'success';die;
//测试时，将来源请求写入到txt文件，方便分析查看
file_put_contents("./callback_log.txt", json_encode($_POST));
//商户名称
$appid  = $_POST['appid'];
//支付时间戳
$callbacks  = $_POST['callbacks'];
//支付状态
$pay_type  = $_POST['pay_type'];
//支付金额
$amount  = $_POST['amount'];
//支付时提交的订单信息
$success_url  = $_POST['success_url'];
//平台订单交易流水号
$error_url  = $_POST['error_url'];
//该笔交易手续费用
$out_trade_no  = $_POST['out_trade_no'];
//实付金额

//回调时间戳
$sign  = $_POST['sign'];

$data = [
    'appid'        => $appid,
    'callbacks'     => $callbacks,
    'pay_type' => $pay_type,
    'amount'       => $amount,
    'success_url'  => $success_url,
    'error_url'    => $error_url,
    'out_trade_no'      => $out_trade_no,
    'sign'      => $sign,
];

//第一步，检测商户appid否一致
if ($appid != '1') exit('error:appid');
//第二步，验证签名是否一致
if (verifySign($data,'ZRPW6QPGZCMP1AHMSAII4ZDVG1JL9U1JCYT6LK97LRAJRYU4HILD0Y7AH7XLTIBTYGW8YNDLQWRXLJGYOMTZT1T3TFJEHNIG6R6GMDUDADKTYAQQM2SIZD2HJPROF3ER') == $sign){
    echo 'success';
} else{
    exit('error:sign');
}


