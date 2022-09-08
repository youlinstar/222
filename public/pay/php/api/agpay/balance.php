<?php
	
	require("../_config.php");  //公共配置文件
	require("../_utils.php");  //工具类
	
    $paramArray = array(
		"mchId" => $mchId, //商户ID
		"reqTime" => date("YmdHis"),	 //请求时间, 格式yyyyMMddHHmmss
        "version" => '1.0'	 //版本号, 固定参数1.0
    );
   
	$sign = paramArraySign($paramArray, $mchKey);  //签名
	$paramArray["sign"] = $sign;

	$paramsStr = http_build_query($paramArray); //请求参数str
	
	$response = httpPost($payHost . "/api/agentpay/query_balance", $paramsStr);
	
	echo $response;
