<?php


namespace app\manage\controller;

use app\common\model\Admin;
use app\common\model\Zhibo as videoModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class Zhibo extends Common
{

    protected $searchFields = 'zhibo.title,zhibo.content';
    protected $modelValidate = true;
    protected $modelSceneValidate = true;
    protected $sceneTag = 'zhibo';
    protected $dataLimit = true;
    protected $dataLimitField = 'uid';
    protected $relationSearch = true;

    public function initialize()
    {
        parent::initialize();
        $this->model = new videoModel();
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
            // manage/zhibo/index?page=1&limit=10&date=2022-06-01+-+2022-07-29&search=&filter=%7B%7D
            $search = $this->request->get("search", '');
            $date = $this->request->get("date", '');
            $page = $this->request->get("page", 1);
            $limit = $this->request->get("limit", 0);
            $page=$page-1;
            $where = [];
            if($date){
                $dateArr=explode(' - ',trim($date));
                $where[] = ['ctime','between time',$dateArr];
            }
            if ($search) {
               $where[] = ['id',$search];
            }
            $total = $this->model
                ->where($where)
                ->where('sortid',1)
                ->order('id', 'DESC')
                ->count();
            $list = $this->model
                 ->where($where)
                 ->where('sortid',1)
                 ->order('id', 'DESC')
                ->select();
               
            foreach ($list->toArray() as $k => $v){
                if($v['sortid'] == 1){
                    $list[$k]['sortid'] = '免费';
                }elseif($v['sortid'] == 2){
                    $list[$k]['sortid'] = '直播';
                }
            }
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        return $this->view->fetch();
    }
    public function zhibo()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            // manage/zhibo/index?page=1&limit=10&date=2022-06-01+-+2022-07-29&search=&filter=%7B%7D
            $search = $this->request->get("search", '');
            $date = $this->request->get("date", '');
            $page = $this->request->get("page", 1);
            $limit = $this->request->get("limit", 0);
            $page=$page-1;
            $where = [];
            if($date){
                $dateArr=explode(' - ',trim($date));
                $where[] = ['ctime','between time',$dateArr];
            }
            if ($search) {
               $where[] = ['id',$search];
            }
            
            $total = $this->model
                ->where($where)
                ->where('sortid',2)
                ->order('id', 'DESC')
                ->count();
            $list = $this->model
                 ->where($where)
                 ->where('sortid',2)
                 ->order('id', 'DESC')
                ->select();
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
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
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
               
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                       
                        $validate = $this->modelSceneValidate ? $name . '.add' . $this->sceneTag : $name;
                      
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            
                            return callback(404, $result);
                        }
                    }
                    $params['sortid'] = 1;
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        $tags = \app\common\model\VideoSort::where('status', 1)->select();
        $this->view->assign('sort', $tags);
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add2()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
               
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                       
                        $validate = $this->modelSceneValidate ? $name . '.add' . $this->sceneTag : $name;
                      
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            
                            return callback(404, $result);
                        }
                    }
                    $params['sortid'] = 2;
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }
        $tags = \app\common\model\VideoSort::where('status', 1)->select();
        $this->view->assign('sort', $tags);
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
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    if (empty($params['content'])) {
                        return callback(404, '请输入资源信息');
                    }
                    
                    $contents = explode("\n", $params['content']);
                    if (!is_array($contents)) {
                        return callback(404, '资源信息格式错误');
                    }
                    $content_Arr = $this->createYield($contents);
                    foreach ($content_Arr as $content) {
                        if (!empty($content)) {
                            $link = $content;
                            $sortid = 1;
                            $data = [
                                'link' => $link,
                                'sortid' => $sortid,
                                'status' => 1,
                                'uid' => $params['uid'],
                                'ctime' => time(),
                            ];
                            $result = $this->model->insert($data);
                            unset($content);
                            unset($data);
                        }
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据写入失败');
                }
            }
            return callback(404, '参数丢失');
        }

        $tags = \app\common\model\VideoSort::where('status', 1)->select();
        $this->view->assign('sort', $tags);
        return $this->view->fetch();
    }

    /**
     * 转换时间秒
     * @param $duration
     */
    protected function covertTime($duration)
    {
        $second = $duration % 60;#秒
        $minute = intval($duration / 60);#分
        $hour = intval($minute / 60);#小时
        $minute = $minute % 60;
        if (empty($hour)) {
            return substr('0' . $minute, -2) . ':' . substr('0' . $second, -2);
        }
        return substr('0' . $hour, -2) . ':' . substr('0' . $minute, -2) . ':' . substr('0' . $second, -2);
    }

    /**
     * 获取id
     * @param $title
     * @param $cat_id
     * @return mixed
     */
    protected function getCatId($title, $cat_id)
    {
        $sort_arr = \app\common\model\VideoSort::where('status', 1)->select();
        foreach ($sort_arr as $k => $v) {
            if (strpos($title, $v['name']) !== false) {
                return $v['id'];
            }
        }
        return $cat_id;
    }

    /**
     * 构造yield生成器
     */
    protected function createYield($contents)
    {
        foreach ($contents as $item) {
            yield $item;
        }
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404, '数据不存在');
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                return callback(404, '您没有权限');
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
                    // if ($this->modelValidate) {
                    //     $name = $this->validatePath;
                    //     $validate = $this->modelSceneValidate ? $name . '.edit' . $this->sceneTag : $name;
                    //     $result = $this->validate($params, $validate);
                    //     if ($result !== true) {
                    //         return callback(404, $result);
                    //     }
                    // }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据更新失败');
                }
            }
            return callback(404, '参数不能为空');
        }

        $sort = \app\common\model\VideoSort::where('status', 1)->select();
        $this->view->assign('sort', $sort);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    
     /**
     * 编辑
     */
    public function status($id = null)
    {
        $row = $this->model->get($id);
        if (!$row) {
            return callback(404, '数据不存在');
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                return callback(404, '您没有权限');
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post();
           
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                   
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    return callback(404, $e->getMessage());
                }
                if ($result !== false) {
                    return callback(200, 'success');
                } else {
                    return callback(404, '数据更新失败');
                }
            }
            return callback(404, '参数不能为空');
        }

        $sort = \app\common\model\VideoSort::where('status', 1)->select();
        $this->view->assign('sort', $sort);
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
                return callback(404, $e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            }
            if ($count) {
                return callback(200, 'success');
            } else {
                return callback(404, '删除失败');
            }
        }
        return callback(404, '参数ids不能为空');
    }

    /**
     * 清空全部
     */
    public function clearAll()
    {
        if ($this->request->isPost()) {
            try {
                Db::execute("truncate table zp_zhibo");
                return callback(200, 'success');
            } catch (Exception $e) {
                return callback(404, $e->getMessage());
            }
        }
    }
}