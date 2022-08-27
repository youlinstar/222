<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <weibo.com/luofei614>
// +----------------------------------------------------------------------

namespace zp;
use think\Db;
use think\facade\Config;
use think\facade\Request;

/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth=new Auth();  $auth->check('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth=new Auth();  $auth->check('规则1,规则2','用户id','and')
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个用户组(think_auth_group_access表 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(think_auth_group 定义了用户组权限)
 * 4，支持规则表达式。
 *      在think_auth_rule 表中定义一条规则，condition字段就可以定义规则表达式。 如定义{score}>5  and {score}<100
 * 表示用户的分数在5-100之间时这条规则才会通过。
 */
class Auth
{

    /**
     * @var object 对象实例
     */
    protected static $instance;
    protected $rule_id = 0;

    /**
     * 当前请求实例
     * @var Request
     */
    protected $request;
    //默认配置
    protected $config = [
        'auth_on'           => 1, // 权限开关
        'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group'        => 'auth_group', // 用户组数据表名
        'auth_rule'         => 'auth_rule', // 权限规则表
        'auth_user'         => 'admin', // 用户信息表
    ];

    public function __construct()
    {
        if ($auth = Config::get('auth')) {
            $this->config = array_merge($this->config, $auth);
        }
        // 初始化request
        $this->request = Request::instance();
    }

    /**
     * 初始化
     * @access public
     * @param array $options 参数
     * @return Auth
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     * 检查权限
     * @param string|array $name     需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param int          $uid      认证用户的id
     * @return bool 通过验证返回true;失败返回false
     */
    public function check($name, $uid)
    {
        if (!$this->config['auth_on']) {
            return true;
        }
        //获取用户需要验证的所有有效规则列表
        $rules = $this->getRuleIds($uid);

        if (in_array('*', $rules)){
            return true;
        }
        $this->rule_id = Db::name($this->config['auth_rule'])->where('href',$name)->value('id');
        if($this->rule_id){
            if(in_array($this->rule_id,$rules)){
                return true;
            }
        }
        return false;
    }
    public function getRuleIds($uid)
    {
        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = []; //保存用户所属用户组设置的所有权限规则id
        if(!empty($groups)){
            $ids = array_merge($ids,explode(',', trim($groups['rules'], ',')));
        }
        return $ids;
    }
    /**
     * 根据用户id获取用户组,返回值为数组
     * @param  int $uid 用户id
     * @return array  用户所属组的信息
     */
    public function getGroups($uid)
    {
        static $groups = [];
        if (isset($groups[$uid])) {
            return $groups[$uid];
        }
        // 执行查询
        $user_rules = Db::name($this->config['auth_user'])
            ->alias('a')
            ->join('__' . strtoupper($this->config['auth_group']) . '__ b', 'a.group_id = b.id')
            ->where("a.id='{$uid}' and b.status=1")
            ->field('a.id,a.group_id,b.title,b.rules,b.is_auth')
            ->find();
        $groups[$uid] = $user_rules ? $user_rules : [];
        return $groups[$uid];
    }
    /**
     * 获得用户资料
     * @param int $uid 用户id
     * @return mixed
     */
    protected function getUserInfo($uid)
    {
        static $user_info = [];

        $user = Db::name($this->config['auth_user']);
        // 获取用户表主键
        $_pk = is_string($user->getPk()) ? $user->getPk() : 'id';
        if (!isset($user_info[$uid])) {
            $user_info[$uid] = $user->where($_pk, $uid)->find();
        }
        return $user_info[$uid];
    }
}