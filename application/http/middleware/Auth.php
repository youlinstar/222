<?php

namespace app\http\middleware;

use app\common\model\User;
use think\facade\Request;
class Auth
{
    public function handle($request, \Closure $next)
    {
        
        $header = request::header();
        $token=null;
        if(array_key_exists('authorization',$header)){
            $token = $header['authorization'];
        }
        if(empty($token)){
            return json(['code'=>403,'msg'=>'请登录后再操作1','data'=>[],'time'=>time()]);
        }
        $token=str_replace('Bearer ','',$token);
        #验证用户的Token
        $isToken=User::where(['token'=>$token,'status'=>1])->value('id');
        if(empty($isToken)){
            return json(['code'=>403,'msg'=>'请登录后再操作2','data'=>[],'time'=>time()]);
        }

        $request->token=$token;

        return $next($request);
    }
}
