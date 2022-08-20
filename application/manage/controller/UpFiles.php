<?php

namespace app\manage\controller;
use think\facade\Env;
use think\Image;
use oss\Alioss;
class UpFiles extends Common
{
    protected $noNeedRight = ['upload','file','pic','upImages','editUpload'];
    protected $alioss;
    public function initialize(){
        parent::initialize();
    }
    /**
     * 上传图片
     * @return string
     */
    public function upload(){
        #获取上传文件表单字段名
        $types=request()->param('types');
        #获取表单上传文件
        #存储方式
        $is_local=config('setting.upload_storage');
        #本地
        if($is_local=='local'){
            $fileKey = array_keys(request()->file());
            $file = request()->file($fileKey['0']);
            $info = $file->validate(['size'=>5*1024*1024,'ext'=>'jpg,png,gif,jpeg'])->move(Env::get('root_path').'public' . DIRECTORY_SEPARATOR . 'uploads');
            if($info){
                #字段配置参数
                $thumb=input('thumb/d',0);
                $width=input('width/d',0);
                $height=input('height/d',0);
                $path=str_replace('\\','/',$info->getSaveName());
                #缩略图
                if(config('setting.upload_isthumb')==1 && $thumb==1){
                    $savename = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$path;
                    $this->thumb($savename,$width,$height);
                }
                return callback(200,'图片上传成功','/uploads/'. $path);
            }else{
                return callback(400,'图片上传失败');
            }
        }else{#oss
            $oss=new Alioss();
            $info=$oss->upload('file');
            if($info['status']==200){
                return callback(200,'图片上传成功',$info['data']['url']);
            }else{
                return callback(400,$info['msg']);
            }
        }
    }
    /**
     *水印
     */
    protected function wather($path){
        $image = Image::open($path);
        $type=config('setting.upload_water_types');#水印类型
        $wather=config('setting.upload_wather_image');//水印图片
        $alpha=config('setting.upload_wather_opacity');#透明度
        $text=config('setting.upload_wather_opacity');#水印文字
        $font=config('setting.upload_wather_font');#文字字体
        $size=config('setting.upload_wather_size');#文字大小
        $color=config('setting.upload_wather_color');#文字大小
        $pos=config('setting.upload_wather_pos');#水印位置
        $x=config('setting.upload_wather_posx');#X位置
        $y=config('setting.upload_wather_posy');#Y位置
        $offset=[$x,$y];
        switch($type){
            case 1:#图片水印
                $image->water($wather,$this->position($pos),$alpha)->save($path);
                break;
            case 2:#文字水印
                $image->text($text,$font,$size,$color,$this->position($pos),$offset)->save($path);
                break;
        }
    }
    /**
    * 位置居左上角|nw  WATER_NORTHEAST
    位置顶部居中|north  WATER_NORTH
    位置居右上角|ne  WATER_NORTHEAST
    位置左部居中|west WATER_WEST
    位置正中心|center  WATER_CENTER
    位置右部居中|east WATER_EAST
    位置居左下角|sw  WATER_SOUTHWEST
    位置底部居中|south WATER_SOUTH
    位置居右下角|se  WATER_SOUTHEAST
     * 转换位置
     * @param $pos
     */
    protected function position($pos){
        switch(strtolower($pos)){
            case 'nw':
                $wather_pos=Image::WATER_NORTHWEST;
                break;
            case 'north':
                $wather_pos=Image::WATER_NORTH;
                break;
            case 'ne':
                $wather_pos=Image::WATER_NORTHEAST;
                break;
            case 'west':
                $wather_pos=Image::WATER_WEST;
                break;
            case 'center':
                $wather_pos=Image::WATER_CENTER;
                break;
            case 'east':
                $wather_pos=Image::WATER_EAST;
                break;
            case 'sw':
                $wather_pos=Image::WATER_SOUTHWEST;
                break;
            case 'south':
                $wather_pos=Image::WATER_SOUTH;
                break;
            case 'se':
                $wather_pos=Image::WATER_SOUTHEAST;
                break;
        }
        return $wather_pos;
    }
    /**
     *缩略图
     */
    protected function thumb($path,$width,$height){
        $image = Image::open($path);
        $wid=config('setting.upload_thumb_width');
        $hei=config('setting.upload_thumb_height');
        $types=config('setting.upload_thumb_types');
        if(!empty($width)){
            $wid=$width;
        }
        if(!empty($height)){
            $hei=$height;
        }
        switch($types){
            case 1:#宽度固定，高度自适应
                $position=Image::THUMB_WIDTH;
                break;
            case 2:#高度固定，宽度自适应
                $position=Image::THUMB_HEIGHT;
                break;
            case 3:#固定宽高，缩略居中裁剪
                $position=Image::THUMB_CENTER;
                break;
        }
        $image->thumb($wid,$hei,$position)->save($path);
    }
    /**
     * 上传文件
     * @return string
     */
    public function file(){
        $fileKey = array_keys(request()->file());
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file($fileKey['0']);
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size'=>10*1024*1024,'ext'=>'doc,docx,xls,xlsx,ppt,pptx'])->move(Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads');

        if($info){
            $result['code'] = 0;
            $result['info'] = '文件上传成功!';
            $path=str_replace('\\','/',$info->getSaveName());

            $result['url'] = '/uploads/'. $path;
            $result['ext'] = $info->getExtension();
            $result['size'] = byte_format($info->getSize(),2);
            return $result;
        }else{
            // 上传失败获取错误信息
            $result['code'] =1;
            $result['info'] = '文件上传失败!';
            $result['url'] = '';
            return $result;
        }
    }
    public function pic(){
        // 获取上传文件表单字段名
        $fileKey = array_keys(request()->file());
        // 获取表单上传文件
        $file = request()->file($fileKey['0']);
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size'=>5*1024*1024,'ext'=>'jpg,png,gif,jpeg'])->move(Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads');
        if($info){
            $result['code'] = 1;
            $result['info'] = '图片上传成功!';
            $path=str_replace('\\','/',$info->getSaveName());
            $result['url'] = '/uploads/'. $path;
            return json_encode($result,true);
        }else{
            // 上传失败获取错误信息
            $result['code'] =0;
            $result['info'] = '图片上传失败!';
            $result['url'] = '';
            return json_encode($result,true);
        }
    }
    //编辑器图片上传
    public function editUpload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size'=>5*1024*1024,'ext'=>'jpg,png,gif,jpeg'])->move(Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads');
        if($info){
            $result['code'] = 0;
            $result['msg'] = '图片上传成功!';
            $path=str_replace('\\','/',$info->getSaveName());
            #水印
            if(config('setting.upload_water_types')>0){
                $savename = DIRECTORY_SEPARATOR.'uploads/'.$path;
                $this->wather($savename);
            }
            $result['data']['src'] = '/uploads/'. $path;
            $result['data']['title'] = $path;
            return json_encode($result,true);
        }else{
            // 上传失败获取错误信息
            $result['code'] =1;
            $result['msg'] = '图片上传失败!';
            $result['data'] = '';
            return json_encode($result,true);
        }
    }
    #多图上传
    public function upImages(){
        $fileKey = array_keys(request()->file());
        // 获取表单上传文件
        $file = request()->file($fileKey['0']);
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size'=>5*1024*1024,'ext'=>'jpg,png,gif,jpeg'])->move(Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads');
        if($info){
            $result['code'] = 0;
            $result['msg'] = '图片上传成功!';
            $path=str_replace('\\','/',$info->getSaveName());
            $result["src"] = '/uploads/'. $path;
            return $result;
        }else{
            // 上传失败获取错误信息
            $result['code'] =1;
            $result['msg'] = '图片上传失败!';
            return $result;
        }
    }
}