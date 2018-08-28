<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class TixianController extends BaseController
{
	private $tiXian = NULL;
	private $qianbao = NULL;
	private $user = NULL;
	private $order = NULL;
	// private $user_bank_card = NULL;
	private $store = NULL;
	private $tixian_type = array('1' => '零售收益');
	private $tixian_status = array('1' => '待审核', '2' => '已审核', '3' => '审核失败');
	public function _initializes(){
		!is_null($this->tiXian)?:$this->tiXian = M('tixian');
		!is_null($this -> user) ? : $this -> user = M('user');
		!is_null($this -> order) ? : $this -> order = M('OrderInfo');
		// !is_null($this -> user_bank_card) ? : $this -> user_bank_card = M('user_bank_card');
		!is_null($this -> store) ? : $this -> store = M('the_store');
	}
	//商品品牌列表
    public function lists(){
    	//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? : 1;
		$start = ($p - 1) * $showpage;
		$where = array();
		//判断高级搜索
		$data = array_merge(I('get.'),I('post.'));

		//判断赋值
		if ($data) {
			!$data['all_order_sn'] ? : $where['app_tixian.all_order_sn'] = array('like', '%' . $data['all_order_sn'] . '%');
			$data['all_order_sn'] !== '0' ? : $where['app_tixian.all_order_sn'] = array('like', '%' . $data['all_order_sn'] . '%');
			!$data['is_check'] ? : $where['app_tixian.is_check'] = $data['is_check'];
			$data['is_check'] !== '0' ? : $where['app_tixian.is_check'] = $data['is_check'];
			!$data['status'] ? : $where['app_tixian.status'] = $data['status'];
			!$data['uid'] ? : $where['app_tixian.uid'] = $data['uid'];
			$data['uid'] !== '0' ? : $where['app_tixian.uid'] = $data['uid'];
			!$data['user_name'] ? : $where['app_tixian.user_name'] = $data['user_name'];
			$data['user_name'] !== '0' ? : $where['app_tixian.user_name'] = $data['user_name'];
			if($data['start'] && $data['end']){
				$where['app_tixian.add_time'] = array('between',array(strtotime($data['start']),strtotime($data['end'])));
//				$timewhere = 'appid=2';
			}else{
				$data['start'] ?$where['app_tixian.add_time'] = array('egt',strtotime($data['start'])):null;
				$data['end'] ?$where['app_tixian.add_time'] = array('elt',strtotime($data['end'])):null;
			}
			
//			!$data['start'] ? : $where['add_time'] = array('egt', strtotime($data['start']));
//			!$data['end'] ? : $where['add_time'] = array('elt', strtotime($data['end']));
			//			$where = array_merge($where,$data);
		}
		//计算指定条件下的提现数
		// $count_list = $this -> tiXian -> field(array('COUNT(*)'=>'count','SUM(`money`)'=>'money','SUM(`procedure`)'=>'procedure','SUM(`truemoney`)'=>'truemoney'))-> join('app_user_bank_card on app_user_bank_card.uid=app_tixian.uid','inner') -> where($where) -> find();
		$count_list = $this -> tiXian -> field(array('COUNT(*)'=>'count','SUM(`money`)'=>'money','SUM(`procedure`)'=>'procedure','SUM(`truemoney`)'=>'truemoney')) -> where($where) -> find();
		// dump($count_list);
		//计算指定条件下的订单页数
		$count = $count_list['count'];
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		// $adminlogArr = $this -> tiXian -> field('app_tixian.*,app_user_bank_card.user_name,app_user_bank_card.coop_bank,app_user_bank_card.bank_name,app_user_bank_card.bank_phone,app_user_bank_card.bank_address,app_user_bank_card.bank_city,app_user_bank_card.id AS card_id') -> join('app_user_bank_card on app_user_bank_card.uid=app_tixian.uid','inner') -> where($where) -> order('status ASC,add_time DESC') ->limit($start, $showpage) -> select();
		$adminlogArr = $this -> tiXian -> field('app_tixian.*') -> where($where) -> order('status ASC,add_time DESC') ->limit($start, $showpage) -> select();
		foreach ($adminlogArr as $k => $v) {
			$adminlogArr[$k]['nickname']=$this->user->where(array('uid'=>$v['uid']))->getField('nickname');
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
		$this -> assign('count_list', $count_list);
		$this -> assign('arr', $adminlogArr);
		$this -> display('tiXian/lists');
    }
	//修改指定数据
	public function update(){
		$data = I('param.');
		$bool = $this->tiXian->save($data);
		if($bool){
			echo 1;
		}else{
			echo 0;
		}
	}
    //导出提现数据
    public function tixian_exprets(){
    	$where = ' 1 ';
		$where .= !empty($_GET['status']) && $_GET['status'] != 'undefined' ? " AND a.`status` = '{$_GET['status']}'" : '' ;
		$where .= !empty($_GET['all_order_sn']) && $_GET['all_order_sn'] != 'undefined' ? " AND a.`all_order_sn` LIKE '%".$_GET['all_order_sn']."%'" : '';
		$where .= !empty($_GET['uid']) && $_GET['uid'] != 'undefined' ? " AND a.`uid` LIKE '%".$_GET['uid']."%'" : '';
		$where .= !empty($_GET['end_time']) && $_GET['end_time'] != 'undefined' ? " AND a.`add_time` <= '{$_GET['end_time']}'" : '';
		$where .= !empty($_GET['sta_time']) && $_GET['sta_time'] != 'undefined' ? " AND a.`add_time` >= '{$_GET['sta_time']}'" : '';
		//echo $where;exit;
		$tixian_list = D()->query("SELECT a.*, b.`user_name`, b.`coop_bank`, b.`bank_name`, b.`bank_phone`, b.`bank_address`, b.`bank_city` FROM `app_tixian` AS a LEFT JOIN `app_user_bank_card` AS b ON a.`uid` = b.`uid`  WHERE {$where} ORDER BY a.`status` DESC, a.`add_time` DESC");
		for($k = 0; $k < count($tixian_list); $k++){
			$tixian_list[$k]['nickname']=$this->user->where(array('uid'=>$tixian_list[$k]['uid']))->getField('nickname');
			$tixian_list[$k]['add_time'] = date('Y-m-d H:i', $tixian_list[$k]['add_time']);
			$tixian_list[$k]['type'] = $this->tixian_type[$tixian_list[$k]['type']];
			$tixian_list[$k]['status'] = $this->tixian_status[$tixian_list[$k]['status']];
		}
		//dump($tixian_list);exit;

		Vendor('PHPExcel.PHPExcel');
        Vendor('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
      	$expTitle="会员提现数据";//表名
      	//dump($expTitle);exit;
        $expCellName = array(
             array('nickname','用户ID'),
             array('user_name','开户名称'),
             array('coop_bank','银行卡号'),
             array('bank_name','支行名称'), 
             array('bank_phone','手机号码'),
             array('bank_address','开户地址'),
             array('bank_city','开户的所属城市'),
             array('money','客户提现金额'),
             array('procedure','提现手续费'),
             array('truemoney','实际需要转账金额'),
             array('type','提现类型'),
             array('all_order_sn','提现单号'),
             array('order_number','订单总笔数'),
             array('remarks','提现备注'),
             array('audit_remarks','审核备注'),
             array('add_time','提现时间'),
             array('status','提现状态'),
        );
        $expTableData=array(); 
        for($i = 0; $i < count($tixian_list); $i++){
            array_push($expTableData, array(//这里的需要导出的内容，要注意键名跟上面的字段键名要一致
                'nickname'=>" ".$tixian_list[$i]['nickname'].'( '.$tixian_list[$i]['uid'].' )',
                'user_name'=>$tixian_list[$i]['user_name'],
                'coop_bank'=>$tixian_list[$i]['coop_bank'],
                'bank_name'=>$tixian_list[$i]['bank_name'],
                'bank_phone'=>$tixian_list[$i]['bank_phone'],
                'bank_address'=>" ".$tixian_list[$i]['bank_address'],
                'bank_city'=>$tixian_list[$i]['bank_city'],
                'money'=>$tixian_list[$i]['money'],
                'procedure'=>$tixian_list[$i]['procedure'],
                'truemoney'=>$tixian_list[$i]['truemoney'],
                'type'=>$tixian_list[$i]['type'],
                'all_order_sn'=>$tixian_list[$i]['all_order_sn'],
                'order_number'=>$tixian_list[$i]['order_number'],
                'remarks'=>$tixian_list[$i]['remarks'],
                'audit_remarks'=>$tixian_list[$i]['audit_remarks'],
                'add_time'=>$tixian_list[$i]['add_time'],
                'status'=>$tixian_list[$i]['status'],
            ));
        }
       exports($expTitle, $expCellName, $expTableData);
       exit;
    }

	//审核提现 
	public function shenhe(){
		if(isset($_POST['audit_remarks'])){
			//获取tiXian对象
			$tiXian = $this->tiXian;
			!is_null($this->qianbao)?:$this->qianbao = D('qianbaoSn');
			//开启事务，保证数据完整
			$tiXian->startTrans();
			$data = I('param.');
			//将钱包流水表的状态更新
			$is_tixian = 2;
			if($data['status'] == 3){
				$is_tixian = 3;
			}
			// echo $is_tixian;
			$bool = $this->qianbao->where(array('order_sn'=>array('in',$data['order_sn'])))->save(array('is_tixian'=>$is_tixian));
			
			//删除指定品牌id数据
			$bool1 = $tiXian->save($data);
			if ($bool && $bool1) {
				$tiXian->commit();
	        	echo json_encode(array('code'=>1));
	        } else {
	        	$tiXian->rollback();
	            echo json_encode(array('code'=>0));
	        }
		}else{
			$this->assign('data',I('param.'));
			$this->display('tiXian/shenhe');
			exit;
		}
		
		
	}
	//信息审核 
	public function isCheckShenhe(){
		if(isset($_POST['is_check_remark'])){
			//获取tiXian对象
			$tiXian = $this->tiXian;

			$data = I('param.');
	
			//删除指定品牌id数据
			$bool = $tiXian->save($data);
			if ($bool) {
	        	echo json_encode(array('code'=>1));
	        } else {
	            echo json_encode(array('code'=>0));
	        }
		}else{
			$this->assign('data',I('param.'));
			$this->display('tiXian/isCheckShenhe');
			exit;
		}
		
		
	}
	//订单流水详情
// 	public function orderList(){
// 		$all_order_sn = I('param.all_order_sn');

// 		//设置查询条件
// 		$where = array();
// 		//时间条件
// 		$timewhere = array();
// 		if($all_order_sn){
// 			$order_sn = explode(',',$all_order_sn);
// 			$where['order_sn'] = array('IN',$order_sn);
// 		}
// 		$data = array_merge(I('get.'),I('post.'));
// 		//判断赋值
// 		if ($data) {
// 			// !$data['order_sn'] ? : $where['order_sn'] = array('like', '%' . $data['order_sn'] . '%');
// 			// $data['order_sn'] !== '0' ? : $where['order_sn'] = array('like', '%' . $data['order_sn'] . '%');
// 			!$data['consignee'] ? : $where['consignee'] = array('like', '%' . $data['consignee'] . '%');
// 			$data['consignee'] !== '0' ? : $where['consignee'] = array('like', '%' . $data['consignee'] . '%');
// 			!$data['shop_id'] ? : $where['shop_id'] = array('like', '%' . $data['shop_id']. '%');
// 			$data['shop_id'] !== '0' ? : $where['shop_id'] = array('like', '%' . $data['shop_id'] . '%');
// 			!$data['user_id'] ? : $where['user_id'] = array('like', '%' . $data['user_id'] . '%');
// 			$data['user_id'] !== '0' ? : $where['user_id'] = array('like', '%' . $data['user_id'] . '%');
// 			!$data['tel'] ? : $where['tel'] = array('like', '%' . $data['tel'] . '%');
// 			$data['tel'] !== '0' ? : $where['tel'] = array('like', '%' . $data['tel'] . '%');
// 			if($data['start'] && $data['end']){
// 				$where['add_time'] = array('egt',strtotime($data['start']));
// 				$timewhere = 'add_time <= '.strtotime($data['end']);
// //				$timewhere = 'appid=2';
// 			}else{
// 				$data['start'] ?$where['add_time'] = array('egt',strtotime($data['start'])):null;
// 				$data['end'] ?$where['add_time'] = array('elt',strtotime($data['end'])):null;
// 			}
// 		}

// 		//查询搜索数据
// 		$orderArr = $this -> order ->  where($where) -> where($timewhere) -> order('add_time desc') -> select();
// 		foreach ($orderArr as $k => $v) {
// 			$where['id']=$v['shop_id'];
// 			$shop = $this->store->field('shop_name')->where($where)->find();
// 			$orderArr[$k]['shop_name']=$shop['shop_name'];
// 			$wh['uid']=array('in',array($v['user_id'],$v['sj_uid'],$v['ssj_uid']));
// 			$user = $this->user->field('uid,nickname')->where($wh)->select();
// 			foreach ($user as $vv) {
// 				$orderArr[$k][$vv['uid']]['nickname']=$vv['nickname'];
// 			}
// 			$orderArr[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
// 			switch ($v['postsign']) {
// 				case '0':
// 					$orderArr[$k]['postsign'] = '未提交中台';
// 					break;
// 				case '1':
// 					$orderArr[$k]['postsign'] = '已提交中台';
// 					break;
// 				case '2':
// 					$orderArr[$k]['postsign'] = '已获取中台订单号';
// 					break;
// 			}
// 			switch ($v['shipping_status']) {
// 				case '0':
// 					$orderArr[$k]['shipping_status'] = '未发货';
// 					break;
// 				case '1':
// 					$orderArr[$k]['shipping_status'] = '已发货';
// 					break;
// 				case '2':
// 					$orderArr[$k]['shipping_status'] = '已收货';
// 					break;
// 			}
// 			switch ($v['pay_status']) {
// 				case '0':
// 					$orderArr[$k]['pay_status'] = '未付款';
// 					break;
// 				case '1':
// 					$orderArr[$k]['pay_status'] = '已付款';
// 					break;
// 			}
// 		}
// 		//判断是否为导出订单
// 		if($data['exports']){
// 			return $orderArr;
// 		}
// 		$this->assign('data',$data);
// 		$this->assign('all_order_sn',$all_order_sn);
// 		$this -> assign('arr', $orderArr);
// 		$this -> display('tiXian/orderList');
// 	}
	//订单流水详情
	public function qianbaoList(){
		// echo  $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].U('Admin/Tixian/payCallback');
		//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? I('get.p') : 1;
		$start = ($p - 1) * $showpage;
		$all_order_sn = I('param.all_order_sn');

		//设置查询条件
		$where = array();
		//时间条件
		$timewhere = array();
		if($all_order_sn){
			$order_sn = explode(',',$all_order_sn);
			$where['order_sn'] = array('IN',$order_sn);
		}
		$where['is_tixian'] = 1;
		$data = array_merge(I('get.'),I('post.'));
		//判断赋值
		if ($data) {
			!$data['income_uid'] ? : $where['app_qianbao_sn.income_uid'] = $data['income_uid'];
			!$data['order_sn'] ? : $where['app_qianbao_sn.order_sn'] = array('like', '%' . $data['order_sn'] . '%');
			$data['order_sn'] !== '0' ? : $where['app_qianbao_sn.order_sn'] = array('like', '%' . $data['order_sn'] . '%');
			!$data['buyid'] ? : $where['app_qianbao_sn.buyid'] = array('like', '%' . $data['buyid'] . '%');
			$data['buyid'] !== '0' ? : $where['app_qianbao_sn.buyid'] = array('like', '%' . $data['buyid'] . '%');
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
		$qianbao = M('qianbao_sn');
		//计算指定条件下的订单页数
		$count_data = $qianbao -> field('income_money,income_status') -> join('app_user ON app_user.uid=app_qianbao_sn.buyid') -> where($where) -> where($timewhere) -> select();
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
		$qianbaoArr = $qianbao -> field('app_user.nickname,app_qianbao_sn.*') -> join('app_user ON app_user.uid=app_qianbao_sn.buyid') -> where($where) -> where($timewhere) -> order('app_qianbao_sn.add_time desc') -> limit($start, $showpage) -> select();
		// dump($qianbaoArr);
		$page = new \Think\Page($count,$showpage,$data);
		//展示分页
		$show = $page->show();
		//判断是否为导出订单
		if($data['exports']){
			return $orderArr;
		}
		// dump($data);
		$this->assign('show',$show);
		$this->assign('data',$data);
		$this->assign('count_list',$count_list);
		$this->assign('all_order_sn',$all_order_sn);
		$this -> assign('arr', $qianbaoArr);
		$this -> display('tiXian/qianbaoList');
	}
	//提现回调
	public function payCallback(){
		echo 'SUCCESS';
	}
	//导出订单
	public function exports(){
		// $data = $this->lists();
		Vendor('PHPExcel.PHPExcel');
        Vendor('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
        $order_list = $this->orderList();
      	$expTitle="订单列表";//表名
      	//dump($expTitle);exit;
        $expCellName = array(
             array('order_sn','订单号'),
             array('user_id','用户ID'),
             array('shop_id','店铺ID'),
             array('shop_money','店铺收益'), 
             array('consignee','收货人名称'),
             array('tel','收货人电话'),
             array('province','配送省份-配送城市-配送区域'),
             array('postsign','是否已提交中台'),
             array('pay_status','订单付款状态'),
             array('shipping_status','订单发货状态'),
             array('shipping_name','快递类型'),
             array('invoice_no','快递单号'),
             array('shipping_fee','商品总运费'),
             array('order_amount','订单总金额'),
             array('sj_uid','上级UID'),
             array('sj_money','上级收益'),
             array('ssj_uid','上上级Uid'),
             array('ssj_money','上上级收益'),
             array('add_time','下单时间'),
        );
        $expTableData=array(); 
        foreach ($order_list as $k => $v) {
        	array_push($expTableData, array(//这里的需要导出的内容，要注意键名跟上面的字段键名要一致
                'order_sn'=>" ".$v['order_sn'],
                'user_id'=>$v['user_id'].'( '.$v[$v['user_id']]['nickname'].' )',
                'shop_id'=>$v['shop_id'].'( '.$v['shop_name'].' )',
                'shop_money'=>$v['shop_money'],
                'consignee'=>$v['consignee'],
                'tel'=>" ".$v['tel'],
                'province'=>$v['province'] .'-'.$v['city'].'-'.$v['district'],
                'postsign'=>$v['postsign'],
                'pay_status'=>$v['pay_status'],
                'shipping_status'=>$v['shipping_status'],
                'shipping_name'=>$v['shipping_name'],
                'shipping_fee'=>$v['shipping_fee'],
                'order_amount'=>$v['order_amount'],
                'sj_uid'=>$v['sj_uid'].'( '.$v[$v['sj_uid']]['nickname'].' )',
                'sj_money'=>$v['sj_money'],
                'ssj_uid'=>$v['ssj_uid'].'( '.$v[$v['ssj_uid']]['nickname'].' )',
                'ssj_money'=>$v['ssj_money'],
                'add_time'=>$v['add_time'],
            ));
        }
 
       exports($expTitle, $expCellName, $expTableData);
       exit;
	}	
}
