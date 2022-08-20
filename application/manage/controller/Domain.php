<?php

namespace app\manage\controller;
use app\common\model\Admin;
use app\common\model\Domain as domainModel;
use app\common\model\BookSection;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
class Domain extends Common
{

    protected $searchFields='domain.domain';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;
    protected $sceneTag='domain';
    protected $relationSearch=true;

    public function initialize(){
        parent::initialize();
        $this->model=new domainModel();
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
            $total = $this->model
                ->withJoin(['user'=>['username','id']])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->withJoin(['user'=>['username','id']])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['status'=>200,'msg'=>'获取成功!','data'=>$list,'total'=>$total];
            return json($result);
        }
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
                    if(!empty($params['uid'])){
                        $params['is_bind']=1;
                        $params['btime']=time();
                    }
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
        $agent=\app\common\model\Agent::where('status',1)->select();
        $this->view->assign('agent',$agent);
        return $this->view->fetch();
    }
    /**
     * 批量添加
     */
    public function batchadd()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    if(empty($params['content'])){
                        return callback(404,'请输入域名');
                    }
                    if(empty($params['type'])){
                        return callback(404,'请选择类型');
                    }
                    $contents=explode("\n",$params['content']);
                    if(!is_array($contents)){
                        return callback(404,'域名格式错误');
                    }
                    foreach($contents as $content){
                        if(!empty($content)){
                            $params['domain']=$content;
                            if(!empty($params['uid'])){
                                $params['is_bind']=1;
                                $params['btime']=time();
                            }
                            $result=$this->model->create($params,true);
                        }
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
                    return callback(404,'数据写入失败');
                }
            }
            return callback(404,'参数丢失');
        }
        $agent=\app\common\model\Agent::where('status',1)->select();
        $this->view->assign('agent',$agent);
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
                    if(!empty($params['uid'])){
                        $params['is_bind']=1;
                        $params['btime']=time();
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
        $agent=\app\common\model\Agent::where('status',1)->select();
        $this->view->assign('agent',$agent);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 删除拦截域名
     */
    public function delBlock()
    {
        $list = $this->model->where('status', '=', -1)->select();
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
            return callback(200,'删除成功');
        } else {
            return callback(404,'删除失败');
        }
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
}