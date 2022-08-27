<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2020/5/2
 * Time: 14:58
 */
namespace oss;
use OSS\Core\OssException;
use OSS\OssClient;
use think\Exception;
use think\facade\Config;
use think\facade\Request;
class Alioss
{
    protected $alioss;
    protected $bucket;
    protected $uploadInfo = [];
    public function __construct()
    {
        $key_id=Config::get('setting.upload_keyid');
        $secret=Config::get('setting.upload_secret');
        $endint=Config::get('setting.upload_endpoint');
        $this->bucket=Config::get('setting.upload_bucket');
        $this->alioss=new OssClient($key_id,$secret,$endint);
    }

    /**
     * 单文件上传文件
     */
    public function upload($input='file',$dir='public/uploads',$rule = ['ext' => ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']]){
        $info=Request::file($input);
        if(!$info){
            return $this->ret(404, '上传文件不能为空');
        }
        //单图片上传
        $check_rule = $info->check($rule);
        if (!$check_rule){
            return $this->ret(404, $info->getError());
        }
        $path = $this->setSaveName($info, $dir);
        $content = file_get_contents($info->getInfo('tmp_name'));
        $result = $this->putObject($path, $content);
        if ($result['status']!==200){
            return $this->ret(404, $result['msg']);
        }
        return $this->ret(200, 'success',['url'=>$result['data']['url']]);
    }

    /**
     * 下载文件保存到对象存储
     */
    public function download($url,$dir='public/uploads'){

        $path = $this->setSaveName($url, $dir);
        $stream = file_get_contents($url);
        $result = $this->putObject($path, $stream);
        if ($result['status']!==200){
            return $this->ret(404, $result['msg']);
        }
        return $this->ret(200, 'success',['url'=>$result['data']['url']]);
    }
    /**
     * 多文件上传
     */
    public function mulupload($input='file',$dir='public/uploads',$rule = ['ext' => ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']]){
        $info=Request::file($input);
        if(!$info){
            return $this->ret(404, '上传文件不能为空');
        }
        //多图上传
        foreach ($info as $file){
            $result = $file->check($rule);
            if (!$result){
                #保存错误信息
                $this->setUploadInfo('error', $file->getInfo('name').'文件上传错误:'.$file->getError(), $dir);
                continue;
            }
            $path = $this->setSaveName($file, $dir);
            $content = file_get_contents($file->getInfo('tmp_name'));
            $result = $this->putObject($path, $content);
            if ($result['status']==200){
                $this->setUploadInfo('success','上传成功', $result['data']['url']);
            }else{
                $this->setUploadInfo('error', $result['msg'],$path);
            }
        }
        return $this->ret(200, 'success',$this->uploadInfo);
    }
    /**
     * description 设置上传信息
     * @param string $result
     * @param string $msg
     * @param string $path
     */
    protected function setUploadInfo($result,$msg,$path){
        array_push($this->uploadInfo,['result' => $result,'msg' => $msg,'path' => $path]);
    }
    /**
     * description 设置文件名路径
     * @param $info mixed 上传文件对象
     * @param $dir
     * @return string
     */
    protected function setSaveName($info, $dir){
        if(is_object($info)){
            $path = $info->getInfo('name');
        }else{
            $path = $info;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if(empty($ext)){
            $ext='jpg';
        }
        $savename = date('Ymd') . DIRECTORY_SEPARATOR . md5(microtime(true)).'.'.$ext;
        return str_replace('\\','/',$dir .DIRECTORY_SEPARATOR. $savename);
    }

    /**
     * description 底层调用
     * @param $method
     * @param $arguments
     * @param bool $isRetOriginal
     * @return array|bool|mixed
     */
    public function baseCall($method, $arguments, $isRetOriginal = false){
        if (!in_array($method, get_class_methods($this->alioss)))
            return $this->ret(500, '方法不存在');
        try{
            $result = call_user_func_array(array($this->alioss, $method), $arguments);
            if ($isRetOriginal) return $result;
            return $this->ret(200,'success',['url'=>$result['info']['url']]);
        }catch (Exception $e){
            return $this->ret(500, $e->getMessage());
        }
    }

    /**
     * description 单或多图上传
     * @param $path
     * @param $content
     * @return array|bool|mixed
     */
    protected function putObject($path, $content){
        return $this->baseCall('putObject',[$this->bucket, $path, $content]);
    }
    /**
     * description 定义返回格式
     * author chicho
     * @param int $code
     * @param string $msg
     * @param string $data
     * @return array
     */
    protected function ret($code = 200, $msg = '操作成功', $data = ''){
        return ['status' => $code, 'msg' => $msg, 'data' => $data];
    }
}