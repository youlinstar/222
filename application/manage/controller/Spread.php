<?php


namespace app\manage\controller;

use app\common\model\Admin;
use app\common\model\Agent;
use app\common\model\Short;
use app\common\model\Spread as spreadModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;

class Spread extends Common
{

    protected $searchFields = 'spread.title,spread.id';
    protected $modelValidate = true;
    protected $modelSceneValidate = true;
    protected $sceneTag = 'spread';
    protected $dataLimit = 'personal';
    protected $dataLimitField = 'uid';
    protected $relationSearch = true;

    public function initialize()
    {
        parent::initialize();
        $this->model = new spreadModel();
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->withJoin(['sort' => ['name']], 'left')
                ->where($where)
                ->where('uid', $this->auth->id)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->withJoin(['sort' => ['name']], 'left')
                ->where($where)
                ->where('uid', $this->auth->id)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            
            $userinfo = Agent::where('id',$this->auth->id)->find();
            foreach ($list as $k => $v){
                $list[$k]['money']=$userinfo['money'];
                $list[$k]['money1']=$userinfo['money1'];
                $list[$k]['money2']=$userinfo['money2'];
                $list[$k]['money3']=$userinfo['money3'];
            }
            
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        $user = Admin::where('id', $this->auth->id)->find();
        $this->view->assign('user', $user);
        return $this->view->fetch();
    }

    /**
     * 获取二维码地址
     */
    public function qrcodeUrl()
    {
        $result = $this->shortUrl();
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
    // public function shortUrl()
    // {
    //     $id = $this->request->param('id/d', 0);
    //     echo $this->auth->id;die;
    //     #推广总链接
    //     $link = $this->getSpreadUrl();
    //     if (empty($id)) {
    //         $url = $link . url('/haokan').'?ldk=' . encrypt(json_encode(['uid'=>$this->auth->id,'t'=>0]));
    //     } else {
    //         $url = $link . url('/haokan').'?ldk=' . encrypt(json_encode(['uid'=>$this->auth->id,'t'=>$id,'hezi'=>$id]));
    //     }
    //     #获取防封链接
    //     $antiUrl=getAntiUrl(1);
    //     if($antiUrl){
    //       $url=$antiUrl.base64_encode(urlencode($url));
    //     }else{
    //         $url=$antiUrl.$url;
    //     }
    //     $short_id = Admin::where('id', $this->auth->id)->value('short_id');
    //     if (empty($short_id)) {
    //         $short_id = 1;
    //     }
    //     $shortModle = new \app\common\model\Short();

    //     $short = $shortModle->where('id', $short_id)->find();
    //     if($short->label != 'dwzStart'){
    //         return getDwz($short,$url);
    //     }
        
        
    // }
    
    
    


    /**
     * 获取推广总链
     */
    // protected function getSpreadUrl()
    // {
    //     $sid = $this->auth->id;
    //     if ($this->auth->admin_id > 0) {
    //         $sid = 1;
    //     }
    //     if (!empty($this->auth->entry_url)) {
    //         $entry_url = $this->auth->entry_url;
    //     } else {
    //         $entry_url = Admin::where('id', $sid)->value('entry_url');
    //     }
    //     if ($entry_url) {
    //         $url = $entry_url;
    //     } else {
    //         $domain = trim(getDomain(1, $sid));
    //         if ($domain) {
    //             $url = $domain;
    //         } else {
    //             $url = '需要添加主域名才能生成盒子链接';
    //         }
    //     }
    //     return trim($url);
    // }

    /**
     * 视频预览
     */
    public function play($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return callback(404, '数据不存在');
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 批量修改单片价格
     */
    public function batchMoney($ids)
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
                    $where[] = ['uid', '=', $this->auth->id];
                    if (empty($params['min_money'])) {
                        return callback(404, '请输入最小随机金额');
                    }
                    if (empty($params['max_money'])) {
                        return callback(404, '请输入最大随机金额');
                    }
                    if ($params['max_money'] < $params['min_money']) {
                        return callback(404, '最大随机金额必须大于最小金额');
                    }
                    $min_pub = Admin::where('id', $this->auth->id)->value('min_pub');
                    if ($min_pub > 0) {
                        if ($params['min_money'] < $min_pub) {
                            return callback(404, '最小金额不能低于' . $min_pub . '元');
                        }
                    }
                    if (!empty($ids)) {
                        $ids = explode(',', $ids);
                        $where[] = ['id', 'in', $ids];
                    }
                    $result = $this->model->where($where)->update([
                        'money' => Db::raw('floor(' . $params['min_money'] . '+rand()*' . ($params['max_money'] - $params['min_money']) . ')')
                    ]);
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
        $this->view->assign('min_pub', $min_pub);
        return $this->view->fetch('money');
    }
    
    /**
    积分、VIP群、
        
    
    */
    
    public function set()
    {
       
        if ($this->request->isPost()) {
            $qrcode_url = $this->request->param('qrcode_url/s', '');
            $zb = $this->request->param('zb/d', 0);
            $jf = $this->request->param('jf/d', 0);
            $where[] = ['id', '=', $this->auth->id];
            $data = [];
            
            $data['qrcode_url'] = $qrcode_url;
         
            $data['zb'] = $zb;
         
            $data['jf'] = $jf;
          
           
            $obj = new Admin();
            $res = $obj->where($where)->update($data);
            if($res){
                return callback(200, '成功');
            }else{
                return callback(404, '失败');
                
            }
        }
       
    }
    
    
    /**
     * 批量修改
     * @throws Exception
     * @throws PDOException
     * @throws \think\db\exception\DbException
     */
    public function batchEdit()
    {
        if ($this->request->isPost()) {
            $num = $this->request->param('money/d', 0);
            $type = $this->request->param('type/d', 0);
            $where[] = ['id', '=', $this->auth->id];
            
            switch ($type) {
                case 1:#金额
                    $data = ['money' => $num];
                    break;
                case 2:#试看
                    $data = ['try_see' => $num];
                    if ($this->auth->group_id = 2) {
                        if ($num > config('setting.try_see')) {
                            return callback(404, '试看时间不能超过' . config('setting.try_see') . '秒');
                        }
                    }

                    break;
                case 3:#包天
                    $data = ['money1' => $num];
                    if ($this->auth->group_id = 2) {
                        if ($num < config('setting.agent_day_min')) {
                            return callback(404, '包日金额不能低于' . config('setting.agent_day_min') . '元');
                        }
                    }
                    break;
                case 4:#包周
                    $data = ['money2' => $num];
                    if ($this->auth->group_id = 2) {
                        if ($num < config('setting.agent_week_min')) {
                            return callback(404, '包周金额不能低于' . config('setting.agent_week_min') . '元');
                        }
                    }
                    break;
                case 5:#包月
                    $data = ['money3' => $num];
                    if ($this->auth->group_id = 2) {
                        if ($num < config('setting.agent_month_min')) {
                            return callback(404, '包月金额不能低于' . config('setting.agent_month_min') . '元');
                        }
                    }
                    break;
            }
            $result = Agent::where($where)->update($data);
            
            if (!$result) {
                return callback(404, '修改失败');
            }
            return callback(200, '修改成功');
        }
    }
    
    /**
     * 批量修改
     * @throws Exception
     * @throws PDOException
     * @throws \think\db\exception\DbException
     */
    public function statusEdit()
    {
        if ($this->request->isPost()) {
            $type = $this->request->param('type/d', 0);
            $status = $this->request->param('status/d', 0);
            $where[] = ['id', '=', $this->auth->id];
           
            switch ($type) {
                case 1:#单片
                    $data = ['is_dp' => $status];
                    break;
                case 3:#包天
                    $data = ['is_day' => $status];
                    break;
                case 4:#包周
                    $data = ['is_week' => $status];
                    break;
                case 5:#包月
                    $data = ['is_month' => $status];
                    break;
            }
            
            $result = Agent::where($where)->update($data);
            
            if (!$result) {
                return callback(404, '修改失败');
            }
            return callback(200, '修改成功');
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
                    $params['otime'] = strtotime($params['otime']);
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
    public function clearAll(){
        if($this->request->isPost()){
            try{
                Db::execute("truncate table zp_spread");
                return callback(200, 'success');
            }catch (Exception $e){
                return callback(404, $e->getMessage());
            }
        }
    }
    
}