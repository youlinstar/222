<?php


namespace app\manage\controller;

use app\common\model\Admin;
use app\common\model\Bill;
use app\common\model\Cash as cashModel;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class Cash extends Common
{

    protected $searchFields = 'cash.uid,cash.money,cash.remark';
    protected $modelValidate = true;
    protected $modelSceneValidate = true;
    protected $sceneTag = 'cash';
    protected $dataLimit = 'auth';
    protected $dataLimitField = 'uid';
    protected $relationSearch = true;

    public function initialize()
    {
        parent::initialize();
        $this->model = new cashModel();
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
                ->withJoin(['user' => ['username', 'id']])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->withJoin(['user' => ['username', 'id']])
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
                    $user = Admin::where('id', $this->auth->id)->find();
                    if ($params['money'] > $user->balance) {
                        return callback(404, '可提现余额不足');
                    }
                    #最低提现
                    $min_cash = config('setting.min_cash');
                    $min_money = $user->min_cash;
                    if (empty($min_money)) {
                        $min_money = $min_cash;
                    }
                    if ($params['money'] < $min_money) {
                        return callback(404, '提现金额最低' . $min_money . '元');
                    }
                    #提现时间
                    $cash_time = config('setting.cash_time');
                    $cashTime = explode('-', $cash_time);
                    $time = time();
                    if ($time < strtotime(date('Y-m-d ' . $cashTime[0])) || $time > strtotime(date('Y-m-d ' . $cashTime[1]))) {
                        return callback(404, '每日提现时间' . $cash_time);
                    }
                    #提现次数
                    $cash_num = config('setting.cash_num');
                    $count = $this->model->where(['uid' => $this->auth->id])->whereTime('ctime', 'today')->count();
                    if ($count >= $cash_num) {
                        return callback(404, '每日只能提现' . $cash_num . '次');
                    }
                    $fee = bcdiv(bcmul($params['money'], $user->cash_fee, 2), 100, 2);
                    $amount = bcsub($params['money'], $fee, 2);
                    if ($amount !== $params['amount']) {
                        return callback(404, '提现金额不正确');
                    }
                    $params['fee'] = $fee;
                    $params['pid'] = $this->auth->admin_id;
                    $params['uid'] = $this->auth->id;
                    $params['ctime'] = time();
                    $result = $this->model->create($params, true);
                    if (!$result) {
                        return callback(404, '提现申请提交失败');
                    }
                    #写入余额记录
                    $remark = '【' . $user->username . '】用户提现' . $params['money'] . '元，手续费' . $fee . '元，实际到账' . $amount . '元';
                    list($res, $info) = Bill::money(2, 2, $params['money'], $this->auth->id, $remark, $result->id);
                    if (!$res) {
                        Db::rollback();
                        return callback(404, '提现申请提交失败');
                    }
                    Db::commit();
                    return callback(200, '提现申请提交成功');
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
            }
            return callback(404, '参数丢失');
        }
        $user = Admin::where('id', $this->auth->id)->find();
        $this->view->assign('user', $user);

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
     * 验证提现密码
     * @return mixed
     */
    public function verifyPwd()
    {
        if ($this->request->isPost()) {
            Db::startTrans();
            try {
                $pwd = $this->request->param("pwd");
                if ($pwd) {
                    $admin = Admin::where('id', $this->auth->id)->find();
                    if (!$admin) {
                        return callback(404, '操作失败');
                    }
                    if (empty($admin->cash_pwd)) {
                        return callback(404, '您还未设置提现密码');
                    }
                    if ($admin->cash_pwd !== $pwd) {
                        return callback(404, '提现密码输入错误');
                    }
                    return callback(200, 'success');
                }
                return callback(404, '请输入提现密码');
            } catch (Exception $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            }
        }
    }

    /**
     * 设置提现密码
     * @return mixed
     */
    public function pwd()
    {
        if ($this->request->isPost()) {
            Db::startTrans();
            try {
                $params = $this->request->post("row/a");
                if ($params) {
                    $admin = Admin::where('id', $this->auth->id)->find();
                    if (!$admin) {
                        return callback(404, '操作失败');
                    }
                    if ($admin->cash_pwd !== $params['opwd'] && !empty($admin->cash_pwd)) {
                        return callback(404, '原提现密码输入错误，设置失败');
                    }
                    if ($params['epwd'] !== $params['npwd']) {
                        return callback(404, '二次新密码输入不一致，设置失败');
                    }
                    if ($admin->cash_pwd == $params['epwd']) {
                        return callback(404, '新密码与原密码相同，设置失败');
                    }
                    $admin->cash_pwd = $params['epwd'];
                    $res = $admin->save();
                    if (!$res) {
                        return callback(404, '提现密码设置失败');
                    }
                    Db::commit();
                    return callback(200, 'success');
                }
                return callback(404, '操作失败');
            } catch (Exception $e) {
                Db::rollback();
                return callback(404, $e->getMessage());
            }
        }
        return $this->view->fetch();
    }

    /**
     * 提现审核
     * @return mixed
     */
    public function passed()
    {
        if ($this->request->isPost()) {
            Db::startTrans();
            try {
                $params = $this->request->post("row/a");
                if ($params) {
                    $cashed = $this->model->where('id', $params['ids'])->find();
                    if (!$cashed) {
                        return callback(404, '操作失败');
                    }
                    switch ($params['status']) {
                        case 1:#审核通过
                            $cashed->reason = $params['reason'];
                            $cashed->status = 1;
                            $cashed->utime = time();
                            $res = $cashed->save();
                            if (!$res) {
                                Db::rollback();
                                return callback(404, '提现打款操作失败');
                            }
                            break;
                        case -1://拒绝
                            $remark = '账户提现退回' . $cashed->money . '元，退回原因:' . $params['reason'];
                            list($res, $info) = Bill::money(1, 2, $cashed->money, $cashed->uid, $remark, $cashed->id);
                            if (!$res) {
                                Db::rollback();
                                return callback(404, $info);
                            }
                            $cashed->reason = $params['reason'];
                            $cashed->status = -1;
                            $cashed->utime = time();
                            $res = $cashed->save();
                            if (!$res) {
                                Db::rollback();
                                return callback(404, '提现打款操作失败');
                            }
                            break;
                    }
                    Db::commit();
                    return callback(200, 'success');
                }
                return callback(404, '操作失败');
            } catch (Exception $e) {
                Db::rollback();
                doSyslog($e->getLine(), 'cashed');
                doSyslog(json_encode($e->getTraceAsString()), 'cashed');
                doSyslog(json_encode($e->getCode()), 'cashed');
                doSyslog($e->getMessage(), 'cashed');
                return callback(404, $e->getMessage());
            }
        }
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
}