<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class ShopuserController extends BaseController {
	private $user = NULL;

	//获取用户列表
	public function lists() {
		!is_null($this -> user) ? : $this -> user = D('shopUser');
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
			!$data['id'] ? : $where['id'] = $data['id'];
			$data['id'] !== '0' ? : $where['id'] = $data['id'];
			!$data['sj_uid'] ? : $where['sj_uid'] = $data['sj_uid'];
			$data['sj_uid'] !== '0' ? : $where['sj_uid'] = $data['sj_uid'];
			!$data['nickname'] ? : $where['nickname'] = array('like', '%' . $data['nickname'] . '%');
			if ($data['start'] && $data['end']) {
				$where['add_time'] = array('egt', strtotime($data['start']));
				$timewhere = 'add_time <= ' . strtotime($data['end']);
				//				$timewhere = 'appid=2';
			} else {
				$data['start'] ? $where['add_time'] = array('egt', strtotime($data['start'])) : null;
				$data['end'] ? $where['add_time'] = array('elt', strtotime($data['end'])) : null;
			}
			//			!$data['start'] ? : $where['add_time'] = array('egt', strtotime($data['start']));
			//			!$data['end'] ? : $where['add_time'] = array('elt', strtotime($data['end']));
			//			$where = array_merge($where,$data);
		}
		//计算指定条件下的用户页数
		$count = $this -> user -> where($where) -> where($timewhere) -> count();
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$userArr = $this -> user -> where($where) -> where($timewhere) -> order('add_time desc') -> limit($start, $showpage) -> select();


		$page = new \Think\Page($count, $showpage, $data);
		//展示分页
		$show = $page -> show();
		//		echo json_encode($userArr,$page);
		//		dump($data);
		//		echo strtotime($data['start']);
		//		exit;
		$this -> assign('show', $show);
		$this -> assign('data', $data);
		$this -> assign('arr', $userArr);
		$this -> display('Shopuser/lists');
	}


}
