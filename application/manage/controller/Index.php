<?php

namespace app\manage\controller;

use app\common\model\Admin;
use app\common\model\Agent;
use think\Db;
use think\facade\Cache;

class Index extends Common
{
    protected $noNeedRight = ['logout', 'clear', 'main', 'nav', 'navbar'];

    public function index()
    {
       
        // 获取缓存数据
        $authRule = cache('authRule');
        if (!$authRule) {
            $authRule = Db::name('auth_rule')->where('is_menu=1')->order('sort')->select();
            cache('authRule', $authRule, 3600);
        }
        $info = Agent::where('id',$this->auth->id)->find();
        $is_yqm = $info->is_yqm;
        //声明数组
        $menus = [];
        foreach ($authRule as $key => $val) {
            
            $authRule[$key]['href'] = url($val['href']);
            if ($val['pid'] == 0) {
                if (!$this->auth->isSuperAdmin()) {
                   
                    if (in_array($val['id'], $this->auth->getRuleIds())) {
                        if($is_yqm == 1 && $val['id'] == 396){
                            
                        }else{
                             $menus[] = $val;
                        }
                       
                    }
                } else {
                    $menus[] = $val;
                }
            }

        }
 
        foreach ($menus as $k => $v) {
            foreach ($authRule as $kk => $vv) {
                if ($v['id'] == $vv['pid']) {
                    if (!$this->auth->isSuperAdmin()) {
                        if (in_array($vv['id'], $this->auth->getRuleIds())) {
                            $menus[$k]['children'][] = $vv;
                        }
                    } else {
                        $menus[$k]['children'][] = $vv;
                    }
                }
            }
        }
        $notice=\app\common\model\Article::where(['sort_id'=>1,'status'=>1])->order('ctime desc')->find();
        $this->assign('notice1',$notice);
        $this->assign('menus', $menus);
        return $this->fetch();
    }

    public function main()
    {
        $version = Db::query('SELECT VERSION() AS ver');
        $config = [
            'url' => $_SERVER['HTTP_HOST'],
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'server_os' => PHP_OS,
            'server_port' => $_SERVER['SERVER_PORT'],
            'server_ip' => $_SERVER['SERVER_ADDR'],
            'server_soft' => $_SERVER['SERVER_SOFTWARE'],
            'php_version' => PHP_VERSION,
            'mysql_version' => $version[0]['ver'],
            'max_upload_size' => ini_get('upload_max_filesize')
        ];
        $notice=\app\common\model\Article::where(['sort_id'=>1,'status'=>1])->order('ctime desc')->limit(5)->select();
        $this->assign('notice',$notice);
        $this->assign('config', $config);
        return $this->fetch();
    }

    public function navbar()
    {
        return $this->fetch();
    }

    public function nav()
    {
        return $this->fetch();
    }

    public function clear()
    {
        if (Cache::clear()) {
            return callback(200, '缓存清除成功', url('index/index'));
        } else {
            return callback(400, '缓存清除失败', url('index/index'));
        }
    }

    /**
     * 获取统计
     */
    public function statinfo()
    {
        $maps = [];
        $where = [];
        if ($this->auth->group_id !== 1) {
            $maps = ['uid' => $this->auth->id];
            $where = ['is_kl' => 0];
        }
        #总访问量
        $total_visitor = \app\common\model\SpreadView::where($maps)->count();
        #支付成功订单数
        $total_order = \app\common\model\Order::where($maps)->where($where)->where(['status' => 1])->count();
        #支付成功金额
        $total_money = \app\common\model\Order::where($maps)->where($where)->where(['status' => 1])->sum('money');
        #本月成功金额
        $month_money = \app\common\model\Order::where($maps)->where($where)->whereTime('ptime','month')->where(['status' => 1])->sum('money');
        #代理总金额
        $total_balance=\app\common\model\Admin::where('group_id',2)->sum('balance');
        #系统总扣量金额
        $total_kl = \app\common\model\Order::where($maps)->where($where)->where(['is_kl' => 1])->sum('money');
        #代理提成金额
        $total_tc = \app\common\model\Order::where($maps)->where($where)->where(['status' => 1])->sum('tc_money');

        #代理余额
        $user = Admin::where('id', $this->auth->id)->find();

        #今日访问
        $today_view = \app\common\model\SpreadView::where($maps)->whereTime('ctime','today')->count();
        #昨天访问
        $yesterday_view = \app\common\model\SpreadView::where($maps)->whereTime('ctime','yesterday')->count();
        #今日成功订单
        $today_order = \app\common\model\Order::where($maps)->where($where)->whereTime('ctime','today')->where(['status' => 1])->count();
        #昨日成功订单
        $yesterday_order = \app\common\model\Order::where($maps)->where($where)->whereTime('ctime','yesterday')->where(['status' => 1])->count();
        #今日金额
        $today_money = \app\common\model\Order::where($maps)->where($where)->whereTime('ptime','today')->where(['status' => 1])->sum('money');
        #昨日金额
        $yesterday_money = \app\common\model\Order::where($maps)->where($where)->whereTime('ptime','yesterday')->where(['status' => 1])->sum('money');

        return callback(200, 'success', '', [
            'visitor' => $total_visitor,
            'total_order' => $total_order,
            'total_money' => $total_money,
            'month_money' => $month_money,
            'total_balance' =>$total_balance,
            'total_kl' =>$total_kl,
            'total_tc' =>$total_tc,
            'today_view' => $today_view,
            'yesterday_view' => $yesterday_view,
            'today_order' =>$today_order,
            'yesterday_order' =>$yesterday_order,
            'today_money' =>$today_money,
            'yesterday_money' =>$yesterday_money,

            'balance' => $user->balance
        ]);
       
    }

    public function dataView()
    {
        #访问量
        $days = $this->get7day();
        $visitor = [];
        $where = [];
        if ($this->auth->group_id !== 1) {
            $where = ['uid' => $this->auth->id];
        }
        foreach ($days as $day) {
            $nums = \app\common\model\SpreadView::where($where)
                ->whereTime('ctime', [$day . ' 00:00:00', $day . ' 23:59:59'])->count();
            if (empty($nums)) {
                $nums = 0;
            }
            $visitor[] = $nums;
        }
        #订单数量及订单金额
        $hours = ["06:00", "06:30", "07:00", "07:30", "08:00", "08:30", "09:00", "09:30", "10:00", "11:30", "12:00", "12:30", "13:00", "13:30", "14:00", "14:30", "15:00", "15:30", "16:00", "16:30", "17:00", "17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30", "21:00", "21:30", "22:00", "22:30", "23:00", "23:30"];
        $order_list = [];
        $order_money = [];

        foreach ($hours as $key => $val) {
            if ($key == 0) {
                $maps = [date('Y-m-d 00:00:00'), date('Y-m-d ' . $val)];
            } else {
                $maps = [date('Y-m-d ' . $hours[$key - 1]), date('Y-m-d ' . $val)];
            }
            $nums = \app\common\model\Order::where($where)
                ->whereTime('ctime', $maps)->count();
            $moneys = \app\common\model\Order::where($where)
                ->whereTime('ctime', $maps)->sum('money');

            $order_list[] = $nums;
            $order_money[] = $moneys;
        }
        return callback(200, 'success', '', [
            'date_list' => $days,
            'view_list' => $visitor,
            'order_list' => $order_list,
            'order_money' => $order_money
        ]);
    }

    /**
     * 获取最近七天
     */
    protected function get7day()
    {
        $days = [];
        for ($i = 0; $i < 9; $i++) {
            array_unshift($days, date('Y-m-d', strtotime('-' . $i . ' day')));
        }
        return $days;
    }

    #退出登陆
    public function logout()
    {
        $this->auth->logout();
        $this->redirect('login/index');
    }
    #显示公告
    public function notice($id=0)
    {
        $notice = \app\common\model\Article::where('sort_id', 1)->where('id',$id)->order('ctime desc')->find();
        $this->assign('notice', $notice);
        return $this->fetch();
    }
}