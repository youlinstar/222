<?php


namespace app\manage\lib\traits;

use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
trait Common
{
	/**
	 * 排除前台提交过来的字段
	 * @param $params
	 * @return array
	 */
	protected function preExcludeFields($params)
	{
		if (is_array($this->excludeFields)) {
			foreach ($this->excludeFields as $field) {
				if (key_exists($field, $params)) {
					unset($params[$field]);
				}
			}
		} else {
			if (key_exists($this->excludeFields, $params)) {
				unset($params[$this->excludeFields]);
			}
		}
		return $params;
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
			if ($this->request->request('keyField')) {
				return $this->selectpage();
			}
			list($where, $sort, $order, $offset, $limit) = $this->buildparams();
			$total = $this->model->where($where)->order($sort, $order)->count();
			$list = $this->model->where($where)->order($sort, $order)->limit($offset, $limit)->select();
			$list = $list->toArray();
			$result = ['status' => 200, 'msg' => '获取成功!', 'data' => $list, 'total' => $total];
			return json($result);
		}
		return $this->view->fetch();
	}
	/**
	 * 回收站
	 */
	public function recyclebin()
	{
		//设置过滤方法
		$this->request->filter(['strip_tags']);
		if ($this->request->isAjax()) {
			list($where, $sort, $order, $offset, $limit) = $this->buildparams();
			$total = $this->model->onlyTrashed()->where($where)->order($sort, $order)->count();
			$list = $this->model->onlyTrashed()->where($where)->order($sort, $order)->limit($offset, $limit)->select();
			$result = array("total" => $total, "rows" => $list);
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
					return callback(200, '添加成功');
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
	 * 更新单个字段值
	 */
	public function setUp($ids = "")
	{
		$ids = $ids ? $ids : $this->request->param("ids");
		if ($ids) {
			if ($this->request->has('params') && !empty($this->request->post("params"))) {
				$values = json_decode($this->request->post("params"), true);
				$count = 0;
				Db::startTrans();
				try {
					$pk = $this->model->getPk();
					$list = $this->model->where($pk, 'in', $ids)->select();
					foreach ($list as $item) {
						$count += $item->allowField(true)->isUpdate(true)->save($values);
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
					return callback(404, '更新失败');
				}
			}
		}
		return callback(404, '参数ids不能为空');
	}
	/**
	 * 删除
	 */
	public function del($ids = "")
	{
		if ($ids) {
			$pk = $this->model->getPk();
			$adminIds = $this->getDataLimitAdminIds();
			if (is_array($adminIds)) {
				$this->model->where($this->dataLimitField, 'in', $adminIds);
			}
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
	 * 真实删除
	 */
	public function destroy($ids = "")
	{
		$pk = $this->model->getPk();
		$adminIds = $this->getDataLimitAdminIds();
		if (is_array($adminIds)) {
			$this->model->where($this->dataLimitField, 'in', $adminIds);
		}
		if ($ids) {
			$this->model->where($pk, 'in', $ids);
		}
		$count = 0;
		Db::startTrans();
		try {
			$list = $this->model->onlyTrashed()->select();
			foreach ($list as $k => $v) {
				$count += $v->delete(true);
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
	/**
	 * 导入
	 */
	protected function import()
	{
		$file = $this->request->request('file');
		if (!$file) {
			return callback(404, '文件不能为空');
		}
		$filePath = env('root_path') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $file;
		if (!is_file($filePath)) {
			return callback(404, '文件不存在');
		}
		//实例化reader
		$ext = pathinfo($filePath, PATHINFO_EXTENSION);
		if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
			$this->error(__('Unknown data format'));
		}
		if ($ext === 'csv') {
			$file = fopen($filePath, 'r');
			$filePath = tempnam(sys_get_temp_dir(), 'import_csv');
			$fp = fopen($filePath, "w");
			$n = 0;
			while ($line = fgets($file)) {
				$line = rtrim($line, "\n\r\0");
				$encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
				if ($encoding != 'utf-8') {
					$line = mb_convert_encoding($line, 'utf-8', $encoding);
				}
				if ($n == 0 || preg_match('/^".*"$/', $line)) {
					fwrite($fp, $line . "\n");
				} else {
					fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
				}
				$n++;
			}
			fclose($file) || fclose($fp);
			$reader = new Csv();
		} elseif ($ext === 'xls') {
			$reader = new Xls();
		} else {
			$reader = new Xlsx();
		}
		//导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
		$importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';
		$table = $this->model->getQuery()->getTable();
		$database = \think\Config::get('database.database');
		$fieldArr = [];
		$list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
		foreach ($list as $k => $v) {
			if ($importHeadType == 'comment') {
				$fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
			} else {
				$fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
			}
		}
		//加载文件
		$insert = [];
		try {
			if (!($PHPExcel = $reader->load($filePath))) {
				return callback(404, '数据格式错误');
			}
			$currentSheet = $PHPExcel->getSheet(0);
			//读取文件中的第一个工作表
			$allColumn = $currentSheet->getHighestDataColumn();
			//取得最大的列号
			$allRow = $currentSheet->getHighestRow();
			//取得一共有多少行
			$maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
			$fields = [];
			for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
				for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
					$val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
					$fields[] = $val;
				}
			}
			for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
				$values = [];
				for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
					$val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
					$values[] = is_null($val) ? '' : $val;
				}
				$row = [];
				$temp = array_combine($fields, $values);
				foreach ($temp as $k => $v) {
					if (isset($fieldArr[$k]) && $k !== '') {
						$row[$fieldArr[$k]] = $v;
					}
				}
				if ($row) {
					$insert[] = $row;
				}
			}
		} catch (Exception $exception) {
			return callback(404, $exception->getMessage());
		}
		if (!$insert) {
			return callback(404, '更新失败');
		}
		try {
			//是否包含admin_id字段
			$has_admin_id = false;
			foreach ($fieldArr as $name => $key) {
				if ($key == 'admin_id') {
					$has_admin_id = true;
					break;
				}
			}
			if ($has_admin_id) {
				$auth = Auth::instance();
				foreach ($insert as &$val) {
					if (!isset($val['admin_id']) || empty($val['admin_id'])) {
						$val['admin_id'] = $auth->isLogin() ? $auth->id : 0;
					}
				}
			}
			$this->model->saveAll($insert);
		} catch (PDOException $exception) {
			$msg = $exception->getMessage();
			if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
				$msg = "导入失败，包含【{$matches[1]}】的记录已存在";
			}
			$this->error($msg);
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
		$this->success();
	}
}