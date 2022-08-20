<?php

namespace app\common\taglib;
use think\template\TagLib;
class Zhi extends TagLib {
    protected $tags = [
        'list_join' => ['attr' => 'table,jointable,order,limit,where,id,key,joinfield,fileds','close' => 1],
        'list' => ['attr' => 'table,order,limit,where,id,key','close' => 1],
        'info' => ['attr' => 'table,where,id','close' => 1],
    ];

    public function tagInfo($attr,$content){
        $table = $attr['table']; //要查询的数据表
        $where = $attr['where']; //查询条件
        $id = $attr['id'];
        $str = '<?php ';
        $str .= '$'.$id.' =db("'.$table.'")->where("'.$where.'")->find();';
        $str .= '?>';
        $str .= $content;
        return $str;
    }

    public function tagList_join($attr,$content) {
        $table = $attr['table']; //要查询的数据表
        $order = empty($attr['order'])?'id desc':$attr['order']; //排序
        $limit = empty($attr['limit'])?10:$attr['limit']; //多少条数据
        $where = $attr['where']; //查询条件
        $jointable = $attr['jointable']; //关联表
        $joinfield = $attr['joinfield']; //关联字段
        $fileds=$attr['fileds'];//查询字段
        $id = empty($attr['id'])?'v':$attr['id'];
        $key = empty($attr['key'])?'k':$attr['key'];
        $str = '<?php ';
        $str.='$result = db("'.$table.'")->alias("a")->join("'.config("database.prefix").$jointable.' b","'.$joinfield.'","left")
            ->where("'.$where.'")
            ->field("'.$fileds.'")
            ->limit("'.$limit.'")
            ->order("'.$order.'")
            ->select();';
        $str .= 'foreach ($result as $'.$key.'=>$'.$id.'):';
        $str .= '?>';
        $str .= $content;
        $str .= '<?php endforeach ?>';
        return $str;
    }

    public function tagList($attr,$content) {
        $table = $attr['table']; //要查询的数据表
        $order = empty($attr['order'])?'id desc':$attr['order']; //排序
        $limit = empty($attr['limit'])?10:$attr['limit']; //多少条数据
        $where = $attr['where']; //查询条件
        $id = empty($attr['id'])?'v':$attr['id'];
        $key =empty($attr['key'])?'k':$attr['key'];
        $str = '<?php ';
        $str.='$result = db("'.$table.'")->where("'.$where.'")->limit('.$limit.')->order("'.$order.'")->select();';
        $str .= 'foreach ($result as $'.$key.'=>$'.$id.'):';
        $str .= '?>';
        $str .= $content;
        $str .= '<?php endforeach ?>';
        return $str;
    }
}