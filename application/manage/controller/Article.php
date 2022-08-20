<?php

namespace app\manage\controller;
use app\common\model\Article as articleModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
class Article extends Common{

    protected $searchFields='name';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;

    protected $sceneTag='article';

    protected $relationSearch=true;

    public function initialize(){
        parent::initialize();
        $this->model=new articleModel();
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
                ->withJoin(['sort'=>['name'],'admin'=>['username']],'left')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->withJoin(['sort'=>['name'],'admin'=>['username']],'left')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
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
                    $params['admin_id']=$this->auth->id;
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
        $sort=\app\common\model\ArticleSort::where('status',1)->select();
        $this->view->assign("sort", $sort);
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
        $sort=\app\common\model\ArticleSort::where('status',1)->select();
        $this->view->assign("sort", $sort);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}