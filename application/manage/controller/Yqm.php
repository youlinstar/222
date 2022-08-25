<?php


namespace app\manage\controller;

use app\common\model\Admin;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class Yqm extends Common
{

    public function initialize()
    {
        parent::initialize();
        // $this->model = new videoModel();
    }

    /**
     * 资源列表
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {
            $where = [];
            $page = $this->request->get("page", 1);
            $limit = $this->request->get("limit", 0);
            $page=$page-1;
            $offset=$page*$limit;
            $where['uid'] = $this->auth->id;
            $total = Db::name('yqm')->where($where)->order('id','DESC')->count();

            $list  = Db::name('yqm')->where($where)->order('id','DESC')->limit($offset, $limit)->select();

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
            $num = $this->request->post("num",0);
            if ($num) {
                $result = false;
                Db::startTrans();
                try {
                    $data = [];
                    for($i=0;$i<$num;$i++){
                        $data[$i]['uid'] = $this->auth->id;
                        $data[$i]['yqm'] = md5(rand_string(32) . time());
                        $data[$i]['status']=0;
                        $data[$i]['c_time'] = date('Y-m-d H:i:s',time());
                    }
                    $result = Db::name('yqm')->insertAll($data);
                    Db::commit();

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
     * 删除
     */
    public function del($ids = "")
    {

        if ($ids) {



            Db::startTrans();
            try {
                $list = Db::name('yqm')->where('id', 'in', $ids)->delete();
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            }
            if ($list) {
                return callback(200, 'success');
            } else {
                return callback(404, '删除失败');
            }
        }else{
            return callback(404, '参数ids不能为空');
        }

    }


}
