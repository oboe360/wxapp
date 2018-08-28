<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class AdminlogController extends BaseController {
	private $adminlog = NULL;
	private $admin = NULL;
	public function _initializes() {
		!is_null($this -> adminlog) ? : $this -> adminlog = M('adminLog');
		!is_null($this -> admin) ? : $this -> admin = M('adminUser');
		
	}

	//获取订单列表
	public function lists() {
		//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? : 1;
		$start = ($p - 1) * $showpage;
		//判断高级搜索
		$data = I('post.') ? : I('get.');
		//判断订单状态跳转
		$status = I('get.');
		//设置查询条件
		$where = array();
		//时间条件
		$timewhere = array();
		//判断赋值
		if ($data) {
			!$data['ip_address'] ? : $where['ip_address'] = array('like', '%' . $data['ip_address'] . '%');
			$data['ip_address'] !== '0' ? : $where['ip_address'] = array('like', '%' . $data['ip_address'] . '%');
			if ($data['start'] && $data['end']) {
				$where['log_time'] = array('egt', strtotime($data['start']));
				$timewhere = 'log_time <= ' . strtotime($data['end']);
				//				$timewhere = 'ip_address=2';
			} else {
				$data['start'] ? $where['log_time'] = array('egt', strtotime($data['start'])) : null;
				$data['end'] ? $where['log_time'] = array('elt', strtotime($data['end'])) : null;
			}
			!$data['user_id'] ? : $where['user_id'] = $data['user_id'];
			//			!$data['start'] ? : $where['add_time'] = array('egt', strtotime($data['start']));
			//			!$data['end'] ? : $where['add_time'] = array('elt', strtotime($data['end']));
			//			$where = array_merge($where,$data);
		}
		//计算指定条件下的订单页数
		$count = $this -> adminlog -> where($where) -> where($timewhere) -> count();
		if(ceil($count/$showpage) < $p  && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$adminlogArr = $this -> adminlog -> where($where) -> where($timewhere) -> order('log_time desc') -> limit($start, $showpage) -> select();

		//获取管理员名称
		foreach ($adminlogArr as $k => $v) {
			$adminlogArr[$k]['user_name'] = $this->admin->where(array('user_id'=>$v['user_id']))->getField('user_name');
		}
		$page = new \Think\Page($count, $showpage, $data);
		//展示分页
		$show = $page -> show();
		//		echo json_encode($adminlogArr,$page);
//				dump($data);
		//		echo strtotime($data['start']);
//				exit;
		$this -> assign('show', $show);
		$this -> assign('data', $data);
		$this -> assign('arr', $adminlogArr);
		$this -> display('adminlog/lists');
	}

}
