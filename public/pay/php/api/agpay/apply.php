<?php
	require("../_config.php");  //公共配置文件
	require("../_utils.php");  //工具类

	if(!isset($_REQUEST["mchOrderNo"]) || !isset($_REQUEST["amount"]) || !isset($_REQUEST["accountName"])||
       !isset($_REQUEST["accountNo"]) || !isset($_REQUEST["remark"]) ){
		echo '参数丢失';
		exit;
	}
	
	$amount = $_REQUEST["amount"] * 1 * 100; //元转换为分
    $paramArray = array(
		"mchId" => $mchId, //商户ID
		"mchOrderNo" => $_REQUEST["mchOrderNo"],  // 商户代付单号
		"amount" => $amount . '',   // 代付金额（单位分）
		"accountAttr" => '0',  // 账户属性:0-对私,1-对公,默认对私
		"accountName" => $_REQUEST["accountName"],  // 收款人账户名
		"accountNo" => $_REQUEST["accountNo"], // 收款人账户号
		"province" => '',   // 开户行所在省份
		"city" => '',    // 开户行所在市
		"bankName" => '',	 // 开户行名称
		"bankNumber" => '',	// 联行号
		"notifyUrl" => 'http://localhost/api/agpay/notify.php',	// 转账结果回调URL
		"remark" => $_REQUEST["remark"],	  // 备注
		"extra" => '',	  // 扩展域
		"reqTime" => date("YmdHis"),	 //请求时间, 格式yyyyMMddHHmmss
        "version" => '1.0'	 //版本号, 固定参数1.0
    );
	
	$sign = paramArraySign($paramArray, $mchKey);  //签名
	
	$paramArray["sign"] = $sign;

	$paramsStr = http_build_query($paramArray); //请求参数str
	$response = httpPost($payHost . "/api/agentpay/apply", $paramsStr);
	
	echo $response;
