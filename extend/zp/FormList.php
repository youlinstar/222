<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace zp;
class FormList
{
    public $data = array();

    public function __construct($data = array())
    {
        $this->data = $data;
    }

    public function text($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $parseStr = '<div class="layui-input-inline from-inline-5">';
        $parseStr .= '<input type="text" lay-verType="msg" lay-reqText="请输入' . $info['title'] . '" title="' . $info['title'] . '" placeholder="请输入' . $info['title'] . '" lay-verify="required" class="layui-input" name="' . $info['name'] . '" value="' . $value . '" /> ';
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function color($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $parseStr = '<div class="layui-input-inline" style="width: 145px;"><input type="text" lay-verType="msg" lay-reqText="请选择' . $info['title'] . '"  lay-verify="required" class="layui-input" id="' . $info['name'] . '" name="' . $info['name'] . '" value="' . $value . '" /></div>';
        $parseStr .= '<div class="layui-inline"><div id="sel' . $info['name'] . '" class="layui-inline"></div></div>';
        $parseStr .= "<script>            
                    layui.use('colorpicker', function(){
                    var colorpicker = layui.colorpicker,$=layui.jquery;
                    colorpicker.render({
                        elem: '#sel" . $info['name'] . "',
                        color:'" . $value . "',
                        done: function(color){
                          $('#" . $info['name'] . "').val(color);
                          //譬如你可以在回调中把得到的 color 赋值给表单
                        }
                    });
                });
                </script>";
        return $parseStr;
    }

    public function textarea($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $parseStr = '<div class="layui-input-inline from-inline-5">';
        $parseStr .= '<textarea lay-reqText="请输入' . $info['title'] . '" placeholder="请输入' . $info['title'] . '" lay-verify="required"  class="layui-textarea" name="' . $info['name'] . '" />' . $value . '</textarea>';
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function ueditor($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $str = '<div class="layui-input-inline from-inline-5">';
        $str .= '<textarea name="' . $info['name'] . '" class="js-ueditor" id="ueditor">' . $value . '</textarea>';
        $str .= '<script>var editor = new UE.ui.Editor({serverUrl:\'' . url("ueditor/index") . '\'});editor.render("ueditor");</script>';
        $str .= '</div>';
        $str .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $str;
    }

    public function datetime($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $value = $value ? toDate($value, "Y-m-d H:i:s") : toDate(time(), "Y-m-d H:i:s");
        $parseStr = '<div class="layui-input-inline from-inline-3">';
        $parseStr .= '<input type="datetime" name="' . $info['name'] . '" lay-verify="required" placeholder="请选择' . $info['ttile'] . '" value="' . $value . '" class="layui-input" id="ctime">';
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function number($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $parseStr = '<div class="layui-input-inline">';
        $parseStr .= '<input type="text" class="input-text layui-input" lay-reqText="请输入' . $info['title'] . '" name="' . $info['name'] . '" placeholder="请输入' . $info['title'] . '" value="' . $value . '" lay-verify="required" /> ';
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function select($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $options = explode("\n", $info['values']);
        foreach ($options as $row) {
            $v = explode("|", $row);
            $k = trim($v[1]);
            $values[$k] = $v[0];
        }
        $parseStr = '<div class="layui-input-inline from-inline-3">';
        $parseStr .= '<select name="' . $info['name'] . '">';
        if (is_array($values)) {
            foreach ($values as $key => $val) {
                if (!empty($value)) {
                    $selected = '';
                    if (is_array($value)) {
                        if (in_array($key, $value)) {
                            $selected = ' selected="selected"';
                        }
                    } else {
                        if ($value == $key) {
                            $selected = ' selected="selected"';
                        }
                    }
                    $parseStr .= '<option ' . $selected . ' value="' . $key . '">' . $val . '</option>';
                } else {
                    $parseStr .= '<option value="' . $key . '">' . $val . '</option>';
                }
            }
        }
        $parseStr .= '</select>';
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function checkbox($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $options = explode("\n", $info['values']);
        foreach ($options as $r) {
            $v = explode("|", $r);
            $k = trim($v[1]);
            $values[$k] = $v[0];
        }
        if ($value != '') $value = strpos($value, ',') ? explode(',', $value) : array($value);
        $i = 1;
        $parseStr = '<div class="layui-input-inline from-inline-5">';
        foreach ($values as $key => $r) {
            $key = trim($key);
            $checked = ($value && in_array($key, $value)) ? 'checked' : '';
            $parseStr .= '<input name="' . $info['name'] . '" ' . $checked . ' value="' . htmlspecialchars($key) . '" type="checkbox" title="' . htmlspecialchars($r) . '">';
            $i++;
        }
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function switchs($info)
    {
        $value = empty($this->data[$info['name']]) ? 0 : $this->data[$info['name']];
        $checked = '';
        if ($value == 1) {
            $checked = 'checked';
        }
        $parseStr = '<div class="layui-input-inline from-inline-2">';
        $parseStr .= '<input name="switch_1" ' . $checked . ' value="'.$value.'" lay-filter="' . $info['name'] . '" lay-skin="switch" type="checkbox" lay-text="' . $info['values'] . '"/>';
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        $parseStr .= '<input type="hidden" id="' . $info['name'] . '" name="' . $info['name'] . '" value="'.$value.'" />';
        $parseStr .= "<script>
                layui.use(['form','jquery'], function (){
                    var form = layui.form,$=layui.jquery;
                    form.on('switch(".$info['name'].")', function(data){
                        if(data.elem.checked){
                            $('#". $info['name']."').val(1);
                        }else{
                            $('#". $info['name']."').val(0);
                        }
                    });
                });
                </script>";
        return $parseStr;
    }

    public function radio($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $parseStr = '<div class="layui-input-inline from-inline-5">';
        $options = explode("\n", $info['values']);
        foreach ($options as $r) {
            $v = explode("|", $r);
            $k = trim($v[1]);
            $values[$k] = $v[0];
        }
        $i = 1;
        foreach ($values as $key => $r) {
            $checked = trim($value) == trim($key) ? 'checked' : '';
            if (empty($value) && empty($key)) {
                $checked = 'checked';
            }
            $parseStr .= '<input name="' . $info['name'] . '" ' . $checked . ' value="' . $key . '" type="radio" title="' . $r . '" />';
            $i++;
        }
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function image($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $thumbstr = '<div class="layui-input-inline">';
        $thumbstr .= '<input type="hidden" name="' . $info['name'] . '" id="' . $info['name'] . 'Val" value="' . $value . '">';
        $thumbstr .= '<div class="layui-upload">';
        $thumbstr .= '<button type="button" class="layui-btn layui-btn-primary" id="on' . $info['name'] . '"><i class="icon icon-upload3"></i>点击上传</button>';
        $thumbstr .= '<div class="layui-upload-list"><img class="layui-upload-img" width="auto" height="60" id="' . $info['name'] . 'Img" src="' . $value . '"><p id="thumbText"></p></div>';
        $thumbstr .= '</div>';
        $thumbstr .= "<script> 
                        layui.use('upload', function () {
                            var upload = layui.upload,$= layui.jquery;
                            upload.render({
                                elem:'#on" . $info['name'] . "', 
                                url: '" . url('upFiles/upload') . "',
                                title: '上传图片',
                                done: function(res){
                                    $('#" . $info['name'] . "Img').attr('src',res.url);
                                    $('#" . $info['name'] . "Val').val(res.url);
                                }
                            });
                        });
                    </script>";
        $thumbstr .= '</div>';
        $thumbstr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $thumbstr;
    }

    public function images($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $data = '';
        $i = 0;
        if ($value) {
            $options = explode(";", mb_substr($value, 0, -1));
            if (is_array($options)) {
                foreach ($options as $r) {
                    $data .= '<div class="layui-col-md3"><div class="dtbox"><img src="' . $r . '" class="layui-upload-img"><input type="hidden" class="imgVal" name="' . $info['name'] . '[]" value="' . $r . '"><i class="delimg layui-icon">&#x1006;</i></div></div>';
                }
            }
        }
        $parseStr = '<div class="layui-input-inline from-inline-5">';
        $parseStr .= '<div id="images" class="images"></div><div id="upImg" class="upImg" data-i="' . $i . '">' . $data . '</div>';
        $parseStr .= '<div class="layui-upload">';
        $parseStr .= '<button type="button" class="layui-btn" id="test2">多图片上传</button>';
        $parseStr .= '<blockquote class="layui-elem-quote layui-quote-nm" style="margin-top: 10px;">';
        $parseStr .= '预览图：<div class="layui-upload-list" id="demo2"><div class="layui-row layui-col-space10">' . $data . '</div></div> </blockquote></div>';
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function file($info)
    {
        $value = empty($this->data[$info['name']]) ? '/static/common/images/file.png' : $this->data[$info['name']];
        $thumbstr = '<div class="layui-input-inline">';
        $thumbstr .= '<div class="layui-input-4"><input type="hidden" name="' . $info['name'] . '" id="' . $info['name'] . 'fval" value="' . $value . '">';
        $thumbstr .= '<div class="layui-upload">';
        $thumbstr .= '<button type="button" class="layui-btn layui-btn-primary" id="on' . $info['name'] . '"><i class="icon icon-upload3"></i>点击上传</button>';
        $thumbstr .= '<div class="layui-upload-list"><img class="layui-upload-img" id="' . $info['name'] . 'File" width="90" height="90" src="/static/common/images/file.png"><p id="thumbText"></p></div>';
        $thumbstr .= '</div></div>';
        $thumbstr .= "<script> 
                        layui.use('upload', function () {
                            var upload = layui.upload,$= layui.jquery;
                            upload.render({
                                elem:'#on" . $info['name'] . "', 
                                accept:'file',
                                url: '" . url('upFiles/file') . "',
                                title: '上传文件',
                                done: function(res){
                                    $('#" . $info['name'] . "File').attr('src', '/static/common/images/'+res.ext+'.png');
                                    $('#" . $info['name'] . "fval').val(res.url);
                                }
                            });
                        });
                    </script>";
        $thumbstr .= '</div>';
        $thumbstr .= '<div class="layui-form-mid layui-word-aux">' . $info['remark'] . '</div>';
        return $thumbstr;
    }
}

?>