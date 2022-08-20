<?php


namespace app\common\behavior;

use app\common\model\Admin;
use app\common\model\Template;
use think\facade\Config;
use think\facade\View;
use think\Request;

class ViewInit
{
    public function run(Request $request, $params)
    {
        $uid = 1;
        $from = $request->param('ldk');
       
        if (!empty($from)) {
            $ldk = decrypt($from);
            // halt($ldk);
            $ldk = json_decode($ldk,true);
            
            $uid = $ldk['uid'];
        }
         
        $user = Admin::where('id', $uid)->find();
       
        if (!empty($user)) {
            $GLOBALS['user'] = $user;
        }
        $view_theme = Template::where('id', $user->view_id)->value('label');
        
        if (empty($view_theme)) {
            $view_theme = Config::get('setting.view_theme');
        }
        $GLOBALS['config'] = Config::get('setting.');
        $view_path = Config::get('template.view_path');
        if ($request->module() == 'index') {
            isMobile() ? $client_path = 'wap' : $client_path = 'pc';
            $view_path = $view_path . $view_theme . '/';
            $GLOBALS['config']['client_path'] = $client_path;
            $GLOBALS['config']['tpl_path'] = $view_path;
            View::config('view_path', $view_path);
        }
    }
}