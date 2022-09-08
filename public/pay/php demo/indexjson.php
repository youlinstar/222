<?php
        $pay_memberid = "您的商户ID";   //商户ID
        $pay_orderid = time().rand(111,999);    //订单号
        $pay_amount ='100.00';    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = 'http://www.baidu.com/notify_url.php';   //服务端返回地址
        $pay_callbackurl = 'http://www.baidu.com/return_url.php';  //页面跳转返回地址
        $Md5key = "您的商户密钥";   //密钥
        $tjurl = "http://商户后台可查看支付网关/Pay_Index.html";   //提交地址
        $pay_bankcode = '商户后台获取通道编码';   //通道编码
        //扫码
        $native = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );
      
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "1234|456";
        $native['pay_productname'] = '购买商品';
        $native['type'] = "json"; //json  或  html
		
        $postData = http_build_query($native);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $tjurl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;charset:utf-8;'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($data,true);
         if($json['code'] =='1'){
			 $url = $json['payUrl'];
			 header("Location:$url");die;
		 }else{
			 exit($data);
		 }
			 