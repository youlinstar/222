<?php


namespace app\api\controller;

use think\Controller;
use app\common\model\Admin;
use app\common\model\DomainCheck;
use app\common\model\Video;
use app\common\model\VideoSort;
use think\Db;
use think\Exception;

class Crond extends Controller
{
    
     /**
     * 检测
     */
    public function check1()
    {
        
       
       $api_info = DomainCheck::where('status', 1)->find();
        if (empty($api_info)) {
            $this->error('检测接口不存在');
        }
        // http://a29.pkosik.cn.w.kunlunea.com
        $domains = \app\common\model\Domain::where('status',1)->where('type','1')->select();
        $fail = 0;
        $sussces = 0;
        if (!empty($domains)) {
            foreach ($domains as $domain) {
                $result = httpRequest($api_info->api_url, 'GET', ['username' => $api_info->username, 'key' => $api_info->key, 'url' => 'http://' . $domain->domain]);
                
                $result = json_decode($result, true);
                if(empty($result)){
                    $fail ++;
                    continue;
                }
                if ($result['code'] == '1002' && $result['statu'] == 'false') {
                    $domain->delete();
                    
                    // code	number	1001为域名正常 1002为已被拦截
                    // msg	string	系统返回提示信息！
                    // statu	string	异常为false，正常为true！
                    // count	number	次数包，如需扣次数则返回！
                    // reason	string	拦截原因，如有则返回！
                    // describe	string	拦截描述，如有则返回！
                    // url	string	检测的地址！
                    // $domain->status = -1;
                    // $domain->remark = $result['reason'];
                    // $domain->utime = time();
                    // $domain->save();
                }
                $sussces ++;
                // else {
                //     $domain->utime = time();
                //     $domain->save();
                // }
                sleep(1);
            }
           
            exit('成功'.$sussces.'条,失败'.$fail.'条');
        }
        exit('失败,没有域名');
        
    }
    public function check2()
    {
        
       
       $api_info = DomainCheck::where('status', 1)->find();
        if (empty($api_info)) {
            $this->error('检测接口不存在');
        }
        // http://a29.pkosik.cn.w.kunlunea.com
        $domains = \app\common\model\Domain::where('status',1)->where('type','2')->select();
        $fail = 0;
        $sussces = 0;
        if (!empty($domains)) {
            foreach ($domains as $domain) {
                $result = httpRequest($api_info->api_url, 'GET', ['username' => $api_info->username, 'key' => $api_info->key, 'url' => 'http://' . $domain->domain]);
                
                $result = json_decode($result, true);
                if(empty($result)){
                    $fail ++;
                    continue;
                }
                if ($result['code'] == '1002' && $result['statu'] == 'false') {
                    $domain->delete();
                    
                    // code	number	1001为域名正常 1002为已被拦截
                    // msg	string	系统返回提示信息！
                    // statu	string	异常为false，正常为true！
                    // count	number	次数包，如需扣次数则返回！
                    // reason	string	拦截原因，如有则返回！
                    // describe	string	拦截描述，如有则返回！
                    // url	string	检测的地址！
                    // $domain->status = -1;
                    // $domain->remark = $result['reason'];
                    // $domain->utime = time();
                    // $domain->save();
                }
                $sussces ++;
                // else {
                //     $domain->utime = time();
                //     $domain->save();
                // }
                sleep(1);
            }
           
            exit('成功'.$sussces.'条,失败'.$fail.'条');
        }
        exit('失败,没有域名');
        
    }
    public function check3()
    {
        
       
       $api_info = DomainCheck::where('status', 1)->find();
        if (empty($api_info)) {
            $this->error('检测接口不存在');
        }
        // http://a29.pkosik.cn.w.kunlunea.com
        $domains = \app\common\model\Domain::where('status',1)->where('type','in', '3,4')->select();
        $fail = 0;
        $sussces = 0;
        if (!empty($domains)) {
            foreach ($domains as $domain) {
                $result = httpRequest($api_info->api_url, 'GET', ['username' => $api_info->username, 'key' => $api_info->key, 'url' => 'http://' . $domain->domain]);
                
                $result = json_decode($result, true);
                if(empty($result)){
                    $fail ++;
                    continue;
                }
                if ($result['code'] == '1002' && $result['statu'] == 'false') {
                    $domain->delete();
                    
                    // code	number	1001为域名正常 1002为已被拦截
                    // msg	string	系统返回提示信息！
                    // statu	string	异常为false，正常为true！
                    // count	number	次数包，如需扣次数则返回！
                    // reason	string	拦截原因，如有则返回！
                    // describe	string	拦截描述，如有则返回！
                    // url	string	检测的地址！
                    // $domain->status = -1;
                    // $domain->remark = $result['reason'];
                    // $domain->utime = time();
                    // $domain->save();
                }
                $sussces ++;
                // else {
                //     $domain->utime = time();
                //     $domain->save();
                // }
                sleep(1);
            }
           
            exit('成功'.$sussces.'条,失败'.$fail.'条');
        }
        exit('失败,没有域名');
        
    }
    public function check4()
    {
        
       
       $api_info = DomainCheck::where('status', 1)->find();
        if (empty($api_info)) {
            $this->error('检测接口不存在');
        }
        // http://a29.pkosik.cn.w.kunlunea.com
        $domains = \app\common\model\Domain::where('status',1)->where('type','in', '5,6')->select();
        $fail = 0;
        $sussces = 0;
        if (!empty($domains)) {
            foreach ($domains as $domain) {
                $result = httpRequest($api_info->api_url, 'GET', ['username' => $api_info->username, 'key' => $api_info->key, 'url' => 'http://' . $domain->domain]);
                
                $result = json_decode($result, true);
                if(empty($result)){
                    $fail ++;
                    continue;
                }
                if ($result['code'] == '1002' && $result['statu'] == 'false') {
                    $domain->delete();
                    
                    // code	number	1001为域名正常 1002为已被拦截
                    // msg	string	系统返回提示信息！
                    // statu	string	异常为false，正常为true！
                    // count	number	次数包，如需扣次数则返回！
                    // reason	string	拦截原因，如有则返回！
                    // describe	string	拦截描述，如有则返回！
                    // url	string	检测的地址！
                    // $domain->status = -1;
                    // $domain->remark = $result['reason'];
                    // $domain->utime = time();
                    // $domain->save();
                }
                $sussces ++;
                // else {
                //     $domain->utime = time();
                //     $domain->save();
                // }
                sleep(1);
            }
           
            exit('成功'.$sussces.'条,失败'.$fail.'条');
        }
        exit('失败,没有域名');
        
    }
    
   

}