<?php


namespace app\api\controller;
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST,GET');
header('Access-Control-Allow-Headers:*');
header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With,X-PINGOTHER,Content-Type');
use app\common\model\Admin;
use app\common\model\DomainCheck;
use app\common\model\Video;
use app\common\model\VideoSort;
use think\Db;
use think\Exception;

class Crond extends Common
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取域名
     */
    public function getDomain(){
        $doamin=\app\common\model\Domain::where(['type'=>1,'status'=>1])->orderRand()->find();
        if(!empty($doamin)){
            return $doamin->domain;
        }
        return 'www.baidu.com';
    }
    /**
     * 转码系统入库API
     */
    public function importVideo()
    {
        try {
            $task = file_get_contents("php://input");
            if ($task) {
                doSyslog($task, 'task');
                $video_info = json_decode($task, true);
                $title_old = $video_info['orgfile'];//原文件名
                $title_ext = '.' . $video_info['suffix'];//后缀
                $info = [
                    'uid' => 1,
                    'ctime' => time(),
                    'status' => 1
                ];
                #标题
                $info['title'] = str_replace($title_ext, '', $title_old);
                //真实路径
                $video_path = str_replace('\\', '/', $video_info['rpath']);
                //$path_arr = explode('/',$video_path['rpath']);
                #mp4地址
                //$info['mp4'] =$video_info['domain'].$video_path['rpath'].'/mp4/'.$path_arr[2].'.mp4';
                #m3u8地址
                $info['link'] = $video_info['domain'] . $video_path['rpath'] . '/index.m3u8';
                #缩略图
                $info['thumb'] = $video_info['domain'] . $video_path['rpath'] . '/1.jpg';

                $info['sortid'] =$this->getCatId($info['title'],0);

                $info['title']=str_replace('】','',str_replace('【','',$info['title']));

                $result = Video::where('title', $info['title'])->find();
                if (empty($result)) {
                    $res_id = Video::create($info, true);
                    if (!$res_id) {
                        doSyslog('资源【' . $info['title'] . '】入库失败', 'task');
                    }else{
                        doSyslog('资源【' . $info['title'] . '】入库成功', 'task');
                    }
                }else{
                    doSyslog('【' . $result['title'] . '】资源已经存在', 'task');
                }
            }else{
                doSyslog('未获取到资源', 'task');
            }
        } catch (Exception $e) {
            doSyslog($e->getMessage(), 'task');
        }
    }

    protected function getCatId($title,$cat_id){
        $sort_arr=VideoSort::where('status',1)->select();
        foreach($sort_arr as $k=>$v){
            if(strpos($title,'【'.$v['name'])!== false){
                return $v['id'];
            }
        }
        return $cat_id;
    }
    /**
     * 自有微信检测域名
     */
    public function checkWxDomain()
    {
        $api_info = DomainCheck::where('status', 1)->find();
        
        if (empty($api_info)) {
            $this->error('检测接口不存在');
        }
        $domains = \app\common\model\Domain::where('status', 1)->select();
        
        if (!empty($domains)) {
            foreach ($domains as $domain) {
                list($code,$result) = checkDomain('http://' . $domain->domain);
                if ($code == 402 || $code == 403){
                    $domain->status = -1;
                    $domain->remark = $result;
                    $domain->utime = time();
                    $domain->save();
                } else {
                    $domain->utime = time();
                    $domain->save();
                }
                sleep(1);
            }
        }
        $this->success('success');
    }
     /**
     * 自有微信检测域名 落地和支付
     */
    public function checkWxDomain1()
    {
        
       
       $api_info = DomainCheck::where('status', 1)->find();
        if (empty($api_info)) {
            $this->error('检测接口不存在');
        }
        // http://a29.pkosik.cn.w.kunlunea.com
        $domains = \app\common\model\Domain::where('status',1)->where('type','in', '2,3')->select();
        
        if (!empty($domains)) {
            foreach ($domains as $domain) {
                $result = httpRequest($api_info->api_url, 'GET', ['username' => $api_info->username, 'key' => $api_info->key, 'url' => 'http://' . $domain->domain]);
               
                $result = json_decode($result, true);
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
                } else {
                    $domain->utime = time();
                    $domain->save();
                }
                sleep(1);
            }
        }
        $this->success('success');
        
    }
    /**
     * 外部检测域名
     */
    public function checkDomain()
    {
        
        $api_info = DomainCheck::where('status', 1)->find();
        if (empty($api_info)) {
            $this->error('检测接口不存在');
        }
        // http://a29.pkosik.cn.w.kunlunea.com
        $domains = \app\common\model\Domain::where('status', 1)->select();
        if (!empty($domains)) {
            foreach ($domains as $domain) {
                $result = httpRequest($api_info->api_url, 'GET', ['username' => $api_info->username, 'key' => $api_info->api_token, 'url' => 'http://' . $domain->domain]);
                
               
                $result = json_decode($result, true);
               
                if ($result['code'] == '1002' && $result['statu'] == 'false') {
                    $domain->status = -1;
                    $domain->remark = $result['reason'];
                    $domain->utime = time();
                    $domain->save();
                } else {
                    $domain->utime = time();
                    $domain->save();
                }
                sleep(1);
            }
        }
        $this->success('success');
    }

}