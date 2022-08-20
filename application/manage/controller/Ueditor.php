<?php

namespace app\manage\controller;
use think\facade\Env;
use think\Image;
class Ueditor extends Common
{
    protected $setting;
    public function initialize(){
        parent::initialize();
        $this->setting=new \app\common\model\Setting();
    }
    /**
     * 编辑上传接口
     */
    public function index(){
        header("Content-Type: text/html; charset=utf-8");
        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("./static/ueditor/php/config.json")), true);
        $action = $_GET['action'];
        switch ($action) {
            case 'config':
                $result =  json_encode($CONFIG,true);
                break;
            /* 上传图片 */
            case 'uploadimage':
                $fieldName = $CONFIG['imageFieldName'];
                $result = $this->upImage($fieldName);
                break;
            /* 上传涂鸦 */
            case 'uploadscrawl':
                $config = array(
                    "pathFormat" => $CONFIG['scrawlPathFormat'],
                    "maxSize" => $CONFIG['scrawlMaxSize'],
                    "allowFiles" => $CONFIG['scrawlAllowFiles'],
                    "oriName" => "scrawl.png"
                );
                $fieldName = $CONFIG['scrawlFieldName'];
                $base64 = "base64";
                $result = $this->upBase64($config,$fieldName);
                break;
            /* 上传视频 */
            case 'uploadvideo':
                $fieldName = $CONFIG['videoFieldName'];
                $result = $this->upFile($fieldName);
                break;
            /* 上传文件 */
            case 'uploadfile':
                $fieldName = $CONFIG['fileFieldName'];
                $result = $this->upFile($fieldName);
                break;
            /* 列出图片 */
            case 'listimage':
                $allowFiles = $CONFIG['imageManagerAllowFiles'];
                $listSize = $CONFIG['imageManagerListSize'];
                $path = $CONFIG['imageManagerListPath'];
                $get =$_GET;
                $result =$this->fileList($allowFiles,$listSize,$get);
                break;
            /* 列出文件 */
            case 'listfile':
                $allowFiles = $CONFIG['fileManagerAllowFiles'];
                $listSize = $CONFIG['fileManagerListSize'];
                $path = $CONFIG['fileManagerListPath'];
                $get = $_GET;
                $result = $this->fileList($allowFiles,$listSize,$get);
                break;
            /* 抓取远程文件 */
            case 'catchimage':
                $config = array(
                    "pathFormat" => $CONFIG['catcherPathFormat'],
                    "maxSize" => $CONFIG['catcherMaxSize'],
                    "allowFiles" => $CONFIG['catcherAllowFiles'],
                    "oriName" => "remote.png"
                );
                $fieldName = $CONFIG['catcherFieldName'];
                /* 抓取远程图片 */
                $list = array();
                isset($_POST[$fieldName]) ? $source = $_POST[$fieldName] : $source = $_GET[$fieldName];

                foreach($source as $imgUrl){
                    $info = json_decode($this->saveRemote($config,$imgUrl),true);
                    array_push($list,[
                        "state" => $info["state"],
                        "url" => $info["url"],
                        "size" => $info["size"],
                        "title" => htmlspecialchars($info["title"]),
                        "original" => htmlspecialchars($info["original"]),
                        "source" => htmlspecialchars($imgUrl)
                    ]);
                }

                $result = json_encode([
                    'state' => count($list) ? 'SUCCESS':'ERROR',
                    'list' => $list
                ]);
                break;
            default:
                $result = json_encode(['state' => '请求地址出错']);
                break;
        }

        /* 输出结果 */
        if(isset($_GET["callback"])){
            if(preg_match("/^[\w_]+$/", $_GET["callback"])){
                echo htmlspecialchars($_GET["callback"]).'('.$result.')';
            }else{
                echo json_encode(['state' => 'callback参数不合法']);
            }
        }else{
            echo $result;
        }
    }
    //上传图片
    private function upImage($fieldName){
        $file = request()->file($fieldName);
        $info = $file->validate(['size'=>5*1024*1024,'ext'=>'jpg,png,gif,jpeg'])->move(Env::get('root_path').'public' . DIRECTORY_SEPARATOR . 'uploads');
        if($info){//上传成功
            $fname='/uploads/'.str_replace('\\','/',$info->getSaveName());
            $path=str_replace('\\','/',$info->getSaveName());
            #编辑器上传图片是否加水印
            $wather=input('wather/d',0);
            #水印图片
            if(config('setting.upload_water_types')>0 && $wather==1){
                $savename = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$path;
                $this->wather($savename);
            }
            $data=array(
                'state' => 'SUCCESS',
                'url' => $fname,
                'title' => $info->getFilename(),
                'original' => $info->getFilename(),
                'type' => '.' . $info->getExtension(),
                'size' => $info->getSize(),
            );
        }else{
            $data=array(
                'state' => $info->getError(),
            );
        }
        return json_encode($data);
    }
    //上传文件
    private function upFile($fieldName){
        $file = request()->file($fieldName);
        $info = $file->validate(['size'=>10*1024*1024,'ext'=>'doc,docx,xls,xlsx,ppt,pptx'])->move(Env::get('root_path').'public' . DIRECTORY_SEPARATOR . 'uploads');
        if($info){//上传成功
            $fname='/uploads/'.str_replace('\\','/',$info->getSaveName());
            $data=[
                'state' => 'SUCCESS',
                'url' => $fname,
                'title' => $info->getFilename(),
                'original' => $info->getFilename(),
                'type' => '.' . $info->getExtension(),
                'size' => $info->getSize(),
            ];
        }else{
            $data=['state' => $info->getError()];
        }
        return json_encode($data);
    }

    //列出图片
    private function fileList($allowFiles,$listSize,$get){
        $dirname = './uploads/';
        $allowFiles = substr(str_replace(".","|",join("",$allowFiles)),1);

        /* 获取参数 */
        $size = isset($get['size']) ? htmlspecialchars($get['size']) : $listSize;
        $start = isset($get['start']) ? htmlspecialchars($get['start']) : 0;
        $end = $start + $size;

        /* 获取文件列表 */
        $path = $dirname;
        $files = $this->getFiles($path,$allowFiles);
        if(!count($files)){
            return json_encode([
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => count($files)
            ]);
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
            $list[] = $files[$i];
        }

        /* 返回数据 */
        $result = json_encode([
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        ]);

        return $result;
    }

    /*
  * 遍历获取目录下的指定类型的文件
  * @param $path
  * @param array $files
  * @return array
 */
    private function getFiles($path,$allowFiles,&$files = array()){
        if(!is_dir($path)) return null;
        if(substr($path,strlen($path)-1) != '/') $path .= '/';
        $handle = opendir($path);
        while(false !== ($file = readdir($handle))){
            if($file != '.' && $file != '..'){
                $path2 = $path.$file;
                if(is_dir($path2)){
                    $this->getFiles($path2,$allowFiles,$files);
                }else{
                    if(preg_match("/\.(".$allowFiles.")$/i",$file)){
                        $files[] = array(
                            'url' => substr($path2,1),
                            'mtime' => filemtime($path2)
                        );
                    }
                }
            }
        }
        return $files;
    }

    //抓取远程图片
    private function saveRemote($config,$fieldName){
        $imgUrl = htmlspecialchars($fieldName);
        $imgUrl = str_replace("&amp;","&",$imgUrl);

        //http开头验证
        if(strpos($imgUrl,"http") !== 0){
            return json_encode(['state' => '链接不是http链接']);
        }
        //获取请求头并检测死链
        $heads = get_headers($imgUrl);
        if(!(stristr($heads[0],"200") && stristr($heads[0],"OK"))){
            return json_encode(['state' => '链接不可用']);
        }
        //格式验证(扩展名验证和Content-Type验证)
        $fileType = strtolower(strrchr($imgUrl,'.'));
        if(!in_array($fileType,$config['allowFiles']) || stristr($heads['Content-Type'],"image")){
            return json_encode(['state' => '链接contentType不正确']);
        }

        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
            array('http' => array(
                'follow_location' => false // don't follow redirects
            ))
        );
        readfile($imgUrl,false,$context);
        $img = ob_get_contents();
        ob_end_clean();
        preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/",$imgUrl,$m);

        $dirname = './uploads/remote/';
        $file['oriName'] = $m ? $m[1] : "";
        $file['filesize'] = strlen($img);
        $file['ext'] = strtolower(strrchr($config['oriName'],'.'));
        $file['name'] = uniqid().$file['ext'];
        $file['fullName'] = $dirname.$file['name'];
        $fullName = $file['fullName'];

        //检查文件大小是否超出限制
        if($file['filesize'] >= ($config["maxSize"])){
            return json_encode(['state' => '文件大小超出网站限制']);
        }

        //创建目录失败
        if(!file_exists($dirname) && !mkdir($dirname,0777,true)){
            return json_encode(['state' => '目录创建失败']);
        }else if(!is_writeable($dirname)){
            return json_encode(['state' => '目录没有写权限']);
        }

        //移动文件
        if(!(file_put_contents($fullName, $img) && file_exists($fullName))){ //移动失败
            return json_encode(['state' => '写入文件内容错误']);
        }else{ //移动成功
            $data=array(
                'state' => 'SUCCESS',
                'url' => substr($file['fullName'],1),
                'title' => $file['name'],
                'original' => $file['oriName'],
                'type' => $file['ext'],
                'size' => $file['filesize'],
            );
        }

        return json_encode($data);
    }

    /*
	 * 处理base64编码的图片上传
	 * 例如：涂鸦图片上传
	*/
    private function upBase64($config,$fieldName){
        $base64Data = $_POST[$fieldName];
        $img = base64_decode($base64Data);

        $dirname = './uploads/scrawl/';
        $file['filesize'] = strlen($img);
        $file['oriName'] = $config['oriName'];
        $file['ext'] = strtolower(strrchr($config['oriName'],'.'));
        $file['name'] = uniqid().$file['ext'];
        $file['fullName'] = $dirname.$file['name'];
        $fullName = $file['fullName'];

        //检查文件大小是否超出限制
        if($file['filesize'] >= ($config["maxSize"])){
            return json_encode(['state' => '文件大小超出网站限制']);
        }

        //创建目录失败
        if(!file_exists($dirname) && !mkdir($dirname,0777,true)){
            return json_encode(['state' => '目录创建失败']);
        }else if(!is_writeable($dirname)){
            return json_encode(['state' => '目录没有写权限']);
        }

        //移动文件
        if(!(file_put_contents($fullName, $img) && file_exists($fullName))){ //移动失败
            $data=[
                'state' => '写入文件内容错误',
            ];
        }else{ //移动成功
            $data=[
                'state' => 'SUCCESS',
                'url' => substr($file['fullName'],1),
                'title' => $file['name'],
                'original' => $file['oriName'],
                'type' => $file['ext'],
                'size' => $file['filesize'],
            ];
        }
        return json_encode($data);
    }

    /**
     *水印
     */
    protected function wather($path){
        $image = Image::open($path);
        $type=config('setting.upload_water_types');#水印类型
        $wather=config('setting.upload_wather_image');//水印图片
        $alpha=config('setting.upload_wather_opacity');#透明度
        $text=config('setting.upload_wather_text');#水印文字
        $font=config('setting.upload_wather_font');#文字字体
        $size=config('setting.upload_wather_size');#文字大小
        $color=config('setting.upload_wather_color');#文字大小
        $pos=config('setting.upload_wather_pos');#水印位置
        $x=config('setting.upload_wather_posx');#X位置
        $y=config('setting.upload_wather_posy');#Y位置
        $offset=[$x,$y];
        switch($type){
            case 1:#图片水印
                $image->water(Env::get('root_path').'public'.$wather,$this->position($pos),$alpha)->save($path);
                break;
            case 2:#文字水印
                $image->text($text,Env::get('root_path').$font,$size,$color,$this->position($pos),$offset)->save($path);
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
}