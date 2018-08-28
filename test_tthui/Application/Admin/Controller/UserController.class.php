<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class UserController extends BaseController {
	private $user = NULL;
	private $rank = NULL;
	public function _initializes(){
		!is_null($this -> user) ? : $this -> user = M('user');
	}
	//获取用户列表
	public function lists() {
		
		!is_null($this -> rank) ? : $this -> rank = D('commission_rate');
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
			!$data['sj_uid'] ? : $where['sj_uid'] = $data['sj_uid'];
			$data['sj_uid'] !== '0' ? : $where['sj_uid'] = $data['sj_uid'];
			!$data['nickname'] ? : $where['nickname'] = array('like', '%' . $data['nickname'] . '%');
			$data['nickname'] !== '0' ? : $where['nickname'] = array('like', '%' . $data['nickname'] . '%');
			!$data['phone'] ? : $where['phone'] = $data['phone'];
			$data['phone'] !== '0' ? : $where['phone'] = $data['phone'];
			if ($data['start'] && $data['end']) {
				$where['reg_time'] = array('egt', strtotime($data['start']));
				$timewhere = 'reg_time <= ' . strtotime($data['end']);
				//				$timewhere = 'appid=2';
			} else {
				$data['start'] ? $where['reg_time'] = array('egt', strtotime($data['start'])) : null;
				$data['end'] ? $where['reg_time'] = array('elt', strtotime($data['end'])) : null;
			}
			//			!$data['start'] ? : $where['add_time'] = array('egt', strtotime($data['start']));
			//			!$data['end'] ? : $where['add_time'] = array('elt', strtotime($data['end']));
			//			$where = array_merge($where,$data);
		}
		//dump($data);
		//计算指定条件下的用户页数
		$count_data = $this -> user -> field('COUNT(*) AS count,SUM(historical_cons) AS historical_cons') -> where($where) -> where($timewhere) -> find();
		$count = $count_data['count'];
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$userArr = $this -> user -> where($where) -> where($timewhere) -> order('reg_time desc') -> limit($start, $showpage) -> select();
		//dump($userArr);
		$the_store = M('the_store');
			foreach ($userArr as $k => $v) {
				$wh['user_rank'] =$v['user_rank'];
				$row = $this->rank->field('rank_name')->where($wh)->find();
				$userArr[$k]['rank_name']=$row['rank_name'];
				$shopwhere['id']=$v['shop_id'];
				$res = $the_store->field('shop_name')->where($shopwhere)->find();
				$userArr[$k]['shop_name'] =$res['shop_name'];
				$userArr[$k]['sj_nickname'] = $this->user->where(array('uid'=>$v['sj_uid']))->getField('nickname');

			}
			
		$page = new \Think\Page($count, $showpage, $data);
		//展示分页
		$show = $page -> show();
		//		echo json_encode($userArr,$page);
		//		dump($data);
		//		echo strtotime($data['start']);
		//		exit;
		$this -> assign('show', $show);
		$this -> assign('data', $data);
		$this -> assign('count_data', $count_data);
		$this -> assign('arr', $userArr);
		$this -> display('user/lists');
	}
	//修改指定数据
	public function update(){
		$data = I('param.');
		$bool = $this->user->save($data);
		// dump($bool);
		// dump($data);
		// exit;
		if($bool){
			echo 1;
		}else{
			echo 0;
		}
	}

}
