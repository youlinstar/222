<?php

namespace app\manage\controller;
use app\common\lib\WeMsg;
use app\common\model\Bill;
use app\common\model\User;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
class Fundlist extends Common{

    protected $searchFields='money,uid,remark';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;
    protected $sceneTag='bill';
    protected $dataLimitField='uid';
    protected $dataLimit='personal';
    protected $relationSearch=true;

    public function initialize(){
        parent::initialize();
        $this->model=new Bill();
    }
    /**
     * 列表
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if($this->request->request('keyField')){
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->withJoin(['user'=>['id','username']])
                ->where($where)
                ->where('bill.type','in',[1,2,3])
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->withJoin(['user'=>['id','username']])
                ->where($where)
                ->where('bill.type','in',[1,2,3])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list,'total'=>$total];
            return json($result);
        }
        return $this->view->fetch();

    }
}