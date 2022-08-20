<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
Route::get('haokan','index/index/index');
Route::get('token','index/index/token');
Route::get('vtoken','index/jf/token');
Route::get('vlist','index/index/lists');
Route::get('fhaokan','index/jf/index');
Route::get('fvlist','index/jf/lists');
Route::get('zlist','index/zhibo/lists');
Route::get('zhibo','index/zhibo/index');
Route::get('zvideo','index/zhibo/video');
Route::get('vsort','index/index/sorts');
Route::rule('video','index/index/video');
Route::rule('fvideo','index/jf/video');
Route::rule('return','index/trade/synNotify');
Route::rule('dreturn','index/trade/dyNotify');
Route::rule('notify/:acname','index/notify/:acname')->pattern(['acname' => '\w+']);
Route::rule('checkOrder','index/trade/checkOrder');
Route::rule('play','index/trade/index');
Route::rule('jfplay','index/trade/jfplay');
Route::rule('qrcode','index/index/qrcode');





