<?php


namespace app\manage\controller;

use app\common\model\Admin;
use app\common\model\Hezi as heziModel;
use Endroid\QrCode\QrCode;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class Hezi extends Common
{
    protected $searchFields = 'hezi.title';
    protected $modelValidate = true;
    protected $modelSceneValidate = true;
    protected $sceneTag = 'hezi';
    protected $dataLimit = true;
    protected $dataLimitField = 'uid';
    protected $relationSearch = true;
    public function initialize()
    {
        parent::initialize();
        $this->model = new heziModel();
    }

    /**
     * 盒子列表
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->withJoin(['user' => ['username','id']],'left')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->withJoin(['user' => ['username','id']],'left')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = $list->toArray();
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
                    $params['is_hz'] = 0;
                    if (preg_match('/http:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$params['video_url']) && substr($params['video_url'],-4,4) == 'm3u8'){ 
                        $params['is_hz'] = 1;
                    }
                    if (preg_match('/https:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$params['video_url']) && substr($params['video_url'],-4,4) == 'm3u8'){ 
                        $params['is_hz'] = 1;
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
        return $this->view->fetch();
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
                    $params['is_hz'] = 0;
                    if (preg_match('/http:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$params['video_url']) && substr($params['video_url'],-4,4) == 'm3u8'){ 
                        $params['is_hz'] = 1;
                    }
                    if (preg_match('/https:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$params['video_url']) && substr($params['video_url'],-4,4) == 'm3u8'){ 
                        $params['is_hz'] = 1;
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 获取二维码地址
     */
    public function qrcodeUrl($ids=0)
    {
        $result = $this->shortUrl($ids);
        
        if ($result['status'] == 200) {
            $short_url = $result['data'];
            $qrCode = new QrCode($short_url);
            $qrCode->setSize(300);
            $qrCode->setMargin(10);
            $qrCode->setWriterByName('png');
            header('Content-Type: ' . $qrCode->getContentType());
            echo $qrCode->writeString();
            exit;
        }
        return callback(404, '短链接获取失败');

    }
    /**
     * 获取推广链接
     */
    public function shortUrl($id=0)
    {
        #推广总链接
        //$link = $this->getSpreadUrl();
        $link = "";
        if (empty($id)) {
            $url = $link . url('/haokan').'?ldk=' . encrypt(json_encode(['uid'=>$this->auth->id,'t'=>0]));
        } else {
            
             $info = $this->model->allowField(true)->find($id);
             
             if($info->is_hz == 1){
                $video_url = $info->video_url; 
                $url = $link . url('/haokan').'?ldk=' . encrypt(json_encode(['uid'=>$this->auth->id,'t'=>$id,'hezi'=>$id]));
               
             }else{
                 $url = $link . url('/haokan').'?ldk=' . encrypt(json_encode(['uid'=>$this->auth->id,'t'=>$id]));
             }
        }
        
        #获取防封链接
        $antiUrl=getAntiUrl(1);
        if($antiUrl){
           $url=$antiUrl.base64_encode(urlencode($url));
        }else{
            $url=$antiUrl.$url;
        }
        $short_id = Admin::where('id', $this->auth->id)->value('short_id');
        if (empty($short_id)) {
            $short_id = 1;
        }
        return getDwz($short_id,$url);
    }
    /**
     * 获取推广总链
     */
    protected function getSpreadUrl()
    {
        $sid = $this->auth->id;
        if ($this->auth->admin_id > 0) {
            $sid = 1;
        }
        if (!empty($this->auth->entry_url)) {
            $entry_url = $this->auth->entry_url;
        } else {
            $entry_url = Admin::where('id', $sid)->value('entry_url');
        }
        if ($entry_url) {
            $url = $entry_url;
        } else {
            $domain = trim(getDomain(1, $sid));
            if ($domain) {
                $url = $domain;
            } else {
                $url = '需要添加主域名才能生成盒子链接';
            }
        }
        return trim($url);
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
}