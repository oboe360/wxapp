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

	//获取订单列表统计数据
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
			!$data['income_uid'] ? : $where['app_qianbao_sn.income_uid'] = $data['income_uid'];
			$data['income_uid'] !== '0' ? : $where['app_qianbao_sn.income_uid'] = $data['income_uid'];
			!$data['nickname'] ? : $where['app_user.nickname'] = array('like', '%' . $data['nickname'] . '%');
			$data['nickname'] !== '0' ? : $where['app_user.nickname'] = array('like', '%' . $data['nickname'] . '%');
			if($data['start'] && $data['end']){
				$where['app_qianbao_sn.order_time'] = array('egt',strtotime($data['start']));
				$timewhere = 'app_qianbao_sn.order_time <= '.strtotime($data['end']);
//				$timewhere = 'appid=2';
			}else{
				$data['start'] ?$where['app_qianbao_sn.order_time'] = array('egt',strtotime($data['start'])):null;
				$data['end'] ?$where['app_qianbao_sn.order_time'] = array('elt',strtotime($data['end'])):null;
			}
			
//			!$data['start'] ? : $where['add_time'] = array('egt', strtotime($data['start']));
//			!$data['end'] ? : $where['add_time'] = array('elt', strtotime($data['end']));
			//			$where = array_merge($where,$data);
		}
		//计算指定条件下的订单页数
		$count_list = $this -> qianbao -> field('income_uid,SUM(money) AS money,SUM(income_money) AS income_money') -> join('app_user ON app_user.uid=app_qianbao_sn.income_uid') -> where($where) -> where($timewhere) -> group('income_uid') -> order('income_money DESC') -> select();
		$count = count($count_list);
		//设置统计数据
		$count_data = array();
		$count_data['income_money'] = 0;
		$count_data['money'] = 0;
		$count_data['count'] = $count;
		foreach ($count_list as $v) {
			$count_data['income_money'] += $v['income_money'];
			$count_data['money'] += $v['money'];
		}
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$qianbaoArr = $this -> qianbao -> field('income_uid,SUM(money) AS money,SUM(income_money) AS income_money,nickname') -> join('app_user ON app_user.uid=app_qianbao_sn.income_uid') -> where($where) -> where($timewhere) -> group('income_uid') -> order('income_money DESC')-> limit($start, $showpage) -> select();
		foreach ($qianbaoArr as $k => $v) {
			
			$first_count = $this -> qianbao ->field("count('id') as first_count")->where("income_status = 1 and income_uid = '$v[income_uid]'")->find();

				$two_count = $this -> qianbao ->field("count('id') as two_count")->where("income_status = 2 and income_uid = '$v[income_uid]'")->find();
				$qianbaoArr[$k]['first_count'] =$first_count['first_count'];
				$qianbaoArr[$k]['two_count'] =$two_count['two_count'];
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
		$this->assign('count_data',$count_data);
		$this -> assign('arr', $qianbaoArr);
		$this -> display('qianbao/lists');
	}

	//获取订单列表详细数据
	public function qianbaoDetail() {
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
			!$data['income_uid'] ? : $where['app_qianbao_sn.income_uid'] = $data['income_uid'];
			!$data['order_sn'] ? : $where['app_qianbao_sn.order_sn'] = $data['order_sn'];
			$data['order_sn'] !== '0' ? : $where['app_qianbao_sn.order_sn'] = $data['order_sn'];
			!$data['buyid'] ? : $where['app_qianbao_sn.buyid'] = $data['buyid'];
			$data['buyid'] !== '0' ? : $where['app_qianbao_sn.buyid'] = $data['buyid'];
			!$data['nickname'] ? : $where['app_user.nickname'] = array('like', '%' . $data['nickname'] . '%');
			$data['nickname'] !== '0' ? : $where['app_user.nickname'] = array('like', '%' . $data['nickname'] . '%');
			if($data['start'] && $data['end']){
				$where['app_qianbao_sn.order_time'] = array('egt',strtotime($data['start']));
				$timewhere = 'app_qianbao_sn.order_time <= '.strtotime($data['end']);
//				$timewhere = 'appid=2';
			}else{
				$data['start'] ?$where['app_qianbao_sn.order_time'] = array('egt',strtotime($data['start'])):null;
				$data['end'] ?$where['app_qianbao_sn.order_time'] = array('elt',strtotime($data['end'])):null;
			}
			
//			!$data['start'] ? : $where['add_time'] = array('egt', strtotime($data['start']));
//			!$data['end'] ? : $where['add_time'] = array('elt', strtotime($data['end']));
			//			$where = array_merge($where,$data);
		}
		//计算指定条件下的订单页数
		$count_data = $this -> qianbao -> field('income_money,income_status') -> join('app_user ON app_user.uid=app_qianbao_sn.buyid') -> where($where) -> where($timewhere) -> select();
		$count = count($count_data);
		//初始化统计数据
		$count_list = array();
		$count_list['count'] = $count;
		$count_list['first_income_money'] = 0;
		$count_list['second_income_money'] = 0;
		foreach ($count_data as $v) {
			if($v['income_status'] == 1){
				$count_list['first_income_money'] += $v['income_money'];
			}elseif ($v['income_status'] == 2) {
				$count_list['second_income_money'] += $v['income_money'];
			}
		}
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$qianbaoArr = $this -> qianbao -> field('app_user.nickname,app_qianbao_sn.*') -> join('app_user ON app_user.uid=app_qianbao_sn.buyid') -> where($where) -> where($timewhere) -> order('app_qianbao_sn.add_time desc') -> limit($start, $showpage) -> select();
		$page = new \Think\Page($count,$showpage,$data);
		//展示分页
		$show = $page->show();
//		echo json_encode($qianbaoArr,$page);
		//dump($qianbaoArr);die;
//		echo strtotime($data['start']);
//		exit;
		$this->assign('show',$show);
		$this->assign('data',$data);
		$this->assign('count_list',$count_list);
		$this -> assign('arr', $qianbaoArr);
		$this -> display('qianbao/qianbaoDetail');
	}

}
