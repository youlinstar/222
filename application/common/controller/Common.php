<?php

namespace app\common\controller;

use think\Controller;
class Common extends Controller
{
	protected $uid = 1;
	public function initialize()
	{
		$this->checkClient();
		$from = $this->request->param('f');
		$ldk = $this->request->param('ldk');
	
		if (!empty($from)) {
			$this->uid = decrypt($from);
		}
		if (!empty($ldk)) {
		    $data = json_decode(decrypt($ldk),true);
	
			$this->uid = $data['uid'];
		}
		$this->label_cms();
	}
	/**
	 * 客户端类型
	 */
	protected function checkClient()
	{
		$isDouyin = config('setting.isDouyin');
		if ($isDouyin == 1 && is_douyin()) {
			$this->cuscomHtml('douyin');
		}
		$isQQ = config('setting.isQQ');
		if ($isQQ == 1 && is_qq()) {
			$this->cuscomHtml('qq');
		}
		$isWeixin = intval(config('setting.isWeixin'));
		if ($isWeixin == 1 && is_weixin()) {
			$this->cuscomHtml('wechat');
		}
	}
	/**
	 * 输出自定义内容
	 */
	protected function cuscomHtml($name)
	{
		$str = '<html>
                <head>
                <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
                <meta http-equiv="content-type" content="text/html; charset=utf-8">
                  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
                  <meta content="always" name="referrer">
                  <title>请用浏览器打开</title>
                    <style>
                        body{
                            margin:0;
                            padding:0;
                        }
                        img{
                            max-width:100%;
                            display: block;
                            margin:0 auto;
                        }
                    </style>
                </head>
                <body>
                    <img src="/static/default/img/' . $name . '.jpg"/>
                </body>
                </html>';
		echo $str;
		exit;
	}
	/**
	 * 加载模版
	 * @param $tpl string 模版路径
	 * @return mixed
	 */
	protected function tpl_fetch($tpl)
	{
	   
		#加载自定义模板变量
		return $this->fetch($tpl);
	}
	/**
	 * 加载系统基础模板变量
	 */
	protected function label_cms()
	{
		$zhicms = $GLOBALS['config'];
		
		$this->assign(['zhicms' => $zhicms]);
	}
}