<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
use Admin\Controller\HuanQiuMeiTaoApi;
class OrderController extends BaseController {
	private $HuanQiuMeiTaoApi = NULL;
	private $order = NULL;
	private $orderlog = NULL;
	private $ordergoods = NULL;
	private $store = NULL;
	private $user = NULL;
	public function _initializes() {
		
		!is_null($this -> order) ? : $this -> order = D('OrderInfo');
		!is_null($this -> orderlog) ? : $this -> orderlog = D('OrderLog');
		!is_null($this -> store) ? : $this -> store = D('the_store');
		!is_null($this -> user) ? : $this -> user = D('user');
	}

	//获取订单列表
	//获取订单列表
	public function lists() {
		//获取排序
		$order_by = cookie('order_order_by')?:'add_time ASC';
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
		//默认获取全部订单
		$tiaozhuan = 'all';
		if($data['postsign'] == 'weifk'){
			$where['pay_status'] = 0;

			$tiaozhuan = 'weifk';
		}else{
			$where['pay_status'] = 1;
		}
		//判断赋值
		if ($data) {
			!$data['order_sn'] ? : $where['order_sn'] = $data['order_sn'];
			$data['order_sn'] !== '0' ? : $where['order_sn'] = $data['order_sn'];
			!$data['consignee'] ? : $where['consignee'] = array('like', '%' . $data['consignee'] . '%');
			$data['consignee'] !== '0' ? : $where['consignee'] = array('like', '%' . $data['consignee'] . '%');
			!$data['shop_id'] ? : $where['shop_id'] = $data['shop_id'];
			$data['shop_id'] !== '0' ? : $where['shop_id'] = $data['shop_id'];
			!$data['user_id'] ? : $where['user_id'] = $data['user_id'];
			$data['user_id'] !== '0' ? : $where['user_id'] = $data['user_id'];
			!$data['sj_uid'] ? : $where['sj_uid'] = $data['sj_uid'];
			$data['sj_uid'] !== '0' ? : $where['sj_uid'] = $data['sj_uid'];
			!$data['ssj_uid'] ? : $where['ssj_uid'] = $data['ssj_uid'];
			$data['ssj_uid'] !== '0' ? : $where['ssj_uid'] = $data['ssj_uid'];
			!$data['invoice_no'] ? : $where['invoice_no'] = $data['invoice_no'];
			$data['invoice_no'] !== '0' ? : $where['invoice_no'] = $data['invoice_no'];
			if(isset($data['postsign']) && $data['postsign'] != 'all'){
				if($data['postsign'] == 'weitijiao'){
					$where['postsign'] = 0;
					// $where['pay_status'] = 1;

					$tiaozhuan = 'weitijiao';
				}elseif($data['postsign'] == 'tijiao'){
					$where['postsign'] = 1;
					$tiaozhuan = 'tijiao';
				}elseif($data['postsign'] == 'huoqu'){
					$where['postsign'] = 2;
					$tiaozhuan = 'huoqu';
				}
				
			}
			!$data['tel'] ? : $where['tel'] = array('like', '%' . $data['tel'] . '%');
			$data['tel'] !== '0' ? : $where['tel'] = array('like', '%' . $data['tel'] . '%');
			if($data['start'] && $data['end']){
				$where['add_time'] = array('egt',strtotime($data['start']));
				$timewhere = 'add_time <= '.strtotime($data['end']);
//				$timewhere = 'appid=2';
			}else{
				$data['start'] ?$where['add_time'] = array('egt',strtotime($data['start'])):null;
				$data['end'] ?$where['add_time'] = array('elt',strtotime($data['end'])):null;
			}
		}

				// dump($where);
				// exit;
		//计算指定条件下的订单页数
		// $count = $this -> order -> where($where) -> where($timewhere) -> count();
		// $order_count = $this -> order -> field('count(*) as count,SUM(order_amount) as order_amount_count,SUM(sj_money) as sj_money_count,SUM(ssj_money) as ssj_money_count') -> where($where) -> where($timewhere) -> select();
		$order_count = $this -> order -> field('order_amount,sj_money,ssj_money,sj_uid,ssj_uid,pay_status') -> where($where) -> where($timewhere) -> select();
		// dump($order_count);
		$count = count($order_count);
		//初始化统计数据
		$count_list = array();
		$count_list['count'] = $count;
		$count_list['order_amount_count'] = 0;
		$count_list['sj_money_count'] = 0;
		$count_list['ssj_money_count'] = 0;
		foreach ($order_count as $v) {
			if($v['pay_status'] == 1){
				if($v['sj_uid']){
					$count_list['sj_money_count'] += $v['sj_money'];
				}
				if($v['ssj_uid']){
					$count_list['ssj_money_count'] += $v['ssj_money'];
				}
				$count_list['order_amount_count'] += $v['order_amount'];
			}
		}
		// dump($count_list);
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//判断是否为导出订单
		if($data['exports']){
			$showpage = intval($data['data']);
		}
		//查询搜索数据
		$orderArr = $this -> order ->  where($where) -> where($timewhere) -> order($order_by) -> limit($start, $showpage) -> select();
		// dump($orderArr);
		foreach ($orderArr as $k => $v) {
			$where['id']=$v['shop_id'];
			$shop = $this->store->field('shop_name')->where($where)->find();
			// dump($shop);
			$orderArr[$k]['shop_name']=$shop['shop_name'];
			$wh['uid']=array('in',array($v['user_id'],$v['sj_uid'],$v['ssj_uid']));
			$user = $this->user->field('uid,nickname')->where($wh)->select();
			foreach ($user as $vv) {
				$orderArr[$k][$vv['uid']]['nickname']=$vv['nickname'];
			}
			$orderArr[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
			switch ($v['postsign']) {
				case '0':
					$orderArr[$k]['postsign'] = '未提交中台';
					break;
				case '1':
					$orderArr[$k]['postsign'] = '已提交中台';
					break;
				case '2':
					$orderArr[$k]['postsign'] = '已获取中台订单号';
					break;
			}
			switch ($v['shipping_status']) {
				case '0':
					$orderArr[$k]['shipping_status'] = '未发货';
					break;
				case '1':
					$orderArr[$k]['shipping_status'] = '已发货';
					break;
				case '2':
					$orderArr[$k]['shipping_status'] = '已收货';
					break;
			}
			switch ($v['pay_status']) {
				case '0':
					$orderArr[$k]['pay_status'] = '未付款';
					break;
				case '1':
					$orderArr[$k]['pay_status'] = '已付款';
					break;
				case '2':
					$orderArr[$k]['pay_status'] = '已退款';
					break;
			}
		}
		//判断是否为导出订单
		if($data['exports']){
			// $order_count = $this -> order -> field('count(*) as count,SUM(order_amount) as order_amount_count,SUM(sj_money) as sj_money_count,SUM(ssj_money) as ssj_money_count') -> where($where) -> where($timewhere) -> limit($start, $showpage) -> select();
			// $orderArr['order_count'] = $order_count;
			return $orderArr;
		}
		$page = new \Think\Page($count,$showpage,$data);
		//展示分页
		$show = $page->show();
		$this->assign('show',$show);
		$this->assign('data',$data);
		// $this->assign('count',$count);
		$this->assign('order_count',$count_list);
		$this -> assign('arr', $orderArr);
		$this->assign('tiaozhuan',$tiaozhuan);
		$this -> display('order/alllists');
	}
	//订单详情
	public function order_detail(){
		$data = I('param.');
		$orderArr = $this -> order -> where($data) -> find();
		// echo $this->order->_sql();
		// var_dump($order_id);
		// exit;
		// $orderArr = reset($this->getStatus(array($orderArr)));
		$goods = $this -> getGoods($orderArr['order_id']);
		//dump($orderArr);die;
		$orderArr['addres_detail'] = $orderArr['province'].' '. $orderArr['city'].' '. $orderArr['region_name'].' '.$orderArr['address'];
		switch ($orderArr['postsign']) {
				case '0':
					$orderArr['postsign'] = '未提交中台';
					break;
				case '1':
					$orderArr['postsign'] = '已提交中台';
					break;
				case '2':
					$orderArr['postsign'] = '已获取中台订单号';
					break;
			}
		switch ($orderArr['shipping_status']) {
			case '0':
				$orderArr['shipping_status'] = '未发货';
				break;
			case '1':
				$orderArr['shipping_status'] = '已发货';
				break;
			case '2':
				$orderArr['shipping_status'] = '已收货';
				break;
		}
		switch ($orderArr['pay_status']) {
			case '0':
				$orderArr['pay_status'] = '未付款';
				break;
			case '1':
				$orderArr['pay_status'] = '已付款';
				break;
			case '2':
				$orderArr['pay_status'] = '已退款';
				break;
		}
		$orderArr['add_time'] = date('Y-m-d H:i:s',$orderArr['add_time']);
		$orderArr['nickname'] = $this->user->field('nickname')->where(array('uid'=>$orderArr['user_id']))->getField('nickname');
		$this->assign('orderArr',$orderArr);
		$this->assign('goods',$goods);
		$this->display('order/order_detail');
	}
	//商品详情
	public function order_goods(){
		$order_id = I('param.order_id');
		$goods = $this -> getGoods($order_id);
		$this->assign('goods',$goods);
		$this->display('order/order_goods');
	}

	//根据订单中台订单号获取订单中的商品基本信息
	public function getGoods($middle = false) {
		$dg = D('goods');
		$order_id = I('post.order_id') ? : $middle;
		$orderGoods = D('OrderGoods');
		$arr = $orderGoods -> field('app_goods.goods_img,app_goods.goods_weight,app_order_goods.*') -> where('order_id="'.$order_id.'"') -> join('app_goods on app_goods.goods_id=app_order_goods.goods_id','left') -> select();
		// dump($arr);
		// foreach ($arr as $k => $v) {
		// 	$goods = $dg ->field('goods_img')->where('goods_id="'.$v[goods_id].'"') -> find();
		// 	$arr[$k][goods_img]=$goods['goods_img'];
		// }
		if ($middle) {
			return $arr;
		}
	}
	//提交万里牛
	public function weitijiao() {
		$orderArr = I('post.order_id');
		if(empty($orderArr)){
			$this->error('请勾选获取订单号！', '', 3);
			exit;
		}
		if(count($orderArr) > 200){
			$this->error('一次性提交订单数不要超过200条！', '', 3);
			exit;
		}
		$orderStr = $this->orderStr($orderArr);
		//设置搜索条件
		$where = array();
		$where['order_id'] = array('in',$orderStr);
		$where['pay_status'] = 1;
		$where['order_status'] = 1;
		$where['postsign'] = 0;
		$where['the_order_category'] = array('neq',2);
		//获取订单数据
		$orderdata = $this->order->field('country,order_id,pay_time,inv_payee,add_time,pay_name,order_sn,user_id,order_status,consignee,address,goods_amount,tel,address_detail,pay_id,address,province,city,district')->where($where)->select();
		if(empty($orderdata)){
			$this->error('没有订单数据！', '', 3);
			exit;
		}
		//获取订单商品详情
		!is_null($this -> ordergoods) ? : $this -> ordergoods = M('OrderGoods');
		// $goodsdata = $this->ordergoods->field('app_order_goods.goods_price,app_order_goods.goods_number,app_order_goods.goods_sn,app_order_goods.goods_name,app_order_goods.goods_id,app_goods.goods_key,app_goods.goods_weight')->join('app_goods on app_goods.goods_id=app_order_goods.goods_id','left')->where(array('app_order_goods.granary_type'=>'1','app_order_goods.order_id'=>array('in',$orderStr)))->select();
		 //dump($goodsdata);
		// exit;
        foreach ($orderdata as $key => $v) {
        	// $orderdata[$key]['goodsdata'] = $goodsdata[$key];
            // $arr=explode(" ",$v['address']);
            // $orderdata[$key]['province']=$arr[0];
            // $orderdata[$key]['city']=$arr[1]?:'无';
            // $orderdata[$key]['district']=$arr[2]?:'无';

            $orderdata[$key]['goodsdata'] = $this->ordergoods->field('app_order_goods.goods_price,app_order_goods.goods_number,app_order_goods.goods_sn,app_order_goods.goods_name,app_order_goods.goods_id,app_goods.goods_key,app_goods.goods_weight')->join('app_goods on app_goods.goods_id=app_order_goods.goods_id','left')->where(array('app_order_goods.granary_type'=>'1','app_order_goods.order_id'=>$v['order_id']))->select();
        }
        // echo $this->ordergoods->_sql();
        // dump($orderdata);
        // exit;
        $hqmt = new HuanQiuMeiTaoApi();
        if(!empty($orderdata)){
            $res = $hqmt->order_info(json_encode($orderdata),"order_info");

        }else{
            $this->error('没有合法数据存在！', '', 3);
			exit;
        }
        //print_r($res);die;
        if($res=='1'){
            foreach ($orderdata as $key => $v) {
            	//更新订单状态数据
            	$data = array();
            	$data['order_id'] = $v['order_id'];
            	$data['postsign'] = '1';
            	$this->order->save($data);
            }
            $this->success('订单数据信息提交成功！', '', 3);
			exit;
        }else{
            $this->error('订单数据信息提交失败！', '', 3);
			exit;
        }  
	}
	//订单id数组拼接成字符串
	private function orderStr($order_id){
		$str = '';
		foreach ($order_id as $v) {
			$str .= $v.',';
		}
		return rtrim($str, ',');
	}
	//获取订单号
	public function tijiao(){
		$orderArr  = I('post.order_id');
		if(empty($orderArr)){
			$this->error('请勾选获取订单号！', '', 3);
			exit;
		}
		$orderStr = $this->orderStr($orderArr);
        //设置搜索条件
		$where = array();
		$where['order_id'] = array('in',$orderStr);
		$where['pay_status'] = 1;
		// $where['order_status'] = 1;
		$where['postsign'] = 1;
		$where['the_order_category'] = array('neq',2);
		//获取订单数据
		$orderdata = $this->order->field('order_sn')->where($where)->select();
		if(empty($orderdata)){
			$this->error('没有订单数据！', '', 3);
			exit;
		}
		// echo $this->order->_sql();
		// exit;
        // dump($orderdata);die;
        if(!empty($orderdata)){
        	$hqmt = new HuanQiuMeiTaoApi();
            foreach ($orderdata as $key => $v) {
            	$arr.="'".$v['order_sn']."',";
            }
            $arr=rtrim($arr, ',');

            $res = $hqmt->invoice_no($arr,$action="invoice_no");
            $data = json_decode($res,true);
            if(empty($res)){
            	$this->error('没有订单数据信息！', '', 3);
				exit;
            }
            
           // $res = $hqmt->invoice_no($arr,$action="invoice_no");
            // dump($data);die;
           	//记录未获取订单号
            $orderStr = '';
            foreach ($data as $key => $v) {
            	//更新订单状态数据
            	
            	if($v['shipping_num']){
            		$data = array();
            		$data['postsign'] = '2';
	            	$data['invoice_no'] = $v['shipping_num'];
	            	if($v['huoqu_status'] == 1 && $v['wancheng_status'] == 1){
	            		$data['shipping_status'] = 2;
	            	}elseif ($v['huoqu_status'] == 1 && $v['wancheng_status'] == 0) {
	            		$data['shipping_status'] = 1;
	            	}
	            	$data['shipping_name'] = $v['shipping_name'];
	            	$data['shipping_code'] = $v['shipping_code'];
	            	$this->order->where(array('order_sn'=>$v['order_sn']))->save($data);
            	}
            }
            if(rtrim($orderStr,',')){
            	$this->success('订单号为：'.$orderStr.' 状态暂无变化，其它订单数据信息获取成功！', '', 3);
				exit;
            }
            $this->success('订单数据信息获取成功！', '', 3);
			exit;
        }else{
            $this->error('订单数据信息获取失败！', '', 3);
			exit;
        }
	}
	

	//订单日志表
	private function addOrderLog($order_id,$action,$bool,$data){
		$data = array('order_id'=>$order_id,'action'=>$action,'status'=>$bool,'log_time'=>time(),'data'=>json_encode($data));
		$this->orderlog->add($data);
	}
	//订单日志错误列表
	public function orderLogList(){
		//分页参数设置
		$showpage = cookie('pageshop')?:15;;
		$p = I('get.p') ?: 1;
		$start = ($p - 1) * $showpage;
		//设置高级搜索条件
		$where = array();
		//时间条件
		$timewhere = array();
		$data = I('post.')?:I('get.');
		if($data['start'] && $data['end']){
				$where['log_time'] = array('egt',strtotime($data['start']));
				$timewhere = 'log_time <= '.strtotime($data['end']);
//				$timewhere = 'appid=2';
			}else{
				$data['start'] ?$where['log_time'] = array('egt',strtotime($data['start'])):null;
				$data['end'] ?$where['log_time'] = array('elt',strtotime($data['end'])):null;
			}
		//执行操作
		$arr = $this->orderlog->where($where)->where($timewhere)->order('log_time desc')->limit($start,$showpage)->select();
		//计算指定条件下的订单页数
		$count = $this -> orderlog -> where($where) -> where($timewhere) -> count();
		$page = new \Think\Page($count,$showpage,$data);
		//展示分页
		$show = $page->show();
		$this->assign('show',$show);
		$this -> assign('arr', $arr);
		$this -> assign('data', $data);
		$this -> display('orderlog/lists');
	}
	//导出订单
	public function exports(){
		// $data = $this->lists();
		Vendor('PHPExcel.PHPExcel');
        Vendor('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
        $order_list = $this->lists();
        // $order_count = $order_list['order_count'][0];
        // dump($order_list);
        // exit;
        $order_count = array('count'=>0,'order_amount_count'=>0,'sj_money_count'=>0,'ssj_money_count'=>0);
        // unset($order_list['order_count']);
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
        	$order_count['count']++;
        	$order_count['order_amount_count'] += $v['order_amount'];
        	$order_count['sj_money_count'] += $v['sj_money'];
        	$order_count['ssj_money_count'] += $v['ssj_money'];
        }
 	   array_push($expTableData, array(//这里的需要导出的内容，要注意键名跟上面的字段键名要一致
            'order_sn'=>'订单总数为 '.$order_count['count'].' , 下单总金额为 '.$order_count['order_amount_count'].' , 上级总收益为 '.$order_count['sj_money_count'].', 上上级总收益为 '.$order_count['ssj_money_count'].'',
            'user_id'=>'',
            'shop_id'=>'',
            'shop_money'=>'',
            'consignee'=>'',
            'tel'=>'',
            'province'=>'',
            'postsign'=>'',
            'pay_status'=>'',
            'shipping_status'=>'',
            'shipping_name'=>'',
            'shipping_fee'=>'',
            'order_amount'=>'',
            'sj_uid'=>'',
            'sj_money'=>'',
            'ssj_uid'=>'',
            'ssj_money'=>'',
            'add_time'=>'',
        ));
       exports($expTitle, $expCellName, $expTableData);
       exit;
	}
	//修改指定数据
	public function update(){
		$data = I('param.');
		$bool = $this->order->save($data);
		if($bool){
			echo 1;
		}else{
			echo 0;
		}
	}
}
