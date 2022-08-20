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

    protected $searchFields = 'video.title,video.content';
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
               
            $spread_ids = \app\common\model\Spread::where('uid', $this->auth->id)->column('video_id');
            foreach ($list as $k => $v) {
                $list[$k]['spread'] = '';
                if (in_array($v['id'], $spread_ids)) {
                    $list[$k]['spread'] = 1;
                }
            }
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
     * 批量发布推广
     */
    public function batchs()
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
                    if (empty($params['ids'])) {
                        return callback(404, '请选择要推广的资源');
                    }
                    $params['ids'] = explode(',', $params['ids']);
                    if (!is_array($params['ids'])) {
                        return callback(404, '请选择要推广的资源');
                    }
                    if (empty($params['money'])) {
                        return callback(404, '请设置资源金额');
                    }
                    if (empty($params['effect_day'])) {
                        return callback(404, '请输入链接有效天数');
                    }
                    $spreadModel = new \app\common\model\Spread();
                    $spread_ids = $spreadModel->where('uid', $this->auth->id)->column('video_id');
                    $videos = $this->model->where('id', 'not in', $spread_ids)->whereIn('id', $params['ids'])->select()->toArray();
                    if (empty($videos)) {
                        return callback(404, '资源已经发布过了');
                    }
                    foreach ($videos as $video) {
                        $params['otime'] = time() + ($params['effect_day'] * 24 * 3600);
                        $params['sortid'] = $video['sortid'];
                        $params['video_url'] = $video['link'];
                        $params['video_id'] = $video['id'];
                        $params['title'] = $video['title'];
                        $params['times'] = $video['times'];
                        $params['img'] = $video['thumb'];
                        $params['status'] = 1;
                        $result = $spreadModel->create($params, true);
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
     * 一键发布推广
     */
    public function setting()
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
                    if (empty($params['min_money'])) {
                        return callback(404, '请输入最小随机金额');
                    }
                    if (empty($params['max_money'])) {
                        return callback(404, '请输入最大随机金额');
                    }
                    if (empty($params['effect_day'])) {
                        return callback(404, '请输入链接有效天数');
                    }
                    if ($params['max_money'] < $params['min_money']) {
                        return callback(404, '最大随机金额必须大于最小金额');
                    }
                    $spreadModel = new \app\common\model\Spread();
                    $spread_ids = $spreadModel->where('uid', $this->auth->id)->column('video_id');
                    $videos = $this->model->where('id', 'not in', $spread_ids)->select()->toArray();
                    if (empty($videos)) {
                        return callback(404, '资源已经发布过了');
                    }
                    $videos_list = $this->createYield($videos);
                    foreach ($videos_list as $video) {
                        $data = [
                            'otime' => time() + ($params['effect_day'] * 24 * 3600),
                            'money' => mt_rand($params['min_money'], $params['max_money']),
                            'sortid' => $video['sortid'],
                            'video_url' => $video['link'],
                            'video_id' => $video['id'],
                            'title' => $video['title'],
                            'times' => $video['times'],
                            'img' => $video['thumb'],
                            'status' => 1,
                            'uid' => $params['uid'],
                            'ctime' => time()
                        ];
                        $result = $spreadModel->insert($data);
                        unset($data);
                        unset($video);
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
        //获取代理最低发布金额
        $min_pub = Admin::where('id', $this->auth->id)->value('min_pub');
        if ($min_pub == 0) {
            $min_pub = 1;
        }
        $tags = \app\common\model\VideoSort::where('status', 1)->select();
        $this->view->assign('sort', $tags);
        $this->view->assign('min_pub', $min_pub);
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
                // thumb link
                // 43.248.130.104:2100
                
                $videos = $this->model->where('link','like',$params['old'].'%')->select()->toArray();
                
              
                if($videos){
                    $result = false;
                    Db::startTrans();
                    try {
                        
                        $list = [];
                        foreach ($videos as $k => $v) {
                            $list[$k]['id'] = $v['id'];
                            $list[$k]['link'] = str_replace($params['old'],$params['new'],$v['link']);
                            $list[$k]['thumb'] = str_replace($params['old'],$params['new'],$v['thumb']);
                           
                        }
                        
                        $result = $this->model->saveAll($list);
                        $spreadModel = new \app\common\model\Spread();
                        // video_url img
                        $spreads = Db::name('Spread')->where('video_url','like',$params['old'].'%')->select();
                        $list1 = [];
                        foreach ($spreads as $k => $v) {
                            $list1[$k]['id'] = $v['id'];
                            $list1[$k]['video_url'] = str_replace($params['old'],$params['new'],$v['video_url']);
                            $list1[$k]['img'] = str_replace($params['old'],$params['new'],$v['img']);
                            
                        }
                        
                        $result = $spreadModel->saveAll($list1);
                        
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
        
//         row[content]: 111111111|http://www.baidu.com|http://www.baidu.com|06:03
// row[type]: 1
// row[sortid]: 0
        
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
                                $link = $items[1];
                                $thumb = $items[2];
                                
                            } elseif ($params['type'] == 2) {
                                $title = $items[0];
                                $thumb = $items[1];
                                $link = $items[2];
                            } elseif ($params['type'] == 3) {
                                $thumb = $items[0];
                                $link = $items[1];
                                $title = $items[2];
                            } else {
                                $title = $items[0];
                                $link = $items[1];
                                $thumb = $items[2];
                            }
                            $times = $items[3];
                            $sortid = !empty($params['sortid']) ? $params['sortid'] : $this->getCatId($title, 81);
                            $title = str_replace('】', '', str_replace('【', '', $title));
                            $data = [
                                'title' => $title,
                                'link' => $link,
                                'thumb' => $thumb,
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
           // 查询
           
            $obj =  $this->model
                    ->where('id',$id)
                    ->find();
            $obj->sorts = $sorts;
           
            $obj->save();
            $spread = new Spread();
            $spread =  $spread->where('video_id',$id)
                    ->find();
            $spread->sorts = $sorts;
           
            $spread->save();
         
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
            $spread = new Spread();
            $spreadList = $spread->where('video_id', 'in', $ids)->select();
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                }
                $count = 0;
                foreach ($spreadList as $k => $v) {
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