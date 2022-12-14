<?php

namespace app\manage\controller;
use app\common\model\Admin;
use app\common\model\Order as orderModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
class Order extends Common
{
    protected $searchFields='order.ordno,order.id,order.uid,order.ip';
    protected $modelValidate=false;
    protected $modelSceneValidate=true;
    protected $sceneTag='order';
    protected $dataLimit='auth';
    protected $dataLimitField='uid';
    protected $relationSearch=true;

    public function initialize(){
        parent::initialize();
        $this->model=new orderModel();
    }
    /**
     * 资源列表
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
            $maps=[];
           
            $is_agent = 0;
            #如果是代理组，只显示不扣量订单
            if($this->auth->group_id!==1){
                $is_agent = 1;
                $maps=['order.is_kl'=>0];
            }
            // $status=['order.status'=>1];
            $total = $this->model
                ->withJoin(['user'=>['id','username'],'agent'=>['id','username'],'pay'=>['title']])
                ->where($where)
                ->where($maps)
                // ->where($status)
                ->order($sort, $order)
                ->count();
            
            $list = $this->model
                ->withJoin(['user'=>['id','username'],'agent'=>['id','username'],'pay'=>['title']])
                ->where($where)
                ->where($maps)
                // ->where($status)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list,'total'=>$total];
            return json($result);
        }
        
        $is_agent = 0;
        $maps = [];
        $where = [];
         #如果是代理组，只显示不扣量订单
        if ($this->auth->group_id !== 1) {
            $maps = ['uid' => $this->auth->id];
            $where = ['is_kl' => 0];
            $is_agent = 1;
        }
        #今日成功订单
        $today_order = $this->model::where($maps)->where($where)->whereTime('ctime','today')->where(['status' => 1])->count();
        #昨日成功订单
        $yesterday_order = $this->model::where($maps)->where($where)->whereTime('ctime','yesterday')->where(['status' => 1])->count();
        #今日金额
        $today_money = $this->model::where($maps)->where($where)->whereTime('ptime','today')->where(['status' => 1])->sum('money');
        #昨日金额
        $yesterday_money = $this->model::where($maps)->where($where)->whereTime('ptime','yesterday')->where(['status' => 1])->sum('money');
          #支付成功订单数
        $total_order = $this->model::where($maps)->where($where)->where(['status' => 1])->count();
        #支付成功金额
        $total_money = $this->model::where($maps)->where($where)->where(['status' => 1])->sum('money');
        
        #今日扣量成功订单
        $today_kl_order = $this->model::where($maps)->where($where)->whereTime('ctime','today')->where(['is_kl' => 1])->count();
        #昨日扣量成功订单
        $yesterday_kl_order = $this->model::where($maps)->where($where)->whereTime('ctime','yesterday')->where(['is_kl' => 1])->count();
        #今日扣量金额
        $today_kl_money = $this->model::where($maps)->where($where)->whereTime('ptime','today')->where(['is_kl' => 1])->sum('money');
        #昨日扣量金额
        $yesterday_kl_money = $this->model::where($maps)->where($where)->whereTime('ptime','yesterday')->where(['is_kl' => 1])->sum('money');
        #累计扣量成功订单数
        $total_kl_order = $this->model::where($maps)->where($where)->where(['is_kl' => 1])->count();
        #累计扣量成功金额
        $total_kl_money = $this->model::where($maps)->where($where)->where(['is_kl' => 1])->sum('money');
        $data = [
             //订单
            'total_order'=>$total_order,
            'total_money'=>$total_money,
            'today_order'=>$today_order,
            'today_money'=>$today_money,
            'yesterday_order'=>$yesterday_order,
            'yesterday_money'=>$yesterday_money,
             //扣量
            'total_kl_order'=>$total_kl_order,
            'total_kl_money'=>$total_kl_money,
            'today_kl_order'=>$today_kl_order,
            'today_kl_money'=>$today_kl_money,
            'yesterday_kl_order'=>$yesterday_kl_order,
            'yesterday_kl_money'=>$yesterday_kl_money
            
            ];
           
        if ($this->auth->group_id !== 1) {
            return $this->view->fetch('dlindex');
        }
        //  halt($data);
        $this->view->assign("data", $data);
        return $this->view->fetch();
        
    }
    
    /**
     * 查询支付列表
     */
    public function getlist(){
        
        //设置过滤方法
      
        if ($this->request->isAjax()) {
           
           $date = $this->request->get("date", '');
           if($date){
               $dateArr=explode(' - ',trim($date));
               $where[] = ['ctime','between time',$dateArr];
           }else{
               $start = date('Y-m-d',time());
               $end = date('Y-m-d',time()+86400);
               $where[] = ['ctime','between time',[$start,$end]];
           }
           
            $count = $this->model::where($where)->where(['is_kl' => 1])->count();
           
            $money = $this->model::where($where)->where(['is_kl' => 1])->sum('money');
            $pay = \app\common\model\PaySetting::field('id,title')->select();
            $list = [];
            foreach ($pay as $k =>$v){
                $list[$k]['pay_id']=$v['id'];
                $list[$k]['title']=$v['title'];
                if($date){
                    $list[$k]['start']=$dateArr[0];
                    $list[$k]['end']=$dateArr[1];
                }else{
                    $list[$k]['start']=$start;
                    $list[$k]['end']=$end;
                }
                $list[$k]['order'] = $this->model::where($where)->where(['is_kl' => 0,'status'=>1,'pay_id'=>$v['id']])->count();
                $list[$k]['klorder'] = $this->model::where($where)->where(['is_kl' => 1,'pay_id'=>$v['id']])->count();
                $list[$k]['money'] = $this->model::where($where)->where(['is_kl' => 0,'status'=>1,'pay_id'=>$v['id']])->sum('money');
                $list[$k]['klmoney'] = $this->model::where($where)->where(['is_kl' => 1,'pay_id'=>$v['id']])->sum('money');
            }
            $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list];
            return json($result);
        }
        
        
    }
    
    
    
    
    
    /**
     * 编辑订单
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404,'数据不存在');
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField],$adminIds)){
                return callback(404,'您没有权限');
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if($this->modelValidate){
                        $name =$this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.edit'.$this->sceneTag : $name;
                        $result=$this->validate($params,$validate);
                        if($result!==true){
                            return callback(404,$result);
                        }
                    }
                    unset($params['tc_money']);
                    unset($params['money']);
                    unset($params['status']);
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404,$e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404,$e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404,$e->getMessage());
                }
                if ($result !== false) {
                    return callback(200,'success');
                } else {
                    return callback(404,'数据更新失败');
                }
            }
            return callback(404,'参数不能为空');
        }

        $payList=\app\common\model\PaySetting::where('status',1)->select();
        $this->view->assign('payList',$payList);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                return callback(404,$e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                return callback(404,$e->getMessage());
            }
            if ($count) {
                return callback(200,'success');
            } else {
                return callback(404,'删除失败');
            }
        }
        return callback(404,'参数ids不能为空');
    }
     /**
     * 未支付订单
     */
    public function norder()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if($this->request->request('keyField')){
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $status=['order.status'=>0];
            $total = $this->model
                ->withJoin(['user'=>['id','username'],'agent'=>['id','username'],'pay'=>['title']])
                ->where($where)
                ->where($status)
                ->order($sort, $order)
                ->count();
            
            $list = $this->model
                ->withJoin(['user'=>['id','username'],'agent'=>['id','username'],'pay'=>['title']])
                ->where($where)
                ->where($status)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list,'total'=>$total];
            return json($result);
        }
        
        return $this->view->fetch();
        
    }
    
}