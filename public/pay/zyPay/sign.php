<?php
/**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    function getSign($secret, $data)
    {

        // 去空
        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
		// echo $data;
        // if($data['pay_type']=='AliRoyalty'){
        //     foreach ($data['royalty_parameters'] as $k=>$v){
        //         ksort($data['royalty_parameters'][$k]);
        //     }
        // }
        // var_dump($data);
        $string_a = http_build_query($data);
		//echo $string_a;
        $string_a = urldecode($string_a);

        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;
        // var_dump($string_sign_temp);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
		
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
        // var_dump($result);
        return $result;
    }


    /**
     * @Note   验证签名
     * @param $data
     * @param $orderStatus
     * @return bool
     */
     function verifySign($data, $secret) {
        // 验证参数中是否有签名
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 生成新的签名、验证传过来的签名
        $sign2 = getSign($secret, $data);
		
        return $sign2;
    }
?>