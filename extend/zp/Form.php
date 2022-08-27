<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */
namespace zp;
class Form{
    public $data = array();
    public function __construct($data=array()) {
        $this->data = $data;
    }

    public function sortid($info,$value){
        $validate = getvalidate($info);
        $category=cache('catelist');
        if(empty($category)){
            $list = db('category')->select();
            foreach ($list as $lk=>$v){
                $category[$v['id']] = $v;
            }
        }
        $id = $field = $info['field'];
        $value = $value ? $value : $this->data[$field];
        $array=[];
        foreach($category as $r){
            if($r['type']==1){
                continue;
            }
            if($r['is_child']){
                $r['disabled'] = ' disabled';
            }else{
                $r['disabled'] = ' ';
            }
            $array[] = $r;
        }
        $str  = "<option value='\$id' \$disabled \$selected>\$spacer \$name</option>";
        $tree = new Tree ($array);
        $parseStr = '<select id="'.$id.'" lay-verify="required" name="'.$field.'"  '.$validate.'>';
        $parseStr .= '<option value="">请选择'.$info['name'].'</option>';
        $parseStr .= $tree->get_tree(0, $str, $value);
        $parseStr .= '</select>';
        return $parseStr;
    }

    public function text($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $field = $info['field'];
        $name = $info['name'];

        $info['setting']['password'] ? $inputtext = 'password' : $inputtext = 'text';
        $action = ACTION_NAME;
        if($action=='add'){
            $value = $value ? $value : $info['setting']['default'];
        }else{
            $value = $value ? $value : $this->data[$field];
        }
        $pattern='';
        if($info['is_must']==1){
            $pattern='required';
        }
        if($info['pattern']!='default'){
            $pattern.='|'.$info['pattern'];
        }
        $parseStr   = '<input type="'.$inputtext.'" min="'.$info['min'].'" lay-verType="msg" max="'.$info['max'].'" lay-reqText="'.$info['msg'].'" title="'.$name.'" placeholder="请输入'.$name.'" lay-verify="'.$pattern.'" class="layui-input" name="'.$field.'" value="'.$value.'" /> ';
        if($info['is_must']==1){
            $parseStr .='</div>';
            $parseStr .='<div class="layui-form-mid layui-word-aux red">*必填';
        }
        return $parseStr;
    }

    public function textarea($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $field = $info['field'];
        $name = $info['name'];
        $action = ACTION_NAME;
        if($action=='add'){
            $value = $value ? $value : $info['setting']['default'];
        }else{
            $value = $value ? $value : $this->data[$field];
        }
        $pattern='';
        if($info['is_must']==1){
            $pattern='required';
        }
        if($info['pattern']!='default'){
            $pattern.='|'.$info['pattern'];
        }
        $parseStr   = '<textarea min="'.$info['min'].'" max="'.$info['max'].'" lay-reqText="'.$info['msg'].'" title="'.$name.'" placeholder="请输入'.$name.'" lay-verify="'.$pattern.'"  class="layui-textarea" name="'.$field.'" />'.$value.'</textarea>';
        if($info['is_must']==1){
            $parseStr .='</div>';
            $parseStr .='<div class="layui-form-mid layui-word-aux red">*必填';
        }
        return $parseStr;
    }

    public function editor($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $field = $info['field'];
        $name = $info['name'];
        $pattern='';
        if($info['is_must']==1){
            $pattern='required';
        }
        if($info['pattern']!='default'){
            $pattern.='|'.$info['pattern'];
        }
        $action = ACTION_NAME;
        if($action=='add'){
            $value = $value ? $value : '';
        }else{
            $value = $value ? $value : $this->data[$field];
        }
        if($info['setting']['edittype']=='UEditor'){
            //配置文件
            $str ='';
            $str .='<input type="hidden" id="editType" value="1">';
            $str .='<textarea name="'.$field.'" class="js-ueditor" id="ueditor">'.$value.'</textarea>';
            $str .='<script>var editor = new UE.ui.Editor({serverUrl:\''.url("ueditor/index",['wather'=>$info['setting']['wather']]).'\'});editor.render("ueditor");</script>';
        }else{
            $str ='';
            $str .='<input type="hidden" id="editType" value="0">';
            if($value){
                $str .='<textarea name="'.$field.'" min="'.$info['min'].'" max="'.$info['max'].'" lay-reqText="'.$info['msg'].'" title="'.$name.'" placeholder="请输入'.$name.'" lay-verify="'.$pattern.'" class="layedit" id="layedit" >'.$value.'</textarea>';
            }else{
                $str .='<textarea name="'.$field.'" min="'.$info['min'].'" max="'.$info['max'].'" lay-reqText="'.$info['msg'].'" title="'.$name.'" placeholder="请输入'.$name.'" lay-verify="'.$pattern.'" class="layedit" id="layedit" >请输入……</textarea>';
            }
            $str .='<script>layui.use("layedit", function () {var layedit = layui.layedit;layedit.set({uploadImage: {url: \''.url("UpFiles/editUpload").'\',type: \'post\'}});edittext[\'layedit\'] = layedit.build("layedit");})</script>';
        }
        return $str;
    }

    public function datetime($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $field = $info['field'];
        $name = $info['name'];
        $action = ACTION_NAME;
        if($action=='add'){
            $value = $value ? $value : '';
        }else{
            $value = $value ? $value : $this->data[$field];
        }
        $value = $value ?  toDate($value,"Y-m-d H:i:s") : toDate(time(),"Y-m-d H:i:s");
        $pattern='';
        if($info['is_must']==1){
            $pattern='required';
        }
        if($info['pattern']!='default'){
            $pattern.='|'.$info['pattern'];
        }
        $parseStr = '<input type="datetime" title="'.$name.'" name="'.$field.'" lay-verify="'.$pattern.'" placeholder="请输入'.$name.'" value="'.$value.'" class="layui-input" id="ctime">';
        if($info['is_must']==1){
            $parseStr .='</div>';
            $parseStr .='<div class="layui-form-mid layui-word-aux red">*必填';
        }
        return $parseStr;
    }

    public function number($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $id = $field = $info['field'];
        $validate = getvalidate($info);
        if(isset($info['setting']['password'])){
            $inputtext = 'passowrd';
        }else{
            $inputtext = 'text';
        }
        $action = ACTION_NAME;
        if($action=='add'){
            $value = $value ? $value : $info['setting']['default'];
        }else{
            $value = $value ? $value : $this->data[$field];
        }
        if(isset($info['setting']['size'])){
            $size = $info['setting']['size'];
        }else{
            $size = "";
        }
        $parseStr   = '<input type="'.$inputtext.'" class="input-text layui-input" name="'.$field.'"  id="'.$id.'" value="'.$value.'" size="'.$size.'"  '.$validate.'/> ';
        return $parseStr;
    }

    public function select($info,$value){

        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $id = $field = $info['field'];
        $action = ACTION_NAME;
        if($action=='add'){
            $value = $value ? $value : $info['setting']['default'];
        }else{
            $value = $value ? $value : $this->data[$field];
        }
        if($value != '') $value = strpos($value, ',') ? explode(',', $value) : $value;
        if(is_array($info['options'])){
            $optionsarr = $info['options'];
        }else{
            $options = explode("\n",$info['setting']['options']);
            foreach($options as $r) {
                $v = explode("|",$r);
                $k = trim($v[1]);
                $optionsarr[$k] = $v[0];
            }
        }
        if(!empty($info['setting']['multiple'])) {
            $onchange = '';
            if(isset($info['setting']['onchange'])){
                $onchange = $info['setting']['onchange'];
            }
            $parseStr = '<select id="'.$id.'" name="'.$field.'"  onchange="'.$onchange.'" size="'.$info['setting']['size'].'" multiple="multiple" >';
        }else {
            $onchange = '';
            if(isset($info['setting']['onchange'])){
                $onchange = $info['setting']['onchange'];
            }
            $parseStr = '<select id="'.$id.'" name="'.$field.'" onchange="'.$onchange .'">';
        }

        if(is_array($optionsarr)) {
            foreach($optionsarr as $key=>$val) {
                if(!empty($value)){
                    $selected='';
                    if(is_array($value)){
                        if(in_array($key,$value)){
                            $selected = ' selected="selected"';
                        }
                    }else{
                        if($value==$key){
                            $selected = ' selected="selected"';
                        }
                    }
                    $parseStr   .= '<option '.$selected.' value="'.$key.'">'.$val.'</option>';
                }else{
                    $parseStr   .= '<option value="'.$key.'">'.$val.'</option>';
                }
            }
        }
        $parseStr   .= '</select>';
        return $parseStr;
    }

    public function checkbox($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $id = $field = $info['field'];
        $action = ACTION_NAME;
        if($action=='add'){
            $value = $value ? $value : $info['setting']['default'];
        }else{
            $value = $value ? $value : $this->data[$field];
        }
        if(is_array($info['options'])){
            $optionsarr = $info['options'];
        }else{
            $options = explode("\n",$info['setting']['options']);
            foreach($options as $r) {
                $v = explode("|",$r);
                $k = trim($v[1]);
                $optionsarr[$k] = $v[0];
            }
        }
        if($value != '') $value = strpos($value, ',') ? explode(',', $value) : array($value);
        $i = 1;
        $parseStr ='';
        foreach($optionsarr as $key=>$r) {
            $key = trim($key);
            $checked = ($value && in_array($key, $value)) ? 'checked' : '';
            $parseStr .= '<input name="'.$field.'['.$i.']" id="'.$id.'_'.$i.'" '.$checked.' value="'.htmlspecialchars($key).'"  type="checkbox" class="ace" title="'.htmlspecialchars($r).'">';
            $i++;
        }
        return $parseStr;

    }
    public function switchs($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $field = $info['field'];
        $action = ACTION_NAME;
        if ($action == 'add') {
            $value = $value ? $value : $info['setting']['default'];
        } else {
            $value = $value ? $value : $this->data[$field];
        }
        $checked='';
        if($value==1){
            $checked = 'checked';
        }
        $parseStr = '<input name="'.$field.'" '.$checked.' value="1" lay-skin="switch" type="checkbox" lay-text="'.$info['setting']['options'].'"/>';

        return $parseStr;
    }
    public function radio($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $id = $field = $info['field'];
        $action = ACTION_NAME;
        if ($action == 'add') {
            $value = $value ? $value : $info['setting']['default'];
        } else {
            $value = $value ? $value : $this->data[$field];
        }
        $parseStr='';
        if (isset($info['options'])) {
            if (is_array($info['options'])) {
                $optionsarr = $info['options'];
            }
        } else if (isset($info['setting']['options'])) {
            $options = explode("\n", $info['setting']['options']);
            foreach ($options as $r) {
                $v = explode("|", $r);
                $k = trim($v[1]);
                $optionsarr[$k] = $v[0];
            }
        }else {
            return $parseStr;
        }
        $i = 1;
        foreach($optionsarr as $key=>$r) {
            $checked = trim($value)==trim($key) ? 'checked' : '';
            if(empty($value) && empty($key) ){
                $checked = 'checked';
            }
            $parseStr .= '<input name="'.$field.'" id="'.$id.'_'.$i.'" '.$checked.' value="'.$key.'" type="radio" class="ace" title="'.$r.'" />';
            $i++;
        }
        return $parseStr;
    }

    public function image($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $field = $info['field'];
        $action = ACTION_NAME;
        $thumb=empty($info['setting']['thumb'])?0:$info['setting']['thumb'];
        $width=empty($info['setting']['width'])?0:$info['setting']['width'];
        $height=empty($info['setting']['height'])?0:$info['setting']['height'];
        if($action=='add'){
            $value =$value?$value:"/static/admin/images/default.png";
        }else{
            if($this->data[$field]){
                $value = $value ?$value : $this->data[$field];
            }else{
                $value = "/static/admin/images/default.png";
            }
        }
        $thumbstr ='<div class="layui-input-4"><input type="hidden" name="'.$field.'" id="'.$field.'Val" value="'.$value.'"><div class="layui-upload">';
        $thumbstr .='<button type="button" class="layui-btn layui-btn-primary" id="on'.$field.'"><i class="icon icon-upload3"></i>点击上传</button>';
        $thumbstr .='<div class="layui-upload-list"><img class="layui-upload-img" width="90" height="90" id="'.$field.'Img" src="'.$value.'"><p id="thumbText"></p></div>';
        $thumbstr .='</div></div>';
        $thumbstr.="<script> 
                        layui.use('upload', function () {
                            var upload = layui.upload;
                            upload.render({
                                elem:'#on".$field."',
                                data:{
                                    thumb:".$thumb.",
                                    width:".$width.",
                                    height:".$height.",
                                },
                                url: '".url('upFiles/upload')."',
                                title: '上传图片',
                                ext: '".$info['setting']['upload_allowext']."', 
                                done: function(res){
                                    $('#".$field."Img').attr('src',res.url);
                                    $('#".$field."Val').val(res.url);
                                }
                            });
                        });
                    </script>";
        return $thumbstr;
    }

    public function images($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $field = $info['field'];
        $action = ACTION_NAME;
        if($action=='add'){
            $value = $value ? $value : $info['setting']['default'];
        }else{
            $value = $value ? $value : $this->data[$field];
        }
        $data='';
        $i=0;
        if($value){
            $options = explode(";",mb_substr($value,0,-1));
            if(is_array($options)){
                foreach($options as  $r) {
                    $data .='<div class="layui-col-md3"><div class="dtbox"><img src="'.$r.'" class="layui-upload-img"><input type="hidden" class="imgVal" name="'.$field.'[]" value="'.$r.'"><i class="delimg layui-icon">&#x1006;</i></div></div>';
                }
            }
        }
        $parseStr   = '<div id="images" class="images"></div><div id="upImg" class="upImg" data-i="'.$i.'">'.$data.'</div>';
        $parseStr   .= '<div class="layui-upload">';
        $parseStr   .= '<button type="button" class="layui-btn" id="moreImg">多图片上传</button>';
        $parseStr   .= '<blockquote class="layui-elem-quote layui-quote-nm" style="margin-top: 10px;">';
        $parseStr   .= '预览图：<div class="layui-upload-list" id="imglist"><div class="layui-row layui-col-space10">'.$data.'</div></div> </blockquote></div>';
        return $parseStr;
    }

    public function file($info,$value){
        $info['setting']=is_array($info['setting']) ? $info['setting'] : json_decode($info['setting'],true);
        $field = $info['field'];
        $action = ACTION_NAME;
        $fileArr=explode('.',$this->data[$field]);
        $ext=$fileArr[1];
        if($action=='add' or $ext==''){
            $value ="/static/common/images/file.png";
        }else{
            $value = "/static/common/images/".$ext.".png";
        }
        $thumbstr ='<div class="layui-input-4"><input type="hidden" name="'.$field.'" id="'.$field.'fval" value="'.$this->data[$field].'"><div class="layui-upload">';
        $thumbstr .='<button type="button" class="layui-btn layui-btn-primary" id="on'.$field.'"><i class="icon icon-upload3"></i>点击上传</button>';
        $thumbstr .='<div class="layui-upload-list"><img class="layui-upload-img" width="90" height="90" id="'.$info['class'].'File" src="'.$value.'"><p id="thumbText"></p>';
        $thumbstr .='</div></div></div>';
        $thumbstr.="<script> 
                        layui.use('upload', function () {
                            var upload = layui.upload;
                            upload.render({
                                elem:'#on".$field."', 
                                accept:'file',
                                url: '".url('upFiles/file')."',
                                title: '上传文件',
                                ext: '".$info['setting']['upload_allowext']."', 
                                done: function(res){
                                    $('#".$field."File').attr('src', '/static/common/images/'+res.ext+'.png');
                                    $('#".$field."fval').val(res.url);
                                }
                            });
                        });
                    </script>";
        return $thumbstr;
    }
}
?>