<?php

namespace app\common\controller;

use think\Controller;
use think\facade\Config;
use app\common\model\Admin;

class Common extends Controller
{
	protected $uid = 1;
	protected $ldk = null;
	public function initialize()
	{		
	    
	    $this->ldk = $this->request->param('ldk');
        if (!empty(request()->routeInfo()['route']) && request()->routeInfo()['route']==='index/trade/synNotifyXz'){
            $this->form = [];
            $this->uid = null;
        }else{
            if(empty($this->ldk)){
                exit('Hello World');
            }

            $data = json_decode(decrypt($this->ldk),true);

            if(empty($data)){
                exit('参数错误');
            }
            $this->form = $data;
            $this->uid = $data['uid'];
        }
	}
	
}