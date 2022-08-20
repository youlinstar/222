<?php

namespace app\manage\controller;
use app\common\model\Template as tempModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
class Template extends Common
{

    protected $searchFields='temp.title,temp.content';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;
    protected $sceneTag='temp';

    public function initialize(){
        parent::initialize();
        $this->model=new tempModel();
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
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
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
        $tags=\app\common\model\VideoSort::where('status',1)->select();
        $this->view->assign('sort',$tags);
        return $this->view->fetch();
    }
    /**
     * 批量推广
     */
    public function batchs(){
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
                    if(empty($params['ids'])){
                        return callback(404,'请选择要推广的资源');
                    }
                    $params['ids']=explode(',',$params['ids']);
                    if(!is_array($params['ids'])){
                        return callback(404,'请选择要推广的资源');
                    }
                    if(empty($params['money'])){
                        return callback(404,'请设置资源金额');
                    }
                    if(empty($params['effect_day'])){
                        return callback(404,'请输入链接有效天数');
                    }
                    $spreadModel=new \app\common\model\Spread();
                    $videos=$this->model->withJoin(['spread'=>['id']])
                            ->where('video.status',1)
                            ->whereIn('video.id',$params['ids'])
                            ->whereNull('spread.id')
                            ->select();
                    if(empty($videos)){
                        return callback(404,'资源发布过了');
                    }
                    foreach ($videos as $video){
                        $params['otime']=time()+($params['effect_day']*24*3600);
                        $params['sortid']=$video->sortid;
                        $params['video_url']=$video->link;
                        $params['video_id']=$video->id;
                        $params['title']=$video->title;
                        $params['img']=$video->thumb;
                        $params['status']=1;
                        $result=$spreadModel->create($params,true);
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
        $tags=\app\common\model\VideoSort::where('status',1)->select();
        $this->view->assign('sort',$tags);
        return $this->view->fetch();
    }
    /**
     * 一键发布推广
     */
    public function setting(){
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
                    if(empty($params['min_money'])){
                        return callback(404,'请输入最小随机金额');
                    }
                    if(empty($params['max_money'])){
                        return callback(404,'请输入最大随机金额');
                    }
                    if(empty($params['effect_day'])){
                        return callback(404,'请输入链接有效天数');
                    }
                    $spreadModel=new \app\common\model\Spread();
                    $videos=$this->model->withJoin(['spread'=>['id']])
                        ->where('video.status',1)
                        ->whereNull('spread.id')
                        ->select();
                    if(empty($videos)){
                        return callback(404,'资源已经发布过了');
                    }
                    foreach ($videos as $video){
                        $params['otime']=time()+($params['effect_day']*24*3600);
                        $params['money']=mt_rand($params['min_money'],$params['max_money']);
                        $params['sortid']=$video->sortid;
                        $params['video_url']=$video->link;
                        $params['video_id']=$video->id;
                        $params['title']=$video->title;
                        $params['img']=$video->thumb;
                        $params['status']=1;
                        $result=$spreadModel->create($params,true);
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
        $tags=\app\common\model\VideoSort::where('status',1)->select();
        $this->view->assign('sort',$tags);
        return $this->view->fetch();
    }
    /**
     * 批量添加资源
     */
    public function batchadd()
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
                    if(empty($params['content'])){
                        return callback(404,'请输入资源信息');
                    }
                    if(empty($params['sortid'])){
                        return callback(404,'请选择资源类目');
                    }
                    $contents=explode("\n",$params['content']);
                    if(!is_array($contents)){
                        return callback(404,'资源信息格式错误');
                    }
                    foreach($contents as $content){
                        if(!empty($content)){
                            list($params['title'],$params['link'],$params['thumb'])=explode('|',$content);
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

        $tags=\app\common\model\VideoSort::where('status',1)->select();
        $this->view->assign('sort',$tags);
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

        $sort=\app\common\model\VideoSort::where('status',1)->select();
        $this->view->assign('sort',$sort);
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
}