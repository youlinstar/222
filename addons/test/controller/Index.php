<?php

namespace addons\test\controller;
use think\addons\Controller;
class Index extends Controller
{
   public function index(){
       return $this->fetch();
   }
}