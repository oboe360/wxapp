<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class HistoryController extends BaseController {
	private $history = NULL;
	private $user = NULL;
	private $store = NULL;
	public function _initializes() {
		!is_null($this -> history) ? : $this -> history = M('history');
		!is_null($this -> user) ? : $this -> user = M('user');
		!is_null($this -> store) ? : $this -> store = M('the_store');
	}
	//获取用户列表
	public function lists() {
		
		//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? I('get.p') : 1;
		$start = ($p - 1) * $showpage;
		//判断高级搜索
		$data = array_merge(I('get.'),I('post.'));
		//设置查询条件
		$where = array();
		//时间条件
		$timewhere = array();
		//判断赋值
		if ($data) {
			!$data['shop_id'] ? : $where['shop_id'] = $data['shop_id'];
			$data['shop_id'] !== '0' ? : $where['shop_id'] = $data['shop_id'];
			!$data['uid'] ? : $where['uid'] = $data['uid'];
			$data['uid'] !== '0' ? : $where['uid'] = $data['uid'];
			!$data['hid'] ? : $where['hid'] = $data['hid'];
			$data['hid'] !== '0' ? : $where['hid'] = $data['hid'];
			if ($data['start'] && $data['end']) {
				$where['time'] = array('egt', strtotime($data['start']));
				$timewhere = 'time <= ' . strtotime($data['end']);
				//				$timewhere = 'appid=2';
			} else {
				$data['start'] ? $where['time'] = array('egt', strtotime($data['start'])) : null;
				$data['end'] ? $where['time'] = array('elt', strtotime($data['end'])) : null;
			}
			//			!$data['start'] ? : $where['add_time'] = array('egt', strtotime($data['start']));
			//			!$data['end'] ? : $where['add_time'] = array('elt', strtotime($data['end']));
			//			$where = array_merge($where,$data);
		}
		//计算指定条件下的用户页数
		$count = $this -> history -> where($where) -> where($timewhere) -> count();
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$historyArr = $this -> history -> where($where) -> where($timewhere) -> order('time desc') -> limit($start, $showpage) -> select();
		foreach ($historyArr as $k => $v) {
			$historyArr[$k]['shop_name']=$this->store->where(array('id'=>$v['shop_id']))->getField('shop_name');
			$historyArr[$k]['nickname']=$this->user->where(array('uid'=>$v['uid']))->getField('nickname');
		}
		// dump($historyArr);
		$page = new \Think\Page($count, $showpage, $data);
		//展示分页
		$show = $page -> show();
		//		echo json_encode($historyArr,$page);
		//		dump($data);
		//		echo strtotime($data['start']);
		//		exit;
		$this -> assign('show', $show);
		$this -> assign('data', $data);
		$this -> assign('arr', $historyArr);
		$this -> display('history/lists');
	}


}
