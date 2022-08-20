<?php

namespace app\common\model;
use think\facade\Env;
use think\Model;
class Setting extends Model
{
    //设置参数
    public function setValue($skey, $value)
    {
        list($res,$msg)= $this->check($skey,$value);
        if(!$res){
            return [$res,$msg];
        }
        $info = $this->where('skey',$skey)->find();
        if($info){
            $info->value = htmlspecialchars($value);
            $info->save();
        }else{
            $model = new $this;
            $model->save([
                'skey' => $skey,
                'value' => htmlspecialchars($value)
            ]);
        }
        return [true,'success'];
    }
    //取得参数
    public function getValue($skey)
    {
        if(empty($setting)){
            $info = $this->where('skey',$skey)->find();
            if($info){
                return htmlspecialchars_decode($info['value']);
            }
            return "";
        }else{
            return htmlspecialchars_decode($setting[$skey]);
        }
    }
    //参数校验
    public function check($skey, $value)
    {
        if($skey == 'web_name'){
            if($value == ''){
                return [false,'项目名称不能为空'];
            }
        }
        if($skey == 'web_title'){
            if($value== ''){
                return [false,'项目标题不能为空'];
            }
        }
        if($skey == 'web_logo'){
            if($value== ''){
                return [false,'项目LOGO不能为空'];
            }
        }
        if($skey == 'web_domain'){
            if($value== ''){
                return [false,'项目域名不能为空'];
            }
        }
        return [true,'success'];
    }
    //取得全部参数
    public function getAll()
    {
        $path=Env::get('config_path').'/setting.php';
        $setting=[];
        $list= $this->order('skey desc,id asc')->select();
        $str = "<?php return [\n\r";
        foreach($list as $k => $v){
            $setting[$v['skey']]=htmlspecialchars_decode($v['value']);
            $str .= "\t'{$v['skey']}'=>'{$v['value']}',".PHP_EOL;
        }
        $str .= '];';
        file_put_contents($path,$str);
        return $setting;
    }
}
