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
Route::rule('haokan','index/index/index');
Route::rule('token','index/index/token');
Route::rule('vtoken','index/jf/token');
Route::rule('vlist','index/index/lists');
Route::rule('fhaokan','index/jf/index');
Route::rule('fvlist','index/jf/lists');
Route::rule('zlist','index/zhibo/lists');
Route::rule('zhibo','index/zhibo/index');
Route::rule('zvideo','index/zhibo/video');
Route::rule('vsort','index/index/sorts');
Route::post('video','index/index/video');
Route::post('fvideo','index/jf/video');
Route::rule('return','index/trade/synNotify');
Route::rule('return_xz','index/trade/synNotifyXz');
Route::rule('dreturn','index/trade/dyNotify');
Route::rule('notify/:acname','index/notify/:acname')->pattern(['acname' => '\w+']);
Route::rule('checkOrder','index/trade/checkOrder');
Route::rule('play','index/trade/index');
Route::rule('jfplay','index/trade/jfplay');
Route::rule('qrcode','index/index/qrcode');





