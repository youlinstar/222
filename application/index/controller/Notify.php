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
use think\Controller;
use Think\Db;
use think\Exception;


class Notify extends Controller
{
    /**
     * 订单受理
     * @param $data
     * @param 【money 金额】 【out_trade_no 平台单号】 【transaction_id 三方单号】
     * @return array
     */
    protected function handleOrder($data): array
    {
        try {

            $orderInfo = \app\common\model\Order::where('ordno', $data['out_trade_no'])->find();
            if($data['money'] != $orderInfo->money){
                doSyslog('回调金额与订单金额不一致' . json_encode($data), 'handleOrder');
                return [false, '订单处理失败'];
            }
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
            $money = $orderInfo->money;
            $take_money = 0;
            if ($min_take > 0 && $is_kl == 0 && $user->admin_id > 0) {
                $take_money = ($orderInfo->money * $min_take) / 100;
                if ($take_money) {
                    // $money = $data['money'] - $take_money;
                    $money = $orderInfo->money;
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
     * 纵横支付异步通知
     * @return void
     */
    public function zhPay()
    {
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
        #todo 签名规则 #
        #   1)	将接口中的请求字段按照Ascii码方式进行升序排序
        #   2)	按照key1=val1&key2=val2&key3=val3....&key=md5秘钥生成加密字符串
        #   3)	将上一步生成的字符串进行MD5加密，并转换成大写
        #################################################################

        try {

            $data = $this->request->param();

            if (empty($data)) {
                exit('数据获取失败');
            }

            #获取支付接口
            $ordno = $data['orderid'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            $appKey = $payInfo->app_key;
            if($data['api_id'] != $pay_id)
            {
                doSyslog('商户ID错误:' . $pay_id . '@' . json_encode($data), 'zhPay');
                exit('商户ID错误');
            }
            #组合签名
            ksort($data);
            reset($data);
            $str = '';
            foreach ($data as $k => $v) {
                if ($v == '' || $k == 'sign') {
                    continue;
                }
                $str .= $k . "=" . $v . '&';
            }
            $sign = strtoupper(md5(trim($str) . $appKey));//md5加密参数

            if($sign!=$data['sign']){
                doSyslog('签名错误：' . $sign . '#' . $data['sign'] . '@' . json_encode($data).'zhPay', 'zhPay');

            }
            #支付结果
            $param = [
                'transaction_id'=>$data['api_orderid'],//TODO 三方订单号
                'out_trade_no'=>$data['orderid'],//TODO 平台订单号
                'money'=>$data['money']
            ];
            list($res, $info) = $this->handleOrder($param);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data).'zhPay', 'zhPay');
                exit('订单受理失败');
            }
            exit('success');
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data).'zhPay', 'zhPay');
            exit('异常');
        }
    }

    /**
     * 永昌支付异步通知
     * @return void
     */
    public function ycPay()
    {
        #########################################################################################################
                            #异步通知API（POST/GET)#
        #########################################################################################################
        // 字段名称         字段类型            字段说明
        // key              string              商户密匙KEY（由支付平台返回给回调地址判断）
        // money            float（2）          金额（注意：php使用 sprintf("%.2f",金额)  接收此参数）
        // order            string              支付平台创建的订单号
        // record           string              附加参数（发起支付传递的您网站的订单号或用户名等唯一参数）
        // sign             string              数据签名（签名方法见下文）
        #########################################################################################################
        // {"key":"b449a58ce0d9931576bae7f417a17dd8","money":"1.00","amount":"1.00","order":"20220519023207165289872780934","record":"20220519023123151157580","remark":"20220519023123151157580","sign":"66b9f11e7f3a17ef7699cb9964d3c779"}
        #########################################################################################################
        try {
            $data = $this->request->param();

            if (empty($data)) {
                echo 'fail';
                die;
            }

            #获取支付接口
            $ordno = $data['remark'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            if ($payInfo->app_key != $data['key']) {
                exit('fail');
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
                echo 'fail';
                exit('fail');
            }

            #支付结果
            $param = [
                'money'=>sprintf("%.2f", $data['money']),
                'transaction_id'=>$data['order'],
                'out_trade_no'=>$data['record']
            ];

            list($res, $info) = $this->handleOrder($param);

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

    /**
     * 禄恒支付异步通知
     * @return void
     */
    public function lhPay()
    {
        ###################################################################################################
        // 字段名	变量名	必填	类型	示例值	描述
        // 支付订单号	payOrderId	是	String(30)	P20160427210604000490	支付中心生成的订单号
        // 商户ID	mchId	是	String(30)	20001222	支付中心分配的商户号
        // 应用ID	appId	否	String(30)	cbsgB1T0SL6tfflFYoBX	商户应用ID
        // 支付产品ID	productId	是	String(24)	8001
        // 商户订单号	mchOrderNo	是	String(30)	20160427210604000490	商户生成的订单号
        // 支付金额	amount	是	int	100	请求支付下单时金额,单位分
        // 付款金额	income	是	int	100	用户实际付款的金额,单位分
        // 状态	status	是	int	1	支付状态,-2:订单已关闭,0-订单生成,1-支付中,2-支付成功,3-业务处理完成,4-已退款（2和3都表示支付成功,3表示支付平台回调商户且返回成功后的状态）
        // 渠道订单号	channelOrderNo	否	String(64)	wx2016081611532915ae15beab0167893571	三方支付渠道订单号
        // 扩展参数2	param2	否	String(64)		支付中心回调时会原样返回
        // 支付成功时间	paySuccTime	是	long		精确到毫秒
        // 通知类型	backType	是	int	1	通知类型，1-前台通知，2-后台通知
        // 通知请求时间	reqTime	是	String(30)	20190723141000	通知请求时间，yyyyMMddHHmmss格式
        // 签名	sign	是	String(32)	C380BEC2BFD727A4B6845133519F3AD6	签名值，详见签名算法

        ###################################################################################################
        try {

            $data = $this->request->param();

            if (empty($data)) {
                doSyslog('数据获取失败', 'lhPay');
                exit('数据获取失败');
            }
            unset($data['acname']);
            #获取支付接口
            $pay_id = \app\common\model\Order::where('ordno', $data['mchOrderNo'])->value('pay_id');

            $payInfo = PaySetting::where('id', $pay_id)->find();
            //组合签名
            ksort($data);  //字典排序
		    reset($data);
            $str = '';
            foreach ($data as $k => $v) {
    			if( strlen($k)  && strlen($v) && $k !== 'sign' && !empty($v)){
    				$str = $str . $k . "=" . $v . "&";
    			}
    		}

    		$sign = strtoupper(md5($str . "key=" . $payInfo->app_key));  //签名

            if($sign!=$data['sign']){
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'lhPay');
                exit('fail');
            }
            #支付结果
            if ($data['status'] != '2') {
                exit('fail');
            }

            $send = [
                'money'=>$data['amount'] / 100,
                'transaction_id'=>$data['payOrderId'],
                'out_trade_no'=>$data['mchOrderNo']
            ];

            list($res, $info) = $this->handleOrder($send);

            if (!$res) {
                doSyslog($info . '@' . json_encode($send), 'lhPay');
                exit('fail');
            }
            exit("success");
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'lhPay');
            echo 'fail';
            die;
        }
    }

    /**
     * 金蝶支付异步通知
     * @return void
     */
    public function jdPay(){
        try {

            $res = file_get_contents('php://input');
            if (empty($res)) {
                doSyslog('数据获取失败', 'jdPAY');
                exit('数据获取失败');
            }
            $res = json_decode($res,true);

            $data = [
                'amount'=>$res['amount'],
                'merchantOrderCode'=>$res['merchantOrderCode'],//平台订单号
                'platformOrderCode'=>$res['platformOrderCode'],//三方订单号
                'sign'=>$res['sign'],
                'status'=>$res['status']
            ];

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
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'jdPay');
                exit('FAIL');
            }
            #支付结果
            if ($data['status'] != '3') {
                exit('FAIL');
            }

            $send = [
                'money'=>$data['amount'] / 100,
                'transaction_id'=>$data['platformOrderCode'],
                'out_trade_no'=>$data['merchantOrderCode']
            ];

            list($res, $info) = $this->handleOrder($send);

            if (!$res) {
                doSyslog($info . '@' . json_encode($send), 'jdPay');
                exit('FAIL');
            }
            exit("SUCCESS");
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($res), 'jdPay');
            echo 'FAIL';
            die;
        }
    }

    /**
     * 飞机H5通道【约德尔子通道】 支付异步通知
     * @return void
     */
    public function fjPay()
    {

        // {"acname":"ydePay","mchid":"2c918082812a65bf01812b8b371548fa","out_trade_no":"202206051120576232538","sign":"34E5C41B8DD240E4170C4F756BB1F699","total_fee":"1.00","trade_no":"231df5b8d80148c8aef284b01af352c3"}
        try {

            $data = $this->request->param();

            if (empty($data)) {
                exit('数据获取失败');
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
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data).'xmlPay', 'fjPay');
                exit('签名错误');
            }
            #支付结果
            $data['money'] = $data['total_fee'];
            $data['transaction_id'] = $data['trade_no'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data).'xmlPay', 'fjPay');
                exit('订单受理失败');
            }
            exit('success');
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data).'xmlPay', 'fjPay');
            exit('异常');
        }
    }

    #####todo ============================易支付类================================================#####

    /**
     * 小鸡【支付宝】异步通知【原码支付】
     * @return void
     */
    public function xjpay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                exit('数据获取失败');
            }
            #获取支付接口
            $ordno = $data['payId'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            $sign = md5($payInfo->app_id.$data['payId'].$data['param'].$data['type'].$data['price'].$data['reallyPrice'].$payInfo->app_key);

            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'xjpay');
                exit('签名错误');
            }
            #支付结果
            $data['transaction_id'] = $data['payId'];
            $data['money'] = $data['price'];
            $data['out_trade_no'] = $data['payId'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'xjpay');
                exit('订单受理失败');
            }
            exit('success');
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'xjpay');
            exit('异常');
        }
    }

    /**
     * 大猪蹄子支付异步通知 【大波浪】
     * @return void
     */
    public function dztzPay()
    {
        #  todo ==================================订单回调信息======================================================
        #  字段名	    变量名	        必填	    类型	    示例值                    描述
        #  商户ID	    pid	            是	    Int	    1001
        #  易支付订单号	trade_no	    是	    String	20160806151343349021	G63支付订单号
        #  商户订单号	    out_trade_no	是	    String	20160806151343349	    商户系统内部的订单号
        #  支付方式	    type	        是	    String	alipay	                alipay:支付宝,tenpay:财付通,qqpay:QQ钱包,wxpay:微信支付, alipaycode:支付宝扫码,jdpay:京东支付
        #  商品名称	    name	        是	    String	VIP会员
        #  商品金额	    money	        是	    String	1.00
        #  支付状态	    trade_status	是	    String	TRADE_SUCCESS
        #  签名字符串	    sign	        是	    String	202cb962ac59075b964b07152d234b70	签名算法与支付宝签名算法相同
        #  签名类型	    sign_type	    是	    String	MD5	默认为MD5
        #  todo ==================================订单回调信息======================================================
        try {
            $data = $this->request->param();
            if (empty($data)) {
                exit('数据获取失败');
            }
            #todo 获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #todo 组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            #todo 验签
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'dztzPay');
                //exit('签名验证失败');
            }
            #todo 验证支付
            if($data['trade_status'] == 'TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                # todo 订单受理
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'dztzPay');
                    exit('订单受理失败');
                }
                exit('success');
            }
            exit('订单未支付');

        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'dztzPay');
            exit('fail');
        }
    }

    /**
     * 高希霸支付异步通知
     * @return void
     */
    public function gxbPay()
    {

        try {
            $data = $this->request->param();

            if (empty($data)) {
                exit('数据获取失败');
            }
            unset($data['acname']);

            #todo 获取通道信息
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();

            #todo 组合参数
            $param = [
                'mchid'=>$data['mchid'],
                'total_fee'=>$data['total_fee'],
                'out_trade_no'=>$data['out_trade_no'],
                'trade_no'=>$data['trade_no']
            ];

            #todo 组合签名
            $str = '';
            ksort($param);
            reset($param);
            foreach ($param as $k => $v) {
                $str .= $k . '=' . $v . '&';
            }
            $str = rtrim($str, '&');
            $str .= $payInfo->app_key;
            $sign = strtoupper(hash('md5', $str));

            #todo 验签
            if($sign!=$data['sign']){
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'gxbPay');
                exit('签名错误');
            }

            #todo 订单受理
            $data['money'] = $data['total_fee'] / 100;
            $data['transaction_id'] = $data['trade_no'];
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'gxbPay');
                exit('订单受理失败');
            }
            exit('success');
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'gxbPay');
            exit('异常');
        }
    }

    /**
     * G63云支付异步通知
     * @return void
     */
    public function glsPay()
    {
        #  todo ==================================订单回调信息======================================================
        #  字段名	    变量名	        必填	    类型	    示例值                    描述
        #  商户ID	    pid	            是	    Int	    1001
        #  易支付订单号	trade_no	    是	    String	20160806151343349021	G63支付订单号
        #  商户订单号	    out_trade_no	是	    String	20160806151343349	    商户系统内部的订单号
        #  支付方式	    type	        是	    String	alipay	                alipay:支付宝,tenpay:财付通,qqpay:QQ钱包,wxpay:微信支付, alipaycode:支付宝扫码,jdpay:京东支付
        #  商品名称	    name	        是	    String	VIP会员
        #  商品金额	    money	        是	    String	1.00
        #  支付状态	    trade_status	是	    String	TRADE_SUCCESS
        #  签名字符串	    sign	        是	    String	202cb962ac59075b964b07152d234b70	签名算法与支付宝签名算法相同
        #  签名类型	    sign_type	    是	    String	MD5	默认为MD5
        #  todo ==================================订单回调信息======================================================
        try {
            $data = $this->request->param();
            if (empty($data)) {
                exit('数据获取失败');
            }
            #todo 获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #todo 组合签名
            $sign = '';
            ksort($data);
            foreach ($data as $k => $v) {
                if ($v && $k !== 'sign' && $k !== 'sign_type') $sign .= $k . '=' . $v . '&';
            }
            $sign = md5(rtrim($sign, '&') . $payInfo->app_key);
            #todo 验签
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'glsPay');
                exit('签名验证失败');
            }
            #todo 验证支付
            if($data['trade_status'] == 'TRADE_SUCCESS'){
                $data['transaction_id'] = $data['trade_no'];
                # todo 订单受理
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'glsPay');
                    exit('订单受理失败');
                }
                exit('success');
            }
            exit('订单未支付');

        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'glsPay');
            exit('fail');
        }
    }

    /**
     * 凯撒支付异步通知
     * @return void
     */
    public function ksPay()
    {
        try {
            $data = $this->request->param();
            if (empty($data)) {
                exit('数据获取失败');
            }
            // {"sign":"4FF4758AA87206F0A7DB0D6F6766D827","money":"4","trade_no":"4200001420202206247237786844","out_trade_no":"202206242200437144630","name":"VIP\\u4f1a\\u5458","pid":"137","type":"jsapi"}',
            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            ksort($data);
            #组合签名
            $str ="";
            foreach ($data as $k=>$v){
                if ($k != "" && $v != "" && "sign" != $k) {
                    $str .= $k . "=" . $v . "&";
                }
            }
            $sign = strtoupper(md5($str."key=".$payInfo->app_key));
            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'ksPay');
                exit('签名验证失败');
            }
            #支付结果

            $data['transaction_id'] = $data['trade_no'];
            $data['money'] = $data['money'] / 100;
            list($res, $info) = $this->handleOrder($data);
            if (!$res) {
                doSyslog($info . '@' . json_encode($data), 'ksPay');
                exit('订单受理失败');
            }
            exit('success');

        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'ksPay');
            exit('异常');
        }
    }

    /**
     * 逍遥支付异步通知
     * @return void
     */
    public function xyPay()
    {
        #todo   协议规则    传输方式：HTTP   数据格式：JSON   签名算法：MD5    字符编码：UTF-8
        #字段名	        变量名	        必填	    类型	        示例值	                描述
        #商户ID	        pid	            是	    Int	        1001
        #易支付订单号	    trade_no	    是	    String	    20160806151343349021	荷花支付订单号
        #商户订单号	    out_trade_no	是	    String	    20160806151343349	    商户系统内部的订单号
        #支付方式	    type	        是	    String	    alipay	                支付方式列表
        #商品名称	    name	        是	    String	    VIP会员
        #商品金额	    money	        是	    String	    1.00
        #支付状态	    trade_status	是	    String	    TRADE_SUCCESS	        只有TRADE_SUCCESS是成功
        #业务扩展参数	    param	        否	    String
        #签名字符串	    sign	        是	    String	    202cb962ac59075b964b07152d234b70	签名算法与支付宝签名算法相同
        #签名类型	    sign_type	    是	    String	    MD5	默认为MD5
        // {"sign":"4FF4758AA87206F0A7DB0D6F6766D827",
        //"money":"4","trade_no":"4200001420202206247237786844",
        //"out_trade_no":"202206242200437144630",
        //"name":"VIP\\u4f1a\\u5458","pid":"137","type":"alipay"}',

        try {
            $data = $this->request->param();
            
            if (empty($data)) {
                exit('数据获取失败');
            }


            unset($data['acname']);

            #获取支付接口
            $ordno = $data['out_trade_no'];
            $pay_id = \app\common\model\Order::where('ordno', $ordno)->value('pay_id');
            $payInfo = PaySetting::where('id', $pay_id)->find();
            #组合签名
            ksort($data);
            $str = "";
            foreach ($data as $k => $v) {
                if ("sign" != $k && "" != $v && $k != 'param' && $k != 'sign_type' && $k !="sign_type") {
                    $str .= $k . "=" . $v . "&";
                }
            }
            $str = rtrim($str, '&');
            $str .= $payInfo->app_key;
            $sign = md5($str);

            if ($sign !== $data['sign']) {
                doSyslog($sign . '#' . $data['sign'] . '@' . json_encode($data), 'xyPay');
                exit('签名验证失败');
            }
            #支付结果
            $data['transaction_id'] = $data['trade_no'];
            $data['money'] = $data['money'];
            #todo 验证支付
            if($data['trade_status'] == 'TRADE_SUCCESS') {
                list($res, $info) = $this->handleOrder($data);
                if (!$res) {
                    doSyslog($info . '@' . json_encode($data), 'xyPay');
                    exit('订单受理失败');
                }
                exit('success');
            }
            exit('订单未支付');
        } catch (Exception $e) {
            doSyslog($e->getMessage() . '@' . json_encode($data), 'xyPay');
            exit('异常');
        }
    }

    #####todo ============================易支付类结束================================================#####


}
