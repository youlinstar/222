<?php


namespace app\manage\controller;

use app\common\model\Admin;
use think\facade\Config;
use app\common\model\Agent;
use app\common\model\Hezi as heziModel;
use app\common\model\ShortCode;
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

            $map = [];
            $date = $this->request->get("date", '');
            if($date){
                $dateArr=explode(' - ',trim($date));
                $map[] = ['ctime','between time',$dateArr];
            }

            $page = $this->request->get("page", 1);
            $limit = $this->request->get("limit", 0);
            $page=$page-1;
            $offset=$page*$limit;
            #搜索关键字
            $search = $this->request->get("search", '');
            if($search)
            {
                $map[] = ['title', "LIKE", "%{$search}%"];
            }
            $map[] = ['uid','in',$this->auth->id];
            $total = $this->model
                ->where($map)
                ->order('id', 'DESC')
                ->count();
            $list = $this->model
                ->where($map)
                ->order('id', 'DESC')
                ->limit($offset, $limit)
                ->select();

            $list = $list->toArray();
            $result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
            return json($result);
        }
        $user = Agent::where('id', $this->auth->id)->find();
        $this->view->assign('user', $user);
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
     * 获取二维码地址
     */
    public function qrcode()
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
    public function shortUrl($id=0)
    {

        #表单
        $type = $this->request->param('type',0,'intval');

        $id = $this->request->param('id',0,'intval');

        if (empty($id) || empty($type))
        {
            return callback(404, '参数错误');
        }

        switch ($type)
        {
            case 1:
                $rukou = 'haokan';
                break;
            case 2:
                $rukou = 'fhaokan';
                break;
            case 3:
                $rukou = 'zhibo';
                break;
            default:
                $rukou = 'haokan';
                break;

        }

        #获取入口域名
        $link = trim(getDomain(1, $this->auth->id));

        $info = $this->model->allowField(true)->find($id);

        if(empty($info)){
            return callback(404, '数据不存在');
        }

        #盒子
        if($info->is_hz == 1 && $type == 1){

            $ldk = encrypt(json_encode(['uid'=>$info['uid'] ,'t'=>$id,'hezi'=>$id,'type'=>$type]));

        }else{

            $ldk = encrypt(json_encode(['uid'=>$info['uid'] ,'t'=>$id,'type'=>$type]));
        }

        $url = $link . '/' . $rukou . '?ldk=' . $ldk;
        #获取短链接类型
        $short_id = Agent::where('id', $this->auth->id)->value('short_id');
        if (empty($short_id)) {
            $short_id = 1;
        }
        $shortModle = new \app\common\model\Short();
        $short = $shortModle->where('id', $short_id)->find();

        #非原生短链接  跳转


        switch ($short->label) {
            case 'self':#原始链接
            
                $result = callback(200, 'success', '', $url);
                break;
            case 'wechat':#微信
                $url = sprintf($short->api_url,$short->api_token,$url);
                $result = callback(200, 'success', '', $url);
                break;
            case 'tinyurl':#猫咪短链接
                $api = $short->api_url . '?token=' . $short->api_token . '&domain=' . urlencode($url); //新猫咪防封
                $res = json_decode(file_get_contents($api), true);
                if ($res['data']['status_code'] == 0) {
                    $result = callback(200, 'success', '', $res['data']['short_url']);
                }else{
                    $result = callback(404, $res['data']['message']);
                }

                break;
            case 'dwz':#DWZ短网址
                $api = $short->api_url . '?format=json&url=' . urlencode($url); //新猫咪防封
                $res = json_decode(file_get_contents($api), true);
                if ($res['status'] == 0) {
                    $result = callback(200, 'success', '', $res['tinyurl']);
                }else{
                    $result = callback(404, $res['err_msg']);
                }
                break;
            case 'qilin':#麒麟短网址
                $api = $short->api_url . '?username=' . $short->username . '&key=' . $short->api_token . '&url=' .urlencode($url); //新猫咪防封
                $res = json_decode(file_get_contents($api), true);
                if (!empty($res['statu'])) {
                    $result = callback(200, 'success', '', $res['short']);
                }else{
                    $result = callback(404, $res['msg']);
                }
                break;
            case 'selfDwz':
                $ShortCodeObj = new ShortCode();
                $code = $ShortCodeObj->where(['uid'=>$info['uid'],'shortId'=>$id,'type'=>$type])->value('code');
                if(empty($code)){
                    do{
                        $code = rand_string(6);
                        $res = $ShortCodeObj->where('code',$code)->find();
                    }while($res);

                    $data = [
                        'code'=>$code,
                        'ldk'=>$ldk,
                        'type'=>$type,
                        'uid'=>$info['uid'],
                        'rukou'=>$type,
                        'shortId'=>$id
                    ];

                    $res = $ShortCodeObj->allowField(true)->save($data);

                }

                $domain = getDomain(5);

                $url = $domain . '/' . $code;
                $result = ['status'=>200,'msg'=>'success','data'=>$url];
                break;
            default :
                $result = callback(404, '没有可用链接');
                break;
        }

        if($result['status'] == 200){
            

            $qrCode = new QrCode($result['data']);
            $qrCode->setSize(300);
            $qrCode->setMargin(10);
            $qrCode->setWriterByName('png');
            header('Content-Type: ' . $qrCode->getContentType());
            //生达BASE64
            $img = $qrCode->writeDataUri();
            $data = [
                'status' => 200,
                'msg' => 'success',
                'data' => $result['data'],
                'img' => $img,
                'time' => date('Y-m-d H:i:s')
            ];
            return $data;
        }
        return $result;
    }

    /** 推广设置
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function setting()
    {
        if ($this->request->isPost()) {
            $config = config::get('setting.');
//            单片
            $agent_dp_min = $config['agent_dp_min'];
//            包天
            $agent_day_min = $config['agent_day_min'];
//            包周
            $agent_week_min = $config['agent_week_min'];
//            包月
            $agent_month_min = $config['agent_month_min'];

            $params = $this->request->param();
            if ($params) {
                if($params['money'] < $agent_dp_min){
                    return callback(404, '单片最低' . $agent_dp_min . '元');
                }
                if($params['money1'] < $agent_day_min){
                    return callback(404, '包天最低' . $agent_day_min . '元');
                }
                if($params['money2'] < $agent_week_min){
                    return callback(404, '包周最低' . $agent_week_min . '元');
                }
                if($params['money3'] < $agent_month_min){
                    return callback(404, '包月最低' . $agent_month_min . '元');
                }
                $params = $this->preExcludeFields($params);
                $result = false;
                $M = new Agent();
                Db::startTrans();
                try {


                    $result = $M->allowField(true)->save($params,['id'=>$this->auth->id]);

                    Db::commit();

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
        $user = Agent::where('id', $this->auth->id)->find();
        $this->view->assign('user', $user);
        return $this->view->fetch();
    }
}