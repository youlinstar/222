<?php 

  // 服务器点对点返回
    public function notifyurl() {
    	
		$returnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "true_amount" =>  $_REQUEST["true_amount"], //实付金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 支付流水号
            "returncode" => $_REQUEST["returncode"],
        );
        $md5key = "您的商户密钥";
        ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
         $sign = strtoupper(md5($md5str . "key=" . $md5key));
        
      
        if($_SERVER['REMOTE_ADDR'] == "支付系统IP"){
        if ($sign == $_REQUEST["sign"]) {
            if ($_REQUEST["returncode"] == "00") {
                /*查询订单开始*/
        $tjurl = "http://支付系统域名/Pay_Trade_query.html";
        $pay_memberid = $_REQUEST["memberid"];
        $pay_orderid = $_REQUEST['orderid'];
        $data = array(
        "pay_memberid"=>$pay_memberid, 
        "pay_orderid"=>$pay_orderid, 
        );
       
         ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $md5key));
        $data["pay_md5sign"] = $sign;
       
        $postData = $data;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $tjurl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        // curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($data,true);
        $dingdancode = $json['returncode'];
        $jiaoyicode = $json['trade_state'];
                /*查询订单结束end*/
                
         if($dingdancode == "00" && $jiaoyicode== "SUCCESS"){       //判断订单状态和付款状态
            $str = "交易成功！订单号：".$_REQUEST["orderid"];
            file_put_contents("success.txt",$str."\n", FILE_APPEND);
        	$this->EditMoney($_REQUEST['orderid'], 'Huihuangpay', 0);
            exit("OK");
         }
            }
        }
        }//ip效验
        else{
        	 exit($_SERVER['REMOTE_ADDR']."回调IP不合法,或回调IP不是指定IP");
       }  
    }

?>