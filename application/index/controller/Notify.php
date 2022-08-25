<?php


namespace app\index\controller;

use app\common\controller\Common;
use app\common\model\Admin;
use app\common\model\Bill;
use app\common\model\PaySetting;
use app\common\model\PayShow;
use app\common\model\Spread;
use app\common\model\VideoSort;
use app\common\model\WxPay;
use Think\Db;
use think\Exception;


class Notify extends Common
{   
     //约德尔支付异步通知
    public function tmiaoPay()
    {
       
        // {"acname":"ydePay","mchid":"2c918082812a65bf01812b8b371548fa","out_trade_no":"202206051120576232538","sign":"34E5C41B8DD240E4170C4F756BB1F699","total_fee":"1.00","trade_no":"231df5b8d80148c8aef284b01af352c3"}
        try {
          
            $data = $this->request->param();
            
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
           
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            ksort($data);
            reset($data);
            $fieldString = [];
            foreach ($data as $key => $value) {
                if(!empty($value)&&$key!='sign'){
                    $fieldString [] = $key . "=" . $value . "";
                 }
            }
            $fieldString = implode('&', $fieldString);
            $sign = strtoupper(md5($fieldString.$payInfo->app_key));
            
            if($sign!=$data['sign']){
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'tmiaoPay');
                echo 'fail1';
                die;
            }
            #支付结果
            $data['money'] = $data['total_fee'];
            $data['transaction_id'] = $data['out_trade_no'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'tmiaoPay');
                echo 'fail2';
                die;
            }
            echo "success";
            die;
        } catch (Exception $e) {
            echo $e->getMessage();
            doSyslog($e->getMessage() . '@' . json_encode($data), 'tmiaoPay');
            echo 'fail3';
            die;
        }
    }
     //马化云支付异步通知
    public function mhyPay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'mhyPay');
                echo 'fail';
                die;
            }
            #支付结果
            if($data['trade_status'] =='TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'mhyPay');
                    echo 'fail';
                    die;
                }
                echo "success";
                die;
            }
            echo 'fail';
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'mhyPay');
            echo 'fail';
            die;
        }
    }
      //OJBK支付异步通知
    public function ojbkPAY()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['fxddh'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = md5($data["fxstatus"] . $data["fxid"] . $data["fxddh"] . $data["fxfee"] . $payInfo->app_key);
            if ($sign !== $data['fxsign']) {
                doSyslog($sign . '#' . $data['fxsign'] . '@' . json_encode($data), 'ojbkPAY');
                echo 'fail';
                die;
            }
         
            #支付结果
            if($data['fxstatus'] == 1){
                //商户单号
                $data['out_trade_no'] = $data['fxddh'];
                $data['money'] = $data['fxfee'];
                //第三方订单号
                $data['transaction_id'] = $data['fxorder'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'ojbkPAY');
                    echo 'fail';
                    die;
                }
                echo "success";
                die;
            }
            echo 'fail';
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'ojbkPAY');
            echo 'fail';
            die;
        }
    }
     //GOGO支付异步通知
    public function gogoPAY()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'gogoPAY');
                echo 'fail';
                die;
            }
            #支付结果
            if($data['trade_status'] =='TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'gogoPAY');
                    echo 'fail';
                    die;
                }
                echo "success";
                die;
            }
            echo 'fail';
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'gogoPAY');
            echo 'fail';
            die;
        }
    }
     //小鸡支付异步通知
    public function xjPAY()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['randStr'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            
            $datas = [
                "order"     => $data['order'],
                "fee"       => $data['fee'],
                "randStr"   => $data['randStr'],
            ];
           
            $sign = $this->makeSign($datas, $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'xjPAY');
                echo 'fail';
                die;
            }
            #支付结果
            
            $data['money'] = $data['fee'] / 100;
            $data['transaction_id'] = $data['trade_no'] = $data['out_trade_no'] = $data['randStr'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'xjPAY');
                echo 'fail';
                die;
            }
            echo "success";
            die;
            
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'xjPAY');
            echo 'fail';
            die;
        }
    }
    //小鸡验证签名
    public function makeSign($arr, $cert)
    {
        $arr = array_filter($arr, function ($val) {
            return ($val === '' || $val === null) ? false : true;
        });
        if (isset($arr['sign'])) {
            unset($arr['sign']);
        }
        ksort($arr);
        $str = http_build_query($arr);
        $old_str = urldecode($str) . '&cert=' . $cert;
        $str = md5($old_str);
        return  strtoupper($str);
    }
     //G63云支付异步通知
    public function glsPAY()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'glsPAY');
                echo 'fail';
                die;
            }
            #支付结果
            if($data['trade_status'] =='TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'glsPAY');
                    echo 'fail';
                    die;
                }
                echo "success";
                die;
            }
            echo 'fail';
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'glsPAY');
            echo 'fail';
            die;
        }
    }
      //七里香异步通知
    public function qlxPay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            unset($data['U']);
            #获取支付接口
            $ordno = $data['record'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data); 
            reset($data); 
            $sign = '';
            foreach ($data as $key => $val) { 
            		if ($val == '' || $key == 'sign') continue; 
            		if ($sign) $sign .= '&';
            		$sign .= "$key=$val";
            }
            
            $sign = md5($sign.$payInfo->app_key);
             
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'qlxPay');
                echo 'fail2';
                die;
            }
            #支付结果
           
            $data['transaction_id'] = $data['trade_no'] = $data['out_trade_no'] = $data['record'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'qlxPay');
                echo 'fail';
                die;
            }
            echo "success";
            die;
            
           
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'qlxPay');
            echo 'fail';
            die;
        }
    }
      //24支付异步通知
    public function esPay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'esPay');
                echo 'fail';
                die;
            }
            #支付结果
            if($data['trade_status'] =='TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'esPay');
                    echo 'fail';
                    die;
                }
                echo "success";
                die;
            }
            echo 'fail';
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'esPay');
            echo 'fail';
            die;
        }
    }
     //SY支付异步通知
    public function syPay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'syPay');
                echo 'fail';
                die;
            }
            #支付结果
            if($data['trade_status'] =='TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'syPay');
                    echo 'fail';
                    die;
                }
                echo "success";
                die;
            }
            echo 'fail';
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'syPay');
            echo 'fail';
            die;
        }
    }
     //百合支付异步通知
    public function bhPay()
    {
        try {
            $data = $this->request->param();
           
            if (empty($data)) {
                echo 'fail';
                die;
            }
            
            unset($data['acname']);
            // {"key":"b449a58ce0d9931576bae7f417a17dd8","money":"1.00","amount":"1.00","order":"20220519023207165289872780934","record":"20220519023123151157580","remark":"20220519023123151157580","sign":"66b9f11e7f3a17ef7699cb9964d3c779"}
            #获取支付接口
            $ordno = $data['ordernumber'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            
            //             参数名称	变量	类型	描述
            // 您的订单号	ordernumber	（string）	您创建支付订单时传过来的订单号
            // 金额
            // number	  (int)	金额
            // 时间
            // pay_time	  (int)	支付时间戳
            // 充值订单号	trade_no	（string）	用户充值订单号
            // 随机字符串
            // nonce_str	（string）	随机生成的字符串 用于验证
            // sign凭证	sign	（string）	
            // 验证规则 
            
            ksort($data);
            $buff="";
            foreach($data as $k => $v){
                if($k != "sign" && $v != "" && !is_array($v)){
                    $buff .= $k . "=" . $v . "&";
                }
            }
            $buff = trim($buff, "&");
            //签名步骤二：在string后加入KEY
            $string=$buff."&key=".$payInfo->app_key;
            //签名步骤三：MD5加密
            $string=md5($string);
            //签名步骤四：所有字符转为大写
            $sign=strtoupper($string);
            
            if($sign != $data['sign']){
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'bhPay');
                echo 'fail';
                exit('fail');
            }    
           
            #支付结果
            $data['out_trade_no'] = $data['transaction_id'] = $ordno;
            $data['money'] = $data['number'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'bhPay');
                echo 'fail';
                die;
            }
            echo "success";
            die;
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'bhPay');
            echo 'fail';
            die;
        }
    }
    // tmPay
     //天猫支付异步通知
    public function tmPay()
    {
        try {
            $data = $this->request->param();
           
            if (empty($data)) {
                echo 'fail2';
                die;
            }
            unset($data['acname']);
            // {"mch_code": "20001","order": "tst0001","plat_order": "APTest001","status": "SUCCESS","money": "10.00","plat_sign_type": "MD5","fee": "0.01","msg": "交易成功","plat_sign": "767c3ac27fa6aff7c31f6efc3cfbab03"
                
            //     }
            // {"amount":"5.00","charset":"utf-8","fee":"1.19","format":"JSON","mch_code":"180215","msg":"SUCCESS","out_trade_no":"202206081624023826621","plat_order_no":"TM60220608162402672965","plat_sign":"6615a14aa27c25679bbcf74f26e0c460","plat_sign_type":"MD5","status":"SUCCESS"}
            #获取支付接口
            
            $ordno = $data['out_trade_no'];
            
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
           
            ksort($data);
            
            $signPars = '';
            foreach ($data as $k => $v) {
                if ("plat_sign" != $k && "" != $v) {
                    $signPars .= $k . "=" . $v . "&";
                }
            }
            
            $signPars .= 'key='.$payInfo->app_key;
            $sign=md5($signPars);
         
            if($sign != $data['plat_sign']){
                doSyslog($sign . '#' . $data['plat_sign'] . '@' . json_encode($data).'sign验证失败', 'tmPay');
                echo 'fail';
                exit('fail');
            }    
           
            #支付结果
            if($data['status'] =='SUCCESS'){
                $data['transaction_id'] = $ordno;
                $data['money'] = $data['amount'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'tmPay');
                    echo 'fail';
                    die;
                }
                echo "SUCCESS";
                die;
            }
            echo 'fail';
            die;
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data).'参数', 'tmPay');
            echo 'fail1';
            die;
        }
    }
     //永昌支付异步通知
    public function ycPay()
    {
        try {
            $data = $this->request->param();
           
            if (empty($data)) {
                echo 'fail';
                die;
            }
            // {"key":"b449a58ce0d9931576bae7f417a17dd8","money":"1.00","amount":"1.00","order":"20220519023207165289872780934","record":"20220519023123151157580","remark":"20220519023123151157580","sign":"66b9f11e7f3a17ef7699cb9964d3c779"}
            #获取支付接口
            $ordno = $data['record'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            if ($payInfo->app_key != $data['key']) {
                //exit('fail');
            }
            
            $sdata = [
                'api_id' => $payInfo->app_id,
                'record' => $data['record'],
                'money' => sprintf("%.2f", $data['money'])
            ];
            ksort($sdata);
            $str = '';
            foreach ($sdata as $k => $v) {
                $str .= '&' . $k . "=" . $v;
            }
            $sign = md5(trim($str) . $payInfo->app_key);
            if($sign != $data['sign']){
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'yclPay');
                //echo 'fail';
                //exit('fail');
            }    
           
            #支付结果
            $data['out_trade_no'] = $data['record'];
           
            list($res, $info) = $this->handleOrder($data['record']);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'yclPay');
                echo 'fail';
                die;
            }
            echo "success";
            die;
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'yclPay');
            echo 'fail';
            die;
        }
    }
    
     //约德尔支付异步通知
    public function ydePay()
    {
       
        // {"acname":"ydePay","mchid":"2c918082812a65bf01812b8b371548fa","out_trade_no":"202206051120576232538","sign":"34E5C41B8DD240E4170C4F756BB1F699","total_fee":"1.00","trade_no":"231df5b8d80148c8aef284b01af352c3"}
        try {
          
            $data = $this->request->param();
            
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
           
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            ksort($data);
            reset($data);
            $fieldString = [];
            foreach ($data as $key => $value) {
                if(!empty($value)&&$key!='sign'){
                    $fieldString [] = $key . "=" . $value . "";
                 }
            }
            $fieldString = implode('&', $fieldString);
            $sign = strtoupper(md5($fieldString.$payInfo->app_key));
            
            if($sign!=$data['sign']){
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data).'ydePay3', 'ydePay');
                echo 'fail1';
                die;
            }
            #支付结果
            $data['money'] = $data['total_fee'];
            $data['transaction_id'] = $data['out_trade_no'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data).'ydePay3', 'ydePay');
                echo 'fail2';
                die;
            }
            echo "success";
            die;
        } catch (Exception $e) {
            echo $e->getMessage();
            doSyslog($e->getMessage() . '@' . json_encode($data).'ydePay3', 'ydePay');
            echo 'fail3';
            die;
        }
    }
     //大波浪支付异步通知
    public function dblPay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'dblPay');
                echo 'fail';
                die;
            }
            #支付结果
            if($data['trade_status'] =='TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'dblPay');
                    echo 'fail';
                    die;
                }
                echo "success";
                die;
            }
            echo 'fail';
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'dblPay');
            echo 'fail';
            die;
        }
    }
    //七淘（个码）微信
    public function qtwPay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'qtwPay');
                echo 'fail';
                die;
            }
            #支付结果
            if($data['trade_status'] =='TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'qtwPay');
                    echo 'fail';
                    die;
                }
                echo "success";
                die;
            }
            echo 'fail';
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'qtwPay');
            echo 'fail';
            die;
        }
    }
    
    //码支付微信异步
    public function zfnPay()
    {
        try {
            #获取数据
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            #获取支付接口
            $ordno = $data['payId'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = md5($payInfo->app_id.$data['payId'].$data['param'].$data['type'].$data['price'].$data['reallyPrice'].$payInfo->app_key);

            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'zfnPay');
                echo 'fail';
                die;
            }
            #支付结果
            $data['transaction_id'] = $data['payId'];
            $data['money'] = $data['price'];
            $data['out_trade_no'] = $data['payId'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'zfnPay');
                echo 'fail';
                die;
            }
            echo "success";
            die;
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'zfnPay');
            echo 'fail';
            die;
        }
    }

    //码支付支付宝通知
    public function codePay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            #获取支付接口
            $ordno = $data['payId'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = md5($payInfo->app_id.$data['payId'].$data['param'].$data['type'].$data['price'].$data['reallyPrice'].$payInfo->app_key);

            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'codePay');
                echo 'fail';
                die;
            }
            #支付结果
            $data['transaction_id'] = $data['payId'];
            $data['money'] = $data['price'];
            $data['out_trade_no'] = $data['payId'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'codePay');
                echo 'fail';
                die;
            }
            echo "success";
            die;
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'codePay');
            echo 'fail';
            die;
        }
    }
    //招财猫（内付）微信
    public function zcmPay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'codePay');
                echo 'fail';
                die;
            }
            #支付结果
            if($data['trade_status'] =='TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'codePay');
                    echo 'fail';
                    die;
                }
                echo "success";
                die;
            }
            echo 'fail';
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'codePay');
            echo 'fail';
            die;
        }
    }
    //星火易支付异步通知
    public function xhyPay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            unset($data['acname']);
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'xhyPay');
                echo 'fail';
                die;
            }
            #支付结果
            if($data['trade_status'] =='TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'xhyPay');
                    echo 'fail';
                    die;
                }
                echo "success";
                die;
            }
            echo 'fail';
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'xhyPay');
            echo 'fail';
            die;
        }
    }
    //码支付未知微信异步
    public function wwxPay()
    {
        try {
            //if($this->request->isPost()){
            #获取数据
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $order = \app\common\model\Order::where('ordno', $ordno)->find();
            #支付结果
            $data['money'] = $order->money;
            $data['transaction_id'] = empty($data['trade_no'])?'':$data['trade_no'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'wwxPay');
                echo 'fail';
                die;
            }
            echo "success";
            die;
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'wwxPay');
            echo 'fail';
            die;
        }
    }

    //微信原生支付
    public function wechatPay()
    {
        try {
            $wechat = new WxPay();
            list($res, $notify_info) = $wechat->notifyData();
            if (!$res) {
                doSyslog($notify_info['msg'],'wechatPay');
                echo 'fail';
                exit;
            }
            #验证签名
            $ordno = $notify_info->out_trade_no;
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            
            $sign = $wechat->getSign(json_decode(json_encode($notify_info),true), $payInfo->app_key);
            if ($sign !== $notify_info->sign) {
                doSyslog($notify_info->sign . '@' . $sign . '@' . json_encode($notify_info), 'wechatPay');
                echo 'fail';
                exit;
            }
            #支付结果
            $datas = [
                'transaction_id' => $notify_info->transaction_id,
                'out_trade_no' => $notify_info->out_trade_no,
                'money' => floatval($notify_info->total_fee / 100)
            ];
            list($res, $info) = $this->handleOrder($datas);
            if (!$res) {
                doSyslog($info . '@' . json_encode($notify_info), 'wechatPay');
                echo 'fail';
                exit;
            }
            $data = ['return_code' => 'SUCCESS', 'return_msg' => 'OK'];
            echo $wechat->arrayToXml($data);
            exit;
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($notify_info), 'wechatPay');
            echo 'fail';
            die;
        }
    }
    public function gbwPay(){
        try {
        
            $res = file_get_contents('php://input');
            $res = json_decode($res,true);
            $data = [
                'amount'=>$res['amount'],
                'merchantOrderCode'=>$res['merchantOrderCode'],
                'platformOrderCode'=>$res['platformOrderCode'],
                'sign'=>$res['sign'],
                'status'=>$res['status']
                ];
            if (empty($data)) {
                echo 'fail';
                die;
            }
           
            #获取支付接口
            $pay_id = \app\common\model\Order::where('ordno', $data['merchantOrderCode'])->value('pay_id');
           
            $payInfo = PaySetting::where('id', $pay_id)->find();
            //组合签名  
            $str = '';
            foreach ($data as $key => $value){
                if ($key !== 'sign'){
                    $str .= $key . '=' . $value;
                }
              
            }
            $str .= 'key=' . $payInfo->app_key;
            $sign = md5($str);
            
            if($sign!=$data['sign']){
                exit('FAIL');
            }
            
            if ($data['status'] == '3') {
                #支付结果
                $send = [
                    'money'=>$data['amount'] / 100,
                    'transaction_id'=>$data['merchantOrderCode'],
                    'out_trade_no'=>$data['merchantOrderCode']
                    ];
               
                list($res, $info) = $this->handleOrder($send);
              
                if (!$res) {
                    doSyslog($info . '@' . json_encode($send), 'gbwPay');
                    exit('FAIL');
                }
                exit("SUCCESS");
            }else{
                exit('FAIL');
            }
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'gbwPay');
            echo 'FAIL';
            die;
        }
    }
    //嘻唰唰
    public function xssPay()
    {
        try {
            //if($this->request->isPost()){
            #获取数据
            $data = $this->request->param();
            if (empty($data)) {
                echo 'fail';
                die;
            }
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'xssPay');
                exit('fail');
            }
            if ($data['trade_status'] == 'TRADE_SUCCESS') {
                #支付结果
                $data['transaction_id'] = $data['trade_no'];
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'xssPay');
                    exit('fail');
                }
                exit("success");
            }
            exit('fail');
            //}
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'xssPay');
            echo 'fail';
            die;
        }
    }


    //云尔易付异步通知
    public function yuanerPay()
    {
        try {
            if ($this->request->isPost()) {
                #获取数据
                $data = $this->request->post();
                if (empty($data)) {
                    echo 'fail';
                    die;
                }
                #获取支付接口
                $ordno = $data['out_trade_no'];
                $orderInfo = \app\common\model\Order::where('ordno', $ordno)->find();
                $payInfo = PaySetting::where('id', $orderInfo->pay_id)->find();
                #组合签名
                $sign = '';
                ksort($data);
                foreach ($data as $k => $v) {
                    if ($v && $k !== 'sign') $sign .= $k . '=' . $v . '&';
                }
                $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
                if ($sign !== $data['sign']) {
                    doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'yuanerPay');
                    echo 'fail';
                    die;
                }
                #支付结果
                if ($data['status'] == 1) {
                    list($res, $info) = $this->handleOrder($data);
                    if (!$res) {
                        doSyslog($info . '@' . json_encode($data), 'yuanerPay');
                    }
                    echo "success";
                    die;
                }
                echo "fail";
                die;
            }
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'yuanerPay');
            echo 'fail';
            die;
        }
    }

    /**
     * 处理订单
     */
    protected function handleOrder($data)
    {
        
        try {
           
            $orderInfo = \app\common\model\Order::where('ordno', $data['out_trade_no'])->find();
           
            if ($orderInfo->status == 1) {
                return [true, '已支付成功'];
            }
            
            $user = Admin::where('id', $orderInfo->uid)->find();
            $uid = $orderInfo->uid;
            $is_kl = 0;
            #有上级代理
            if ($user->admin_id > 0) {
                #扣量比例
                $take_num = $user->take_num;
                if ($take_num > 0) {
                    $count = (new \app\common\model\Order())->where(['uid' => $orderInfo->uid, 'status' => 1])->count();
                    if ($count > 0 && ($count + 1) % $take_num == 0) {
                        $is_kl = 1;
                    }
                }
            }
            //优化逻辑扣量
            if ($is_kl == 1) {
                //扣量逻辑
                if ($user['admin_id'] == 0) {
                    $uid = $user['id'];
                } else {
                    $uid = $orderInfo['pid_top'];//todo 获取总代理即管理员ID
                }
            }
            //计算提成
            $min_take = $user['min_take'];
            $money = $data['money'];
            $take_money = 0;
            if ($min_take > 0 && $is_kl == 0 && $user->admin_id > 0) {
                $take_money = ($data['money'] * $min_take) / 100;
                if ($take_money) {
                    // $money = $data['money'] - $take_money;
                    $money = $data['money'];
                }
            }
            Db::startTrans();
            #扣量
            if ($is_kl == 1) {
                $remark = "【扣量订单】单号:{$orderInfo->ordno} 代理ID:" . $orderInfo->uid . " 代理名称:" . $user->username;
                $type = 4;
            } else {
                $remark = '【打赏收入】单号:' . $orderInfo->ordno;
                $type = 1;
            }
            list($res, $info) = Bill::money(1, $type, $money, $uid, $remark, $orderInfo->id);
            if (!$res) {
                doSyslog($info . '-打赏收入@' . json_encode($data), 'handleOrder');
                return [false, $info];
            }
            #提成
            if ($take_money && $is_kl == 0) {
                $remark = "【分销抽成】单号:{$orderInfo->ordno};提成抽取比例{$user->min_take}%;代理【{$user->username}】ID:{$user->id}";
                list($res, $info) = Bill::money(1, 3, $take_money, $user->admin_id, $remark, $orderInfo->id);
                if (!$res) {
                    doSyslog($info . '-分销抽成@' . json_encode($data), 'handleOrder');
                    Db::rollback();
                    return [false, $info];
                }
            }
            #缓存
            $key = "success_order_{$uid}_" . date('Y-m-d');
            if ($is_kl == 1) {
                $key = "success_order_1_" . date('Y-m-d');
            }
            cache($key, [time(), $orderInfo->ordno]);

            $res = (new \app\common\model\Order())->save([
                'trade_id' => $data['transaction_id'],
                'status' => 1,
                'ptime' => time(),
                'tc_money' => $take_money,
                'is_kl' => $is_kl
            ], ['ordno' => $orderInfo->ordno]);
            if (!$res) {
                doSyslog('订单更新失败@' . json_encode($data), 'handleOrder');
                Db::rollback();
                return [false, '订单更新失败'];
            }
            $expire = time() + 86400;
            if ($orderInfo->type == 2) {
                $expire = time() + 86400;
            } elseif ($orderInfo->type == 3) {
                $expire = time() + (86400 * 7);
            } elseif ($orderInfo->type == 4) {
                $expire = time() + (86400 * 30);
            }
            $res = (new PayShow())->save([
                'v_id' => $orderInfo->v_id,
                'uid' => $orderInfo->uid,
                'ip' => $orderInfo->ip,
                'ordno' => $orderInfo->ordno,
                'ua' => $orderInfo->ua,
                'etime' => $expire,
                'is_month' => $orderInfo->type == 4 ? 1 : 0,
                'is_week' => $orderInfo->type == 3 ? 1 : 0,
                'is_day' => $orderInfo->type == 2 ? 1 : 0,
                'ctime' => time()
            ]);
            if (!$res) {
                doSyslog('更新已支付记录失败@' . json_encode($data), 'handleOrder');
                Db::rollback();
                return [false, '更新已支付记录失败'];
            }
            Db::commit();
            return [true, '订单处理完成'];
        } catch (Exception $e) {
            doSyslog($e->getMessage() . json_encode($data), 'handleOrder');
            return [false, '订单处理失败'];
        }
    }
    
    
   
}