<?php
/**
 * Created by ZHIPALL.
 * User: workrd 304609001@qq.com
 * Date: 2019/9/2
 * Time: 15:53
 */
namespace addons\test\controller;
use think\addons\Controller;
class Index extends Controller
{
   public function index(){
       return $this->fetch();
   }
}