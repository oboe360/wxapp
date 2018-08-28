<?php
namespace Admin\Controller;
header("Access-Control-Allow-Origin:*");
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class QianbaoController extends BaseController {
	private $qianbao = NULL;
	private $user = NULL;
	public function _initialize() {
		!is_null($this -> qianbao) ? : $this -> qianbao = M('QianbaoSn');
		!is_null($this -> user) ? : $this -> user = M('user');
	}

	//获取订单列表
	public function lists() {
		//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? I('get.p') : 1;
		$start = ($p - 1) * $showpage;
		//判断高级搜索
		$data = array_merge(I('get.'),I('post.'));
		//判断订单状态跳转
		$status = I('get.');
		//设置查询条件
		$where = array();
		//时间条件
		$timewhere = array();
		//判断赋值
		if ($data) {
			!$data['order_sn'] ? : $where['order_sn'] = array('like', '%' . $data['order_sn'] . '%');
			$data['order_sn'] !== '0' ? : $where['order_sn'] = array('like', '%' . $data['order_sn'] . '%');
			!$data['buyid'] ? : $where['buyid'] = array('like', '%' . $data['buyid'] . '%');
			$data['buyid'] ? : $where['buyid'] = array('like', '%' . $data['buyid'] . '%');
			if($data['start'] && $data['end']){
				$where['order_time'] = array('egt',strtotime($data['start']));
				$timewhere = 'order_time <= '.strtotime($data['end']);
//				$timewhere = 'appid=2';
			}else{
				$data['start'] ?$where['order_time'] = array('egt',strtotime($data['start'])):null;
				$data['end'] ?$where['order_time'] = array('elt',strtotime($data['end'])):null;
			}
			
//			!$data['start'] ? : $where['add_time'] = array('egt', strtotime($data['start']));
//			!$data['end'] ? : $where['add_time'] = array('elt', strtotime($data['end']));
			//			$where = array_merge($where,$data);
		}
		//计算指定条件下的订单页数
		$count = $this -> qianbao -> where($where) -> where($timewhere) -> count();
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$qianbaoArr = $this -> qianbao -> where($where) -> where($timewhere) -> order('add_time desc') -> limit($start, $showpage) -> select();

		foreach ($qianbaoArr as $k => $v) {
			$qianbaoArr[$k]['buy_name'] = $this->user->where(array('uid'=>$v['buyid']))->getField('nickname');
			$qianbaoArr[$k]['income_name'] = $this->user->where(array('uid'=>$v['income_uid']))->getField('nickname');
		}
		$page = new \Think\Page($count,$showpage,$data);
		//展示分页
		$show = $page->show();
//		echo json_encode($qianbaoArr,$page);
//		dump($data);
//		echo strtotime($data['start']);
//		exit;
		$this->assign('show',$show);
		$this->assign('data',$data);
		$this -> assign('arr', $qianbaoArr);
		$this -> display('qianbao/lists');
	}



}
