<?php
	
	require("../_config.php");  //公共配置文件
	require("../_utils.php");  //工具类

	if(!isset($_REQUEST["sign"]) ){
		echo "fail(sign not exists)";
		exit;
	}
	
	$resSign = $_REQUEST["sign"] ;
	
	$paramArray = array();
	
	if(isset($_REQUEST["agentpayOrderId"]) ){
		$paramArray["agentpayOrderId"] = $_REQUEST["agentpayOrderId"];
	}
	
	if(isset($_REQUEST["status"]) ){
		$paramArray["status"] = $_REQUEST["status"];
	}
	
	if(isset($_REQUEST["fee"]) ){
		$paramArray["fee"] = $_REQUEST["fee"];
	}
	
	if(isset($_REQUEST["transMsg"]) ){
		$paramArray["transMsg"] = $_REQUEST["transMsg"];
	}
	
	if(isset($_REQUEST["extra"]) ){
		$paramArray["extra"] = $_REQUEST["extra"];
	}

	if(isset($_REQUEST["reqTime"]) ){
    		$paramArray["reqTime"] = $_REQUEST["reqTime"];
    }
	
	
	$sign = paramArraySign($paramArray, $mchKey);  //签名
	
	if($resSign != $sign){  //验签失败
		echo "fail(verify fail)";
		exit;
	}
	
	//处理业务

	
	echo "success";
	exit;
