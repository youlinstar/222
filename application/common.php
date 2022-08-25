<?php


/**
 * 微信域名检测
 * @param $url string 检测url
 * @return array 返回数组
 */
function checkDomain($url)
{
    try {
        $api_url = 'http://mp.weixinbridge.com/mp/wapredirect?url=' . $url;
        $result = get_headers($api_url, 1);
        if (empty($result['Location'])) {
            return [400, '未检测到或检测失败1'];
        }
        if (empty($result['Location'][1])) {
            return [401, '未检测到或检测失败2'];
        }
        if ($result['Location'][1] == $url) {
            return [200, '域名正常'];
        }
        $querys = parse_url($result['Location'][1]);
        parse_str($querys['query'], $params);
        if ($params['main_type'] == 1) {
            return [402, '域名可点击跳转访问'];
        }
        if ($params['main_type'] == 2) {
            return [403, '域名已拦截'];
        }
        return [200, '未检测到域名'];
    } catch (\think\Exception $e) {
        return [500, $e->getMessage()];
    }
}

/**
 * 获取USER-AGENT
 */
function userAgent()
{
    $ua = request()->header('USER_AGENT');

    $ua = str_replace('WIFI', '', $ua);
     
    $ua = str_replace('4G', '', $ua);
    $ua = str_replace('2G', '', $ua);
    $ua = str_replace('5G', '', $ua);
     
    return md5($ua);
}

/**
 * AES加密
 * @param $str
 * @return string
 */
function encrypt($str)
{
    $data = openssl_encrypt($str, 'AES-128-ECB', config('setting.secret_key'), OPENSSL_RAW_DATA);
    $data = base64_encode($data);
    $data = str_replace(['+', '/', '='], ['o000o', 'oo00o', ''], $data);
    return $data;
}

/**
 * AES解密
 * @param $str
 * @return string
 */
function decrypt($str)
{
    $str = str_replace(['o000o', 'oo00o'], ['+', '/'], $str);
    $decrypted = openssl_decrypt(base64_decode($str), 'AES-128-ECB', config('setting.secret_key'), OPENSSL_RAW_DATA);
    return $decrypted;
}

/**
 * 获取防封链接 弃用
 * @param $type
 * @return mixed|string
 */
function getAntiUrl($type)
{
    $antiModle = new \app\common\model\Anti();
    $links = $antiModle->where(['type' => $type, 'status' => 1])->orderRand()->find();
    if (empty($links)) {
        return '';
    }
    return $links->link;
}
/**
 * 获取短链接
 * @param $short_id
 * @return mixed|string
 */
function getDwz($short , $url = ''){
   

}

/**
 * 获取域名配置
 * @param int $type 域名类型 1入口 2落地 3支付 4标识 5原生短链接入口 6、原生短链接中转 7、后台
 * @param int $uid 用户ID 默认为0
 * @return string
 */
function getDomain($type = 1, $uid = 0)
{
    
    $dominModel = new \app\common\model\Domain();
    if ($uid > 0) {

        # 获取用户绑定域名   

        $domain = $dominModel->where(['type' => $type, 'status' => 1,'is_bind' => 1,'uid'=>$uid])->orderRand()->find();
        
        if (empty($domain)) {            

            # 随机获取域名

            $domain = $dominModel->where(['type' => $type, 'status' => 1,'is_bind' => 0])->orderRand()->find();

            if (empty($domain)) {
                # 后期增加发送短信 进行提醒
                exit;
            }
        }
    } else {
        # 获取域名
        $domain = $dominModel->where(['type' => $type, 'status' => 1,'is_bind' => 0])->orderRand()->find();
        if (empty($domain)) {
            exit;
        }
    }
    # 是否启用域名前缀 并且是落地类型
    $https = $domain['is_ssl'] == 1 ? 'https://' : 'http://';
    
    # 落地域名前缀随机
    if (config('setting.pre_rand') && $type == 2) {
        $pre_domain = str_rand() . '.';
        return $https . $pre_domain . trim($domain['domain']);
    }
    return $https . trim($domain['domain']);
}

/**
 * 随机前缀
 * @param int $num
 * @return string
 */
function str_rand($num = 4)
{
    $rand_code = "";
    for ($i = 1; $i <= $num; $i++) {
        $rand_code .= chr(rand(97, 122));
    }
    return $rand_code;
}

/**
 *计算附近距离经纬度
 * @param  $latitude float    纬度
 * @param  $longitude float  经度
 * @param  $raidus   int   半径范围(单位：米)
 * @return array
 * 赤道周长24901英里 1609是转换成米的系数
 */
function getAround($latitude, $longitude, $raidus)
{
    $PI = 3.14159265;
    $degree = (24901 * 1609) / 360.0;
    $dpmLat = 1 / $degree;
    $radiusLat = $dpmLat * $raidus;
    $minLat = $latitude - $radiusLat;
    $maxLat = $latitude + $radiusLat;
    $mpdLng = $degree * cos($latitude * ($PI / 180));
    $dpmLng = 1 / $mpdLng;
    $radiusLng = $dpmLng * $raidus;
    $minLng = $longitude - $radiusLng;
    $maxLng = $longitude + $radiusLng;
    $nearbyData = array(
        'minLat' => $minLat,
        'maxLat' => $maxLat,
        'minLng' => $minLng,
        'maxLng' => $maxLng
    );
    return $nearbyData;
}

/*
 * 1.纬度1，经度1，纬度2，经度2
 * 2.返回结果是单位是米。
 * 3.保留一位小数
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    //将角度转为狐度
    $radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
    $radLat2 = deg2rad($lat2);

    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);

    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6371;
    return $s * 1000;
}

/*
*写入日志
*/
function doSyslog($content, $name)
{
    $config['type'] = 'file';
    $config['single'] = false;
    $config['apart_level'] = ['info'];
    $config['file_size'] = '2097152*10';
    $config['path'] = env('runtime_path') . 'log' . DIRECTORY_SEPARATOR . 'payLog' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
    think\facade\Log::init($config);
    if (gettype($content) != "array") {
        $log['info'] = [$content];
    } else {
        $log['info'] = $content;
    }
    think\facade\Log::write($log['info'], $name);
}

function formatCont($str, $len)
{
    $str = strip_tags($str);
    $str = trimall($str);
    $str = str_replace('&nbsp;', '', $str);
    $str = mb_substr($str, 0, $len);
    return $str;
}

/***
 * 判断是否json
 * @param $str
 * @return bool
 */
function checkJson($str)
{
    return is_null(json_decode($str)) ? false : true;
}

/**
 * 判断是否手机号
 * @param $mobile
 * @return bool
 */
function checkMobile($mobile = '')
{
    if (preg_match("/^1[3456789]{1}\d{9}$/", $mobile)) {
        return true;
    } else {
        return false;
    }
}

//请求返回
function callback($status = 400, $msg = '', $url = null, $data = '')
{
    $data = [
        'status' => $status,
        'msg' => $msg,
        'url' => $url,
        'data' => $data,
        'time' => date('Y-m-d H:i:s')
    ];
    return $data;
}

function getvalidate($info)
{
    $validate = '';
    if ($info['is_must']) $validate = 'required';
    if ($info['pattern']) $validate .= '|' . $info['pattern'];
    $errormsg = '';
    if ($info['msg']) {
        $errormsg = ' lay-reqText="' . $info['msg'] . '"';
    }
    $validate = 'lay-verify="' . $validate . '" ';
    $parseStr = $validate . $errormsg;
    return $parseStr;
}

function string2array($info)
{
    if ($info == '') return array();
    eval("\$r = $info;");
    return $r;
}

function array2string($info)
{
    if ($info == '') return '';
    if (!is_array($info)) {
        $string = stripslashes($info);
    }
    foreach ($info as $key => $val) {
        $string[$key] = stripslashes($val);
    }
    $setup = var_export($string, TRUE);
    return $setup;
}

//初始表单
function paramform($form, $info)
{
    $type = $info['types'];
    return $form->$type($info);
}

//初始表单
function getform($form, $info, $value = '')
{
    $type = $info['type'];
    return $form->$type($info, $value);
}

//文件单位换算
function byte_format($input, $dec = 0)
{
    $prefix_arr = array("B", "KB", "MB", "GB", "TB");
    $value = round($input, $dec);
    $i = 0;
    while ($value > 1024) {
        $value /= 1024;
        $i++;
    }
    $return_str = round($value, $dec) . $prefix_arr[$i];
    return $return_str;
}

//时间日期转换
function toDate($time, $format = 'Y-m-d H:i:s')
{
    if (empty ($time)) {
        return '';
    }
    $format = str_replace('#', ':', $format);
    return date($format, $time);
}

function template_file($module = '')
{
    $tempfiles = dir_list(APP_PATH . 'index/view/', 'html');
    foreach ($tempfiles as $key => $file) {
        $dirname = basename($file);
        if ($module) {
            if (strstr($dirname, $module . '_')) {
                $arr[$key]['value'] = substr($dirname, 0, strrpos($dirname, '.'));
                $arr[$key]['filename'] = $dirname;
                $arr[$key]['filepath'] = $file;
            }
        } else {
            $arr[$key]['value'] = substr($dirname, 0, strrpos($dirname, '.'));
            $arr[$key]['filename'] = $dirname;
            $arr[$key]['filepath'] = $file;
        }
    }
    return $arr;
}

function dir_list($path, $exts = '', $list = array())
{
    $path = dir_path($path);
    $files = glob($path . '*');
    foreach ($files as $v) {
        $fileext = fileext($v);
        if (!$exts || preg_match("/\.($exts)/i", $v)) {
            $list[] = $v;
            if (is_dir($v)) {
                $list = dir_list($v, $exts, $list);
            }
        }
    }
    return $list;
}

function dir_path($path)
{
    $path = str_replace('\\', '/', $path);
    if (substr($path, -1) != '/') $path = $path . '/';
    return $path;
}

function fileext($filename)
{
    return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}

/**
 * +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 * +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 * +----------------------------------------------------------
 * @return string
 * +----------------------------------------------------------
 */
function rand_string($len = 6, $type = '', $addChars = '')
{
    $str = '';
    switch ($type) {
        case 0:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        case 1:
            $chars = str_repeat('0123456789', 3);
            break;
        case 2:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
            break;
        case 3:
            $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        case 4:
            $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借" . $addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;

    }
    if ($len > 10) {//位数过长重复字符串一定次数
        $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
    }
    if ($type != 4) {
        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $len);
    } else {
        // 中文随机字
        for ($i = 0; $i < $len; $i++) {
            $str .= msubstr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1);
        }
    }
    return $str;
}

/**
 * 验证输入的邮件地址是否合法
 */
function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
        if (preg_match($chars, $user_email)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * 验证输入的手机号码是否合法
 */
function is_mobile_phone($mobile_phone)
{
    $chars = "/^1(3|4|5|6|7|8|9)\d{9}$/";
    if (preg_match($chars, $mobile_phone)) {
        return true;
    }
    return false;
}

/**
 * 取得IP
 *
 * @return string 字符串类型的返回结果
 */
function getIp()
{
    if (@$_SERVER['HTTP_CLIENT_IP'] && $_SERVER['HTTP_CLIENT_IP'] != 'unknown') {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (@$_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown') {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return preg_match('/^\d[\d.]+\d$/', $ip) ? $ip : '';
}

//字符串截取
function str_cut($sourcestr, $cutlength, $suffix = '...')
{
    $returnstr = '';
    $i = 0;
    $n = 0;
    $str_length = strlen($sourcestr);//字符串的字节数
    while (($n < $cutlength) and ($i <= $str_length)) {
        $temp_str = substr($sourcestr, $i, 1);
        $ascnum = Ord($temp_str);//得到字符串中第$i位字符的ascii码
        if ($ascnum >= 224)    //如果ASCII位高与224，
        {
            $returnstr = $returnstr . substr($sourcestr, $i, 3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
            $i = $i + 3;            //实际Byte计为3
            $n++;            //字串长度计1
        } elseif ($ascnum >= 192) //如果ASCII位高与192，
        {
            $returnstr = $returnstr . substr($sourcestr, $i, 2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
            $i = $i + 2;            //实际Byte计为2
            $n++;            //字串长度计1
        } elseif ($ascnum >= 65 && $ascnum <= 90) //如果是大写字母，
        {
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i = $i + 1;            //实际的Byte数仍计1个
            $n++;            //但考虑整体美观，大写字母计成一个高位字符
        } else                //其他情况下，包括小写字母和半角标点符号，
        {
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i = $i + 1;            //实际的Byte数计1个
            $n = $n + 0.5;        //小写字母和半角标点等与半个高位字符宽...
        }
    }
    if ($n > $cutlength) {
        $returnstr = $returnstr . $suffix;//超过长度时在尾处加上省略号
    }
    return $returnstr;
}

//删除目录及文件
function dir_delete($dir)
{
    $dir = dir_path($dir);
    if (!is_dir($dir)) return FALSE;
    $list = glob($dir . '*');
    foreach ($list as $v) {
        is_dir($v) ? dir_delete($v) : @unlink($v);
    }
    return @rmdir($dir);
}

/**
 * CURL请求
 * @param $url string 请求url地址
 * @param $method string 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug 调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method, $postfields = null, $headers = array(), $debug = false)
{
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    $tmpdatastr = '';
    if (!empty($postfields)) {
        $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
    }
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($tmpdatastr)) {
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            curl_setopt($ci, CURLOPT_URL, $url);
            break;
        default:
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
            }
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            curl_setopt($ci, CURLOPT_URL, $url . (!empty($tmpdatastr) ? '?' . $tmpdatastr : ''));
            break;
    }
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    //curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
    //return array($http_code, $response,$requestinfo);
}

/**
 * @param $arr
 * @param $key_name
 * @return array
 * 将数据库中查出的列表以指定的 id 作为数组的键名
 */
function convert_arr_key($arr, $key_name)
{
    $arr2 = array();
    foreach ($arr as $key => $val) {
        $arr2[$val[$key_name]] = $val;
    }
    return $arr2;
}

//查询IP地址
function getCity($ip = '')
{
    $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
    if (empty($res)) {
        return false;
    }
    $jsonMatches = array();
    preg_match('#\{.+?\}#', $res, $jsonMatches);
    if (!isset($jsonMatches[0])) {
        return false;
    }
    $json = json_decode($jsonMatches[0], true);
    if (isset($json['ret']) && $json['ret'] == 1) {
        $json['ip'] = $ip;
        unset($json['ret']);
    } else {
        return false;
    }
    return $json;
}

//判断图片的类型从而设置图片路径
function imgUrl($img, $defaul = '')
{
    if ($img) {
        if (substr($img, 0, 4) == 'http') {
            $imgUrl = $img;
        } else {
            $imgUrl = '__PUBLIC__' . $img;
        }
    } else {
        if ($defaul) {
            $imgUrl = $defaul;
        } else {
            $imgUrl = '__ADMIN__/images/default.png';
        }

    }
    return $imgUrl;
}

/**
 * PHP格式化字节大小
 * @param  number $size 字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '')
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 判断当前访问的用户是  PC端  还是 手机端  返回true 为手机端  false 为PC 端
 *  是否移动端访问访问
 * @return boolean
 */
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;

    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA'])) {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            return true;
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

function is_weixin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    }
    return false;
}

function is_douyin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'Aweme') !== false) {
        return true;
    }
    return false;
}

function is_qq()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') !== false) {
        return true;
    }
    return false;
}

function is_alipay()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
        return true;
    }
    return false;
}

/**
 * 获取用户信息
 * @param $user_id_or_name  用户id 邮箱 手机 第三方id
 * @param int $type 类型 0 user_id查找 1 邮箱查找 2 手机查找 3 第三方唯一标识查找
 * @param string $oauth 第三方来源
 * @return mixed
 */
function get_user_info($user_id_or_name, $type = 0, $oauth = '')
{
    $map = array();
    if ($type == 0) {
        $map['user_id'] = $user_id_or_name;
    }
    if ($type == 1) {
        $map['email'] = $user_id_or_name;
    }
    if ($type == 2) {
        $map['mobile'] = $user_id_or_name;
    }
    if ($type == 3) {
        $map['openid'] = $user_id_or_name;
        $map['oauth'] = $oauth;
    }
    if ($type == 4) {
        $map['unionid'] = $user_id_or_name;
        $map['oauth'] = $oauth;
    }
    if ($type == 5) {
        $map['nickname'] = $user_id_or_name;
    }
    $user = db('users')->where($map)->find();
    return $user;
}

/**
 * 过滤数组元素前后空格 (支持多维数组)
 * @param $array 要过滤的数组
 * @return array|string
 */
function trim_array_element($array)
{
    if (!is_array($array))
        return trim($array);
    return array_map('trim_array_element', $array);
}

/**
 * @param $arr
 * @param $key_name
 * @return array
 * 将数据库中查出的列表以指定的 值作为数组的键名，并以另一个值作为键值
 */
function convert_arr_kv($arr, $key_name, $value)
{
    $arr2 = array();
    foreach ($arr as $key => $val) {
        $arr2[$val[$key_name]] = $val[$value];
    }
    return $arr2;
}