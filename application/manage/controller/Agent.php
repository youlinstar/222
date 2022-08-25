<?php

namespace app\manage\controller;
use app\common\model\AdminBill;
use app\common\model\Agent as agentModel;
use app\common\model\AuthGroup;
use app\common\model\Short;
use app\common\model\Order;

use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\facade\Config;

class Agent extends Common
{

    protected $searchFields='agent.username,agent.id,groups.title';

    protected $modelValidate=true;

    protected $modelSceneValidate=true;

    protected $sceneTag='agent';

    protected $dataLimit='auth';

    protected $relationSearch=true;

    public function initialize(){
        parent::initialize();
        $this->model=new agentModel();
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
                ->withSearch(['group_id'],['group_id'=>[2,5]])
                ->withJoin(['groups'=>['title'],'agents'=>['username','id'],'pay'=>['title'],'short'=>['name']])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->withSearch(['group_id'],['group_id'=>[2,5]])
                ->withJoin(['groups'=>['title'],'agents'=>['username','id'],'pay'=>['title'],'short'=>['name']])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = $list->toArray();

            $orderModer = new order();

            foreach ($list as $k => $v){
                if ($this->auth->group_id == 1)
                {
                    $list[$k]['todayKl'] = $orderModer->where(['uid'=>$v['id']])->whereTime('ptime','today')->where(['is_kl' => 1])->sum('money');
                    $list[$k]['yesterdayKl'] = $orderModer->where(['uid'=>$v['id']])->whereTime('ptime','yesterday')->where(['is_kl' => 1])->sum('money');

                }
                $list[$k]['today_money'] = $orderModer->where(['status'=>1,'is_kl'=>0,'uid'=>$v['id']])->whereTime('ctime','today')->sum('money');
                $list[$k]['today_order'] = $orderModer->where(['status'=>1,'is_kl'=>0,'uid'=>$v['id']])->whereTime('ctime','today')->count();
                $list[$k]['yesterday_money'] = $orderModer->where(['status'=>1,'is_kl'=>0,'uid'=>$v['id']])->whereTime('ctime','yesterday')->sum('money');
                $list[$k]['yesterday_order'] = $orderModer->where(['status'=>1,'is_kl'=>0,'uid'=>$v['id']])->whereTime('ctime','yesterday')->count();
            }

            $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list,'total'=>$total];
            
            return json($result);
        }
        if($this->auth->group_id!==1){
            $balance = $this->model->where('group_id',2)->where('admin_id',$this->auth->id)->sum('balance');
        }else{
            $balance = $this->model->where('group_id',2)->sum('balance');
        }
        $payList=\app\common\model\PaySetting::where(['status'=>1])->select();
        $this->view->assign('payList',$payList);
        $this->view->assign("balance", $balance);
        return $this->view->fetch();
    }
    
    public function setAll(){
        
        if ($this->request->isPost()) {
            $type = $this->request->post("type");
            $paytype = $this->request->post("paytype");
            $uidAll = $this->request->post("uidAll");
            $kouliang = $this->request->post("kouliang");
            
            switch ($type) {
                //一键全部修改支付
                case 1:
                    $res = agentModel::where('pay_id','<>',$paytype)->update(['pay_id'=>$paytype]);
                    break;
                case 2:
                  
                    if($uidAll){
                       $uidAll = explode(',',$uidAll);
                       $where[] = ['id','in',$uidAll];
                    }
                    $res = agentModel::where($where)->update(['pay_id'=>$paytype]);
                    break;
                case 3:
                    $res = agentModel::where('take_num','<>',$kouliang)->update(['take_num'=>$kouliang]);
                    break;
                case 4:
                    if($uidAll){
                       $uidAll = explode(',',$uidAll);
                       $where[] = ['id','in',$uidAll];
                    }
                    $where[] = ['take_num'=>$kouliang];
                    $res = $this->model->where($where)->update(['take_num'=>$kouliang]);
                    break;
                default:
                    
                    break;
            }
            if($res){
                return callback(200,'操作成功');
            }else{
                return callback(404,'操作失败');
            }
        }
    }
    
    
    
    /**
     * 密码修改
     */
    public function pwd($ids=0){
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404,'数据不存在');
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    if($params['password']!==$params['epassword']){
                        return callback(404,'二次密码输入错误');
                    }
                    $params['salt']=mt_rand(111111,999999);
                    $params['password']=md5($params['password'].$params['salt']);
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $config = config::get('setting.');
            if ($params) {
                if($params['min_take'] > $config['aget_max_take']){
                    return callback(404,'代理佣金最大设置百分之' . $config['aget_max_take']);
                }
                if($params['money'] < $config['agent_dp_min']){
                    return callback(404,'代理代理最小单片金额' . $config['agent_dp_min']);
                }
                if($params['money1'] < $config['agent_day_min']){
                    return callback(404,'代理代理最小包日金额' . $config['agent_day_min']);
                }
                if($params['money2'] < $config['agent_week_min']){
                    return callback(404,'代理最小包周金额' . $config['agent_week_min']);
                }
                if($params['money3'] < $config['agent_month_min']){
                    return callback(404,'代理最小包月金额' . $config['agent_month_min']);
                }
                $params = $this->preExcludeFields($params);
                if(!$params['admin_id']){
                    if($this->dataLimit && $this->dataLimitFieldAutoFill){
                      
                        $params[$this->dataLimitField] = $this->auth->id;
                    }
                }
                if($params['admin_id']==0){
                    if($this->dataLimit && $this->dataLimitFieldAutoFill){
                      
                        $params[$this->dataLimitField] = $this->auth->id;
                    }
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if($this->modelValidate){
                        $name =$this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.add'.$this->sceneTag : $name;
                        $result=$this->validate($params,$validate);
                        if($result!==true){
                            return callback(404,$result);
                        }
                    }
                    $res=$this->model->where('username',$params['username'])->find();
                    if(!empty($res)){
                        return callback(404,'用户名已经存在');
                    }
                    $params['salt']=mt_rand(111111,999999);
                    $params['password']=md5($params['password'].$params['salt']);

                    $result = $this->model->create($params,true);
                    #写入一键推广
                    // $this->spreadAll($result->id);
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
                    return callback(404,'数据写入失败');
                }
            }
            return callback(404,'参数丢失');
        }
        $payList=\app\common\model\PaySetting::where(['status'=>1])->select();
        $shortList=Short::where('status',1)->select();
        $groupList=AuthGroup::where('is_auth',1)->select();
        $this->view->assign('payList',$payList);
        $this->view->assign('shortList',$shortList);
        $this->view->assign('groupList',$groupList);
        return $this->view->fetch();
    }

    protected function spreadAll($uid){
        $spreadModel = new \app\common\model\Spread();
        $videoModel = new \app\common\model\Video();
        $videos = $videoModel->where('status', '=', 1)->select();
        foreach ($videos as $video) {
            $data = [
                'otime' => time() + 24 * 3600,
                'money' => 5,
                'sortid' => $video['sortid'],
                'video_url' => $video['link'],
                'video_id' => $video['id'],
                'title' => $video['title'],
                'img' => $video['thumb'],
                'status' => 1,
                'uid' => $uid,
                'times' => $video['times'],
                'ctime' => time()
            ];
            $spreadModel->insert($data);
            unset($data);
            unset($video);
        }
    }
    /**
     * 编辑个人资料
     */
    public function personal($ids = null)
    {
      
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404,'数据不存在');
        }
        if ($this->request->isPost()) {

            $params = $this->request->post("row/a");
            if ($params) {
                $config = config::get('setting.');
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
                    #判断修改密码
                    if(!empty($params['password'])){
                        $params['salt']=mt_rand(111111,999999);
                        $params['password']=md5($params['password'].$params['salt']);
                    }else{
                        unset($params['password']);
                    }
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
        $payList=\app\common\model\PaySetting::where(['status'=>1])->select();
        $shortList=Short::where('status',1)->select();
        $groupList=AuthGroup::where('is_auth',1)->select();
        $this->view->assign('payList',$payList);
        $this->view->assign('shortList',$shortList);
        $this->view->assign('groupList',$groupList);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404,'数据不存在');
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if ($params) {
                $config = config::get('setting.');
                if($params['min_take'] > $config['aget_max_take']){
                    return callback(404,'代理佣金最大设置百分之' . $config['aget_max_take']);
                }
                if($params['money'] < $config['agent_dp_min']){
                    return callback(404,'代理最小单片金额' . $config['agent_dp_min']);
                }
                if($params['money1'] < $config['agent_day_min']){
                    return callback(404,'代理最小包日金额' . $config['agent_day_min']);
                }
                if($params['money2'] < $config['agent_week_min']){
                    return callback(404,'代理最小包周金额' . $config['agent_week_min']);
                }
                if($params['money3'] < $config['agent_month_min']){
                    return callback(404,'代理最小包月金额' . $config['agent_month_min']);
                }
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    // if($this->modelValidate){
                    //     $name =$this->validatePath;
                    //     $validate = $this->modelSceneValidate ? $name . '.edit'.$this->sceneTag : $name;
                    //     $result=$this->validate($params,$validate);
                    //     if($result!==true){
                    //         return callback(404,$result);
                    //     }
                    // }
                
                    if($params['admin_id']==0){
                        unset($params['admin_id']);
                    }

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
        $payList=\app\common\model\PaySetting::where(['status'=>1])->select();
        $shortList=Short::where('status',1)->select();
        $groupList=AuthGroup::where('is_auth',1)->select();
        $this->view->assign('payList',$payList);
        $this->view->assign('shortList',$shortList);
        $this->view->assign('groupList',$groupList);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    //一键修改短链接
    public function editAllDwz(){
        if ($this->request->isPost()) {
            $shortId = $this->request->param('short_id',0,'intval');
            if($shortId == 0){
                return callback(404,'非法访问');
            }
            $res = $this->model->select();
            foreach ($res as $list){
                $list->short_id = $shortId;
                $list->save();
            }
            return callback(200,'修改成功');
        }
        $shortList=Short::where('status',1)->select();
        $this->view->assign('shortList',$shortList);
        return $this->view->fetch();
    }
    //一键修改扣量
    public function editAllKl(){
        return $this->view->fetch();
    }
    //批量修改扣量
    public function editKl(){
        return $this->view->fetch();
    }
    //一键修改支付
    public function editAllPay(){
        $payList=\app\common\model\PaySetting::where(['status'=>1])->select();
        $this->view->assign('payList',$payList);
        return $this->view->fetch();
    }
    //批量修改支付
    public function editPay(){
        $payList=\app\common\model\PaySetting::where(['status'=>1])->select();
        $this->view->assign('payList',$payList);
        return $this->view->fetch();
    }
    
}