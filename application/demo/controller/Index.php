<?php


namespace app\demo\controller;


use think\Controller;
use think\facade\Session;
use think\facade\Cookie;
class Index extends Controller
{
   
    //入口
    public function index()
    {
      
       
       
        //  $api_url = 'http://43.248.188.182/demo/index/api';
        //  $res = httpRequest($api_url,'GET');
        //  $res = json_decode($res, true);
            
        //     halt($res);
       
        
        // $biaoshi = Session::get('biaoshi');
        
      
        // if(!isset($biaoshi) || empty($biaoshi)){
            
        
        //     $biaoshi = time();
        //     Session::set('biaoshi',$biaoshi);
        // }
        
        
        // echo $biaoshi;
        
        
       
        
//         // // 请求状态码。非 200 表示没有获取到设备明细信息
//         // if ($result['stateCode'] == 200) 
//         //     echo "设备明细信息如下： : " . json_encode($result['data'], true);
//         // else 
//         //     echo $result['message'];
        
//         $token = $this->request->param('token','','trim');
//         // $token = '62db65224GV7v7o2VreibLm0mo8kj69gqwXjfp31';
//         $appId = 'f1785a32dbb0a4a529e825483f02dd40';
        
//         $appSecret = '39a92f9f516f753a8166969c80a3c2ed';
//         $sign = md5($appSecret . $token . $appSecret);
//         $data = [
//             'appId'=>$appId,
//             'sign'=>$sign,
//             'token'=>$token
//             ];
        
//         $data = http_build_query($data);
//         $url = 'https://constid.dingxiang-inc.com/udid/api/getDeviceInfo';
// 		$ch = curl_init();
// 		curl_setopt($ch, CURLOPT_URL, $url);
// 		curl_setopt($ch, CURLOPT_POST, 1);
// 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
// 		curl_setopt($ch, CURLOPT_TIMEOUT, 2);

// 		$response = curl_exec($ch);
// 		if (curl_errno($ch))
// 		{
// 			$response=json_encode(array( 'stateCode'=>curl_errno($ch),'message' => curl_error($ch)), JSON_FORCE_OBJECT);
// 		}
// 		curl_close($ch);

// 		halt($response);
        
        
        // $requestHandle = new DeviceFingerprintHandle();
        
        // $responseData = $requestHandle->getDeviceInfo("https://constid.dingxiang-inc.com/udid/api/getDeviceInfo", $appID, $appSecret, $token);
        
        // $result = json_decode($responseData, true);
        
        // halt($result);
       
  
        
    //   halt(request());
    //     $ua = request()->header('USER_AGENT');
     
    //     $ua = str_replace('WIFI', '', $ua);
         
    //     $ua = str_replace('4G', '', $ua);
    //     $ua = str_replace('2G', '', $ua);
    //     $ua = str_replace('5G', '', $ua);
         
    //     echo $ua;
    }
    public function api(){
        
        
        $biaoshi = Cookie::get('biaoshi');
       
       
        if(!isset($biaoshi) || empty($biaoshi)){
        
            $biaoshi = time();
            Cookie::forever('biaoshi',$biaoshi);
            // Cookie::set('biaoshi',$biaoshi);
        }
        
        echo json_encode(['code'=>1,'biaoshi'=>Cookie::get('biaoshi')]);
        exit;
    }
   
}