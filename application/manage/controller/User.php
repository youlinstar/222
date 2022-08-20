<?php

namespace app\manage\controller;
use app\common\model\AdminBill;
use app\common\model\Bill;
use app\common\model\User as UsersModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
class User extends Common{

    protected $searchFields='mobile,username,realname';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;

    protected $sceneTag='user';

    protected $dataLimit='personal';

    protected $relationSearch=true;

    public function initialize(){
        parent::initialize();
        $this->model=new UsersModel();
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
                ->withJoin(['agent'=>['username','mobile','realname'],'province'=>['name'],'city'=>['name'],'county'=>['name']])
                ->where($where)
                ->order('user.id', $order)
                ->count();

            $list = $this->model
                ->withJoin(['agent'=>['username','mobile','realname','group_id'],'province'=>['name'],'city'=>['name'],'county'=>['name']])
                ->where($where)
                ->order('user.id', $order)
                ->limit($offset, $limit)
                ->select();
           
            $list = $list->toArray();
            $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list,'total'=>$total];
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 充值
     */
    public function pay($ids = 0){
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
                    if($this->auth->balance<$params['money']){
                        return callback(404,'余额不足，无法给用户充值');
                    }
                    $remark = '服务商给用户'. $row->username.'余额充值'.$params['money'].'元';
                    list($result,$msg)=Bill::money(1,1,$params['money'],$row['id'],$remark);
                    if(!$result){
                        Db::rollback();
                        return callback(404,'充值操作失败');
                    }
                    #写入服务商日志
                    $remark = '服务商给用户'. $row->username.'充值支出'.$params['money'].'元';
                    list($result,$msg)=AdminBill::money(2,1,$params['money'],$this->auth->id,$remark);
                    if(!$result){
                        Db::rollback();
                        return callback(404,'充值操作失败');
                    }
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
                    if($params['pwd']!==$params['epwd']){
                        return callback(404,'二次密码输入错误');
                    }
                    $params['salt']=mt_rand(111111,999999);
                    $params['pwd']=md5($params['pwd'].$params['salt']);
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
            if ($params) {
                $params = $this->preExcludeFields($params);
                if($this->dataLimit && $this->dataLimitFieldAutoFill){
                    $params[$this->dataLimitField] = $this->auth->id;
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
                    $params['pwd']=md5($params['pwd'].$params['salt']);
                    $result = $this->model->allowField(true)->save($params);
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
        return $this->view->fetch();
    }
}