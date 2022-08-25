<?php


namespace app\manage\controller;

use app\common\model\Admin;
use app\common\model\Spread;
use app\common\model\Video as videoModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class Video extends Common
{

    protected $searchFields = 'video.title';
    protected $modelValidate = true;
    protected $modelSceneValidate = true;
    protected $sceneTag = 'video';
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
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->dataLimit = false;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->withJoin(['sort' => ['name']])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->withJoin(['sort' => ['name']])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
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
     * 一键修改网址
     */
    public function alledit()
    {
         
        if ($this->request->isPost()) {
            $params = $this->request->post();
            if ($params) {

                $videos = $this->model->where('link','like',$params['old'].'%')->select()->toArray();
                
              
                if($videos){
                    $result = false;
                    Db::startTrans();
                    try {

                        $list = [];
                        foreach ($videos as $k => $v) {
                            $list[$k]['id'] = $v['id'];
                            $list[$k]['video_url'] = str_replace($params['old'],$params['new'],$v['video_url']);
                            $list[$k]['img'] = str_replace($params['old'],$params['new'],$v['img']);
                           
                        }

                        $result = $this->model->saveAll($list);
                        
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
                }else{
                    return callback(404, '操作失败');
                }
            }
            return callback(404, '参数丢失');
        }
        
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
                    if (empty($params['type'])) {
                        return callback(404, '请选择资源格式类型');
                    }
                    $contents = explode("\n", $params['content']);
                    if (!is_array($contents)) {
                        return callback(404, '资源信息格式错误');
                    }
                    $content_Arr = $this->createYield($contents);
                
                    foreach ($content_Arr as $content) {
                        if (!empty($content)) {
                            $items = explode('|', $content);
                            if (empty($items[3])) {
                                $times = null;
                            }
                            if ($params['type'] == 1) {
                                $title = $items[0];
                                $video_url = $items[1];
                                $img = $items[2];
                                
                            } elseif ($params['type'] == 2) {
                                $title = $items[0];
                                $img = $items[1];
                                $video_url = $items[2];
                            } elseif ($params['type'] == 3) {
                                $img = $items[0];
                                $video_url = $items[1];
                                $title = $items[2];
                            } else {
                                $title = $items[0];
                                $video_url = $items[1];
                                $img = $items[2];
                            }
                            $times = $items[3];
                            $sortid = !empty($params['sortid']) ? $params['sortid'] : $this->getCatId($title, 81);
                            $title = str_replace('】', '', str_replace('【', '', $title));
                            $data = [
                                'title' => $title,
                                'video_url' => $video_url,
                                'img' => $img,
                                'times' => is_numeric($times) ? $this->covertTime($times) : $times,
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
                    if ($this->modelValidate) {
                        $name = $this->validatePath;
                        $validate = $this->modelSceneValidate ? $name . '.edit' . $this->sceneTag : $name;
                        $result = $this->validate($params, $validate);
                        if ($result !== true) {
                            return callback(404, $result);
                        }
                    }
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
     * 更新排序
     */
    public function setSort($ids = null)
    {
         if ($this->request->isAjax()) {
            
            $sorts = $this->request->post("sorts");
            $id = $this->request->post("ids");
            # todo 查询
            $obj =  $this->model
                    ->where('id',$id)
                    ->find();
            $obj->sorts = $sorts;
           
            $obj->save();
         
            $result = ['status' => 200, 'msg' => '操作成功!'];
            return json($result);
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
                Db::execute("truncate table zp_video");
                return callback(200, 'success');
            } catch (Exception $e) {
                return callback(404, $e->getMessage());
            }
        }
    }
}