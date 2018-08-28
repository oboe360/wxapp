<?php
namespace Think;
/**
 *万客小程序公共函数库 
 * 2018 -7-12 
 *by tao  
 *tel 15360806016
 */
class  Common
{
	private $arr   =   array();
   	private $pay_log= NULL;	
   	private $order= NULL;	
   	private $order_goods= NULL;	
   	private $user= NULL;	
   	private $goods= NULL;	
   	private $qian_sn= NULL;	
   	private $record= NULL;	
   	private $shop= NULL;	
	/* 输出调试信息 */
	public function dp($array = array()){
	    echo '<pre>';
	    print_r($array);
	    echo '</pre>';
	}
	/*查询用户基本信息*/
	public function user_info($uid){
		$user = D('user');
		$commission = D('commission_rate');
		$where['uid']=$uid;
		$row = $user->field('user_rank,user_money,nickname,headimgurl,phone,shop_id,historical_cons,phone')->where($where)->find();
		$wh['user_rank'] =$row[user_rank];
		$rank = $commission->field('rank_name')->where($wh)->limit(1)->find();
		$row[rank_name]=$rank['rank_name'];
		return $row;	
	}
	/*查询用户等级*/
	public function user_rank($uid){
		$user = D('user');
		$where['uid']=$uid;
		$row = $user->field('user_rank')->where($where)->find();
		return $row['user_rank'];	
	}
	/*查询用户的上级*/
	public function sjuid($uid){
		$user = D('user');
		$where['uid']=$uid;
		$row = $user->field('sj_uid')->where($where)->find();
		return $row['sj_uid'];	
	}
	/*根据shop_id获取邦定uid*/
	public function getBdUid($shop_id){
		$the_store = M('the_store');
		$where['id']=$shop_id;
		$bd_uid = $the_store->where($where)->getField('bd_uid');
		return $bd_uid;	
	}
	/** 商品详情播图  dersym */
	 public function get_goods_gallery_dersym($goods_id)
	{
		//$arr = array();	
		$goods_gallery = D('goods_gallery');
		//echo $goods_id;die;
		$where['goods_id']=$goods_id;
		$where['is_delete']=0;
		$arr = $goods_gallery -> field('img_id, img_url,img_desc') -> where($where)->order('img_desc desc')->select();
			//echo $goods_gallery->_sql();
	   return $arr;
	}
	/** 获取商品详情的数据dersym */
	 public function get_goods_detail($goods_id)
	{
		//$arr = array();	
		$goods = D('goods');
		//echo $goods_id;die;
		$where['goods_id']=$goods_id;
		$arr = $goods -> field('goods_name,sales_count,goods_brief, goods_desc,shop_price,market_price,goods_number,video_img,goods_guige,goods_img,goods_thumb,offline_count,is_top,prent_id,goods_lianjie,share_img') ->where($where)->find();
	   return $arr;
	}
	/** 获取用戶購物車数据dersym */
	 public function get_cart_goods($user_id)
	{
		//$arr = array();	
		$cart = D('cart');
		$goods = D('goods');
		$where['user_id']=$user_id;
		$arr = $cart -> where($where)->select();
		//echo $cart->_sql();die;
		foreach ($arr as $k => $v) {
			$wh['goods_id']=$v[goods_id];
			$row = $goods -> field('goods_thumb,goods_number')-> where($wh)->find();
			$arr[$k]['goods_thumb'] = $row['goods_thumb'];
			$arr[$k]['kucun'] = $row['goods_number'];
			$total['goods_price']  += $v['goods_price'] * $v['goods_number'];
            $total['total_number'] += $v['goods_number'];//by Leah    
            $total['subtotal']   	+=  sprintf("%.2f", $v['goods_price'] * $v['goods_number']);
		}
		
		$data['cartgoods']=$arr;
		$data['total']=$total;
		//$this ->dp($data);die;
	    return $data;
	}
	/** 用戶添加購物車函數 */
	 public function addto_cart($goods_id,$number,$user_id)
	{
	
		//$arr = array();	
		$cart = D('cart');
		$goods = D('goods');
		$user_rank = $this->user_rank($user_id);
		$where['user_id']=$user_id;
		$where['goods_id']=$goods_id;
		$wh['goods_id']=$goods_id;
		$goodsArr = $goods -> field('goods_key,goods_name,goods_sn,shop_price,market_price,goods_number')-> where($wh)->find();
		//
		if($number > $goodsArr['goods_number']){
           $data['code']=0; 
           $data['msg']="库存不足！"; 
           return $data;exit();
        }
		$row = $cart -> field('cid,goods_number')-> where($where)->find();

		if($row){
			$arr['goods_number']=$row['goods_number']+$number;
			$arr['addtime']=time();
			$cart->where($where)->data($arr)->save(); 
			$data['code']=2; 
            $data['msg']="添加购物车成功"; 
            return $data;exit();

		}else{
			$arr['goods_id']=$goods_id;
			$arr['goods_number']=$number;
			$arr['user_id']=$user_id;
			$arr['goods_name']=$goodsArr[goods_name];
			$arr['goods_sn']=$goodsArr[goods_sn];
			if($user_rank==1){///判斷用戶等級
				$arr['goods_price']=$goodsArr[market_price];//普通用戶價格
			}else{
				$arr['goods_price']=$goodsArr[shop_price];//會員價
			}
			if($goodsArr[goods_key]!='0'){//判斷商品類型，1是云倉商品，2是本地倉商品
				//$this->dp($goodsArr[goods_key]);die;
				$arr['goods_type']=1;
			}
			$arr['addtime']=time();
			$cart->data($arr)->add(); 
			$data['code']=1; 
            $data['msg']="添加购物车成功"; 
            return $data;exit();
		}
		//$this ->dp($data);die;
	    //return $data;
	}
	/**
	* 调用购物车商品数目
	*/
	public function insert_cart_info_number($user_id)
	{
		$cart = D('cart');
		$wh['user_id']=$user_id;
		$row = $cart -> field('count(cid) AS number')-> where($wh)->find();
		$number =$row['number'];
	    // $sql = 'SELECT count(rec_id) AS number FROM ' . $GLOBALS['ecs']->table('cart') .
	    //        " WHERE user_$id = '$user_id'";
	    // $number = $GLOBALS['db']->getOne($sql);
	    return intval($number);
	}
	/**
	 * 删除购物车中的商品
	 *
	 * @access  public
	 * @param   integer $id
	 * @return  void
	 */
	public function drop_cart_goods($id,$user_id)
	{		
		$cart = D('cart');
    	/* 取得商品id */
	    $where['cid']=$id;
	    $where['user_id']=$user_id;
	  	$res = $cart->where($where)->delete();
	  	return $res;
	}
	/**
	 * 删除购物车中的商品
	 *
	 * @access  public
	 * @param   integer $id
	 * @return  void
	 */
	public function check_cart_goods($user_id)
	{		
		$cart = D('cart');
    	/* 取得商品id */
	    $where['user_id']=$user_id;
	  	$res = $cart->field('count(cid) AS number')->where($where)->find();
	  	return $res;
	}
	/**
	 * 查询会员礼包的商品数据
	 *
	 * @access  public
	 * @param   integer $id
	 * @return  void
	 */
	public function vip_goods($goods_id)
	{		
		$goods = D('goods');
    	/* 取得商品id */
	    $where['goods_id']=$goods_id;
		$arr = $goods -> field('first_comm,second_comm,shop_comm,goods_id,goods_sn,goods_key,goods_name,shop_price,market_price,goods_number,goods_thumb,share_img') -> where($where)->find();
	  	return $arr;
	  	//return $arr;
	}
	/**
	 * 获取用户购物商品信息
	 *
	 * @access  public
	 * @param   $user_id 用户ID
	 * @return  array
	 * @return  type 查询类型：checkout 查询购物车详细数据
	 * @date  	2018-7-12s
	 *    
	*/
	public function cart_goods($user_id)
	{		
		$cart = D('cart');
		$goods = D('goods');
		$where['user_id']=$user_id;
		$cartdata = $cart->where($where)->order('cid desc')->select();

		foreach ($cartdata as $k => $v) {
			$wh['goods_id']=$v[goods_id];
			$row = $goods->where($wh)->find();
			$cartdata[$k]['goods_thumb']=$row['goods_thumb'];
			$cartdata[$k]['goods_weight']=$row['goods_weight'];
			//$cartdata[$k]['goods_money']=$v['goods_number']*$v['goods_price'];
			if($v['goods_type'] == 1){
				$data['yuncang'][]=$cartdata[$k];
				$data['yuncang_money']+=$v['goods_number']*$v['goods_price'];

			}else{
				$data['warehouse'][]=$cartdata[$k];
				$data['warehouse_money']+=$v['goods_number']*$v['goods_price'];
			}
			$data['total_money']+=$v['goods_number']*$v['goods_price'];
			
		}
		$data['total_number']=count($cartdata);
	  	return $data;
	}
	/**
	 * 获取用户购物下单商品信息
	 *
	 * @access  public
	 * @param   $user_id 用户ID
	 * @return  array
	 * @return  type 查询类型：checkout 查询购物车详细数据
	 * @date  	2018-7-12s
	 *    
	*/
	public function order_goods($user_id)
	{		
		$cart = D('cart');
		//$goods = D('goods');
		$where['user_id']=$user_id;
		$cartdata = $cart->where($where)->select();
		//echo $cart->_sql();die;
	  	return $cartdata;
	}

	/**
	 * 获取用户收货地址或者默认收货地址
	 *
	 * @access  public
	 * @param   integer $user_id 用户ID
	 * @return  void
	 */
	public function get_default_consignee($user_id)
	{		
		$address = D('user_address');
		$user = D('user');
		$where['uid']=$user_id;
		$row = $user->field('wx_address_id')->where($where)->find();
		$wx_address_id=$row['wx_address_id'];
		if($wx_address_id!=0){
			$wh['aid']=$wx_address_id;
			$data = $address->where($wh)->find();
		}else{
			$data = $address->where($where)->order('aid desc')->limit(1)->find();
		}
	  	return $data;
	}

	/**
	 * 获取用户所有收货地址
	 *
	 * @access  public
	 * @param   $user_id 用户ID
	 * @return  array
	 * @date  	2018-7-12s
	 *    
	*/
	public function get_all_consignee($user_id)
	{		
		$address = D('user_address');
		$where['uid']=$user_id;
		$data = $address->where($where)->order('aid desc')->select();
	  	return $data;
	}
	/**
	 * 获取用户所有收货地址
	 * @access  public
	 * @param   $user_id 用户ID
	 * @return  array
	 * @date  	2018-7-12s
	 *    
	*/
	public function get_address($address_id)
	{		
		$address = D('user_address');
		$where['aid']=$address_id;
		$data = $address->where($where)->find();
		//echo $address->_sql();die;
		//dump();die;
	  	return $data;
	}
	/**
	 * 获取用户訂單統計
	 * @access  public
	 * @param   $user_id 用户ID
	 * @return  array
	 * @date  	2018-7-12s
	 *    
	*/
	public function order_total($uid)
	{		
		$order = D('order_info');
		/*未发货订单数*/
		$where['user_id']=$uid;
		$where['order_status']=1;
		$where['pay_status']=1;
		$where['shipping_status']=0;
		$row = $order->field("count(order_id) as number")->where($where)->find();
		//echo $order->_sql();die;
		$data[unsend_number] = $row[number];
		
		/*已发货订单数*/
		$wh['user_id']=$uid;
		$wh['order_status']=1;
		$wh['pay_status']=1;
		$wh['shipping_status']=1;
		$row = $order->field("count(order_id) as number")->where($wh)->find();
		$data[send_number] = $row[number];
		/*已完成的订单数*/
		$over['user_id']=$uid;
		$over['order_status']=1;
		$over['pay_status']=1;
		$over['shipping_status']=2;
		$row= $order->field("count(order_id) as number")->where($over)->find();
		$data[over_number] = $row[number];

		//echo $address->_sql();die;
		$data[all]=$data[unsend_number]+$data[send_number]+$data[over_number];
	  	return $data;
	}
	/**
	 * 获取用户訂單数据
	 * @access  public
	 * @param   $uid 用户ID
	 * @return  order_type 订单类型：unsend 未发货 send已发货 over已完成，all全部订单
	 * @date  	2018-7-12s
	 *    
	*/
	public function order_info($uid,$order_type)
	{
		
		//设置请求订单状态	
		if($order_type==''){
			$order_type="all";
		}
		//初始化数据表对象
		!is_null($this->order)?:$this->order = M('order_info');
		!is_null($this->order_goods)?:$this->order_goods = M('order_goods');
		!is_null($this->goods)?:$this->goods = M('goods');
		$order = $this->order;
		$order_goods = $this->order_goods;
		$goods = $this->goods;
		//设置查询条件
		$where = array();
		$where['user_id']=$uid;
		$where['order_status']=1;
		$where['pay_status']=1;
		
		switch ($order_type) {
			/*未发货订单数*/
			case 'unsend':
				$where['shipping_status']=0;
				break;
			/*已发货订单数*/
			case 'send':
				$where['shipping_status']=1;
				break;
			/*已完成的订单数*/
			case 'over':
				$where['shipping_status']=2;
				break;
			case 'all':
			/*全部的订单数*/
				unset($where['order_status']);
				break;
		}
		$data = I('param.');
		//获取请求分页数
		$currentPage = $data['currentPage']?:1;
		//获取每页显示数
		$pageShow = $data['pageShow']?:10;
		//计算当前记录起步索引
		$start = ($currentPage-1)*$pageShow;
		if($start < 0){
			$start = 0;
		}
		//获取当前用户提现总记录数
		$count = $order->where($where)->count();
		//计算当前总页数
		$page = ceil($count/$pageShow);

		$orderList = $order->field("postsign,order_sn,order_amount,pay_time,order_id,shipping_status,invoice_no")->limit($start,$pageShow)->where($where)->order("order_id desc")->select();
		//dump($unsend);die;
		foreach ($orderList as $k => $v) {
			$orderwhere['order_id']=$v['order_id'];
			$row = $order_goods->field("count(rec_id) as goods_count,goods_id,goods_number,goods_name")->where($orderwhere)->find();
			//$this ->dp($row);die;
			$orderList[$k]['goods_count']=$row['goods_count'];
			$orderList[$k]['goods_id']=$row['goods_id'];
			$orderList[$k]['goods_number']=$row['goods_number'];
			$orderList[$k]['goods_name']=$row['goods_name'];
			
			$wh['goods_id']=$row['goods_id'];
			$goodsrow = $goods->field("goods_thumb,market_price,shop_price")->where($wh)->find();
			$orderList[$k]['good_img']=$goodsrow['goods_thumb'];
			
			$orderList[$k]['goods_price']=$goodsrow['market_price'];
			$orderList[$k]['pay_time']=date("Y-m-d H:i:s",$v['pay_time']);
			switch ($v['shipping_status']) {
				/*未发货订单数*/
				case '0':
					$orderList[$k]['shipping_status']='未发货';
					break;
				/*已发货订单数*/
				case '1':
					$orderList[$k]['shipping_status']='已发货';
					break;
				/*已完成的订单数*/
				case '2':
					$orderList[$k]['shipping_status']='已收货';
					break;
			}

		}
		if($count){
			$orderList[0]['page'] = $page;
		}
		return $orderList;exit;	
	}
	/**
	 * 获取用户訂單详情数据
	 * @access  public
	 * @param   $user_id 用户ID
	 * @return  array
	 * @date  	2018-7-12s
	 *    
	*/
	public function order_detail($uid,$order_id)
	{		
		$order = D('order_info');
		$order_goods = D('order_goods');
		$goods = D('goods');
		$where['order_id']=$order_id;
		$where['user_id']=$uid;
		$orderrow = $order->where($where)->find();
		if($orderrow[shipping_status]==0){
				$orderrow[shipping_status]="未发货";
			}elseif($orderrow[shipping_status]==1){
				$orderrow[shipping_status]="已发货";
			}elseif($orderrow[shipping_status]==2){
				$orderrow[shipping_status]="已收货";
		}
		$orderrow['date_time'] =date("Y-m-d H:i:s",$orderrow['pay_time']);
		$wh['order_id']=$orderrow['order_id'];
		$goodsArr = $order_goods->field("goods_id,goods_number,goods_price,goods_name")->where($wh)->select();
		foreach ($goodsArr as $k => $v) {
			$goodwhere['goods_id']=$v['goods_id'];
			$row = $goods->field("goods_thumb")->where($goodwhere)->find();
			$goodsArr[$k]['good_img']=$row['goods_thumb'];
		}

		$shop=$this->shopinfo($orderrow['shop_id']);
		$data['orderarr']=$orderrow;
		$data['shop']=$shop;
		$data['goodsArr']=$goodsArr;
	  	return $data;
	}
	/**
	 *确认收货
	 * @access  public
	 * @param   $user_id 用户ID
	 * @return  array
	 * @date  	2018-7-12s
	 *    
	*/
	public function order_update($uid,$order_id)
	{		
		$order = D('order_info');
		$where['order_id']=$order_id;
		$where['user_id']=$uid;
		$arr['shipping_status']	=2;
		$res = $order->where($where)->data($arr)->save();
	  	return $res;
	}
/**
	 *修改收货地址
	 * @access  public
	 * @param   $user_id 用户ID
	 * @return  array
	 * @date  	2018-7-12s
	 *    
	*/
	public function update_address($uid,$order_id,$address)
	{		
		$order = D('order_info');
		//print_r($address);die;
		$where['order_id']=$order_id;
		$where['user_id']=$uid;
		// $arr = array(
  //               'consignee'     =>trim($address->userName),
  //               'country'       =>trim(1),
  //               'province'      =>trim( $address->provinceName),
  //               'city'          =>trim($address->cityName),
  //               'district'      =>trim( $address->countyName),
  //               'address'       =>trim( $address->detailInfo),
  //               'tel'           =>trim($address->telNumber),

  //             );
		$arr['consignee'] =$address['userName'];
		$arr['country'] =1;
		$arr['province'] =$address['provinceName'];
		$arr['city'] =$address['cityName'];
		$arr['district'] =$address['countyName'];
		$arr['address'] =$address['detailInfo'];
		$arr['tel'] =$address['telNumber'];
		$res = $order->where($where)->data($arr)->save();
	  	return $res;
	}
	/**
	 * 获取快遞列表
	 *
	 * @access  public
	 * @param   $user_id 用户ID
	 * @return  array
	 * @date  	2018-7-12s
	 *    
	*/
	public function shipping_list($area,$weight)
	{		
		$shiplist = D('touch_ship');
		$kuaidi = D('ship');
		$data = $kuaidi->where("province like '%$area%' and start=1 ")->select();
		//dump($data);die;
		foreach ($data as $k => $v) {
 			$row = $shiplist->field('touch_id,shipping_name')->where("touch_id = $v[touch_id]")->find();
			$shou=$v['first_weigt'];
			$row['shipping_fee']= $v['first_price'];
			$row['weight']= $weight;
			if($weight>$shou){
				$row['shipping_fee']  += (ceil(($weight - $shou))) * $v['con_price'];
			}

			$arr[]=$row;
		}
		//
		foreach ($arr as $key => $va) {
			if($va['shipping_fee']>=60){
				$row = $shiplist->field('touch_id,shipping_name')->where("shipping_name like '%物流%'")->find();
				//echo $shiplist->_sql();die;
				$row['shipping_fee']=0;
				$arr[]=$row;
			}
		}
		//dump($array);die;
		return  $arr;
	}

	/** 用戶普通商品添加購物車函數 */
	 public function add_order($array,$goodsarr)
	{
		//$arr = array();	
		$order = D('order_info');
		$pay_log = D('pay_log');
		$order_goods = D('order_goods');
		$order_id = $order->data($array)->add();
		if($order_id){
			foreach ($goodsarr as $k => $v) {
				$arr['order_id']=$order_id;
				$arr['goods_id']=$v[goods_id];
				$arr['goods_name']=$v[goods_name];
				$arr['goods_sn']=$v[goods_sn];
				$arr['goods_number']=$v[goods_number];
				$arr['goods_price']=$v[goods_price];
				$arr['granary_type']=$v[goods_type];
				$res = $order_goods->data($arr)->add(); 
			}

			$pay['order_id']=$order_id;
			$pay['order_amount']=$array[goods_amount];
			$log_id = $pay_log->data($pay)->add();
			if($order_id&&$log_id){
				$data['order_id']=$order_id;
				$data['log_id']=$log_id;
				$data['code']=1;
				$data['msg']="提交订单成功";
			}else{
				$data['code']=0;
				$data['msg']="提交订单失败";
			}
		}else{
			$data['code']=0;
			$data['msg']="提交订单失败";
		}
		
		return $data;
    }
    	/** 用戶普通商品添加購物車函數 */
	public function add_vip_order($array,$goodsarr)
	{
		//$arr = array();	
		$order = D('order_info');
		$pay_log = D('pay_log');
		$order_goods = D('order_goods');
		$order_id = $order->data($array)->add();
		if($order_id){
				$arr['order_id']=$order_id;
				$arr['goods_id']=$goodsarr[goods_id];
				$arr['goods_name']=$goodsarr[goods_name];
				$arr['goods_sn']=$goodsarr[goods_sn];
				$arr['goods_number']=1;
				$arr['goods_price']=$goodsarr[market_price];
				if($goodsarr['goods_key']!='0'){
					$arr['granary_type']='1';
				}else{
					$arr['granary_type']='2';
				}
				$res = $order_goods->data($arr)->add(); 
				$pay['order_id']=$order_id;
				$pay['order_amount']=$array[goods_amount];
				$log_id = $pay_log->data($pay)->add();
			if($order_id&&$log_id&&$res){
				$data['order_id']=$order_id;
				$data['log_id']=$log_id;
				$data['code']=1;
				$data['msg']="提交订单成功";
			}else{
				$data['code']=0;
				$data['msg']="提交订单失败";
			}
		}else{
			$data['code']=0;
			$data['msg']="提交订单失败";
		}
		
		return $data;
    }
/**
 * 得到会员成交订单数量 新订单号
 * @return  string
 */
public function get_order_number($uid)
{
  $order =D('order_info');
 //$where['_logic']="AND";
  $where['sj_uid']= $uid;
  $where['ssj_uid']=$uid;
  $where['_logic']='OR';
  $wh['_complex'] = $where;
  $wh['pay_status']=1;
  $wh['inv_type']=1;
  $row =$order->field("count('order_id') as number")->where($wh)->find();
  //dump($number);die;
  //echo $order->_sql();die;
  return $row['number'];
}
/**
**
 * 获取成交订单详情 
 * @access  public
 * @param   $uid 用户ID
 * @return  array
 * @return  $type 类型：direct是直接订单  间接订单indirect 
 * @date  	2018-7-12s
 */
public function vip_order_info($uid,$type)
{
	!is_null($this->order)?:$this->order = M('order_info');
	!is_null($this->order_goods)?:$this->order_goods = M('order_goods');
	!is_null($this->goods)?:$this->goods = M('goods');
	!is_null($this->user)?:$this->user = M('user');
	$order =$this->order;
	$order_goods =$this->order_goods;
	$goods =$this->goods;
	$user =$this->user;
	//$where['_logic']="AND";
	if($type==""){
		$type=='direct';
	}
	//dump($type);die;
	if($type=='direct'){
		 $where['sj_uid']= $uid;
	}else{
		$where['ssj_uid']= $uid;
	}
  	$now = time();
	/*今日订单*/
	$beginTime =strtotime(date('Y-m-d', $now));
	$endTime = $beginTime+24*60*60 ;
	$row =  $order->field("count(order_id) as count")->where("pay_time>='$beginTime' and pay_time<='$endTime' and inv_type=1  and (ssj_uid ='$uid' or sj_uid='$uid') and pay_status=1")->find();
	$arr['today'] = $row[count]?$row[count]:0;
	/*本周订单*/
  	$thisweek_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")));

  	$thisweek_start=strtotime($thisweek_start);
  		//echo $thisweek_start;
	$thisweek_end = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y"))); 
	//echo $thisweek_end;die;
	$thisweek_end=strtotime($thisweek_end);
	$row =  $order->field("count(order_id) as count")->where(" pay_time>='$thisweek_start' and pay_time<='$thisweek_end' and inv_type=1  and (ssj_uid ='$uid' or sj_uid='$uid') and pay_status=1")->find();
	//echo $order->_sql();die;
  	$arr['week'] =$row[count]?$row[count]:0;
  	/*本月 订单*/
  	$thismonth_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
  	//echo $thismonth_start;
  	$thismonth_start=strtotime($thismonth_start);
	$thismonth_end = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("t"),date("Y"))); 
	$thismonth_end=strtotime($thismonth_end);
	$row =  $order->field("count(order_id) as count")->where("pay_time>='$thismonth_start' and pay_time<='$thismonth_end' and inv_type=1 and (ssj_uid ='$uid' or sj_uid='$uid') and pay_status=1")->find();
	//echo $order->_sql();die;
  	$arr['month'] =$row[count]?$row[count]:0;
  	/*统计订单数量*/
  	$direct_where['pay_status']=1;
 	$direct_where['inv_type']=1;
 	$direct_where['sj_uid']=$uid;
 	$row = $order->field("count(order_id) as count")->where($direct_where)->find();
 	$arr['direct_number'] = $row[count]?$row[count]:0;//直接订单数量

 	$indirect_where['pay_status']=1;
 	$indirect_where['inv_type']=1;
 	$indirect_where['ssj_uid']=$uid;
 	$row = $order->field("count(order_id) as count")->where($indirect_where)->find();
 	$arr['indirect_number'] = $row[count]?$row[count]:0;//直接订单数量
 	$where['pay_status']=1;
 	$where['inv_type']=1;
 	$data = I('param.');
	//获取请求分页数
	$currentPage = $data['currentPage']?:1;
	//获取每页显示数
	$pageShow = $data['pageShow']?:10;
	//计算当前记录起步索引
	$start = ($currentPage-1)*$pageShow;
	if($start < 0){
		$start = 0;
	}
	//获取当前用户提现总记录数
	$count = $order->where($where)->count();
	//计算当前总页数
	$page = ceil($count/$pageShow);
 	$orderArr= $order->field("user_id,order_sn,order_amount,pay_time,order_id,shipping_status,sj_money,ssj_money")->limit($start,$pageShow)->where($where)->select();
 	// echo json_encode($where);die;
	foreach ($orderArr as $k => $v) {
		$orderwhere['order_id']=$v['order_id'];
		$row = $order_goods->field("goods_id,goods_number,goods_name,goods_price")->where($orderwhere)->find();
		$orderArr[$k][goods_number]=$row['goods_number'];
		$orderArr[$k][goods_name]=$row['goods_name'];
		$orderArr[$k][goods_price]=$row['goods_price'];
		$wh['goods_id']=$row['goods_id'];
		$goodsrow = $goods->field("goods_thumb")->where($wh)->find();
		$orderArr[$k][good_img]=$goodsrow['goods_thumb'];
		$userwhere['uid']=$v['user_id'];
		$userrow = $user->field("nickname,headimgurl")->where($userwhere)->find();
		$orderArr[$k][nickname]=$userrow['nickname'];
		$orderArr[$k][headimgurl]=$userrow['headimgurl'];
		$orderArr[$k][pay_time]=date("Y-m-d H:i:s",$v['pay_time']);
	}
	$arr['count_num'] = $arr['direct_number'] + $arr['indirect_number'];
	$all['arr']=$arr;
	$all['orderArr']=$orderArr;
	$all['page']=$page;
	// $all['data']=$data;
  //dump($number);die;
  //echo $order->_sql();die;
 	return $all;
}
/**
 * 获取会员收入订单流水详情
 * @access  public
 * @param   $uid 用户ID
 * @return  array
 * @return  $type 类型：direct是直接订单  间接订单indirect 
 * @date  	2018-7-12s
 */
public function vip_qianbao_sn($uid)
{
	!is_null($this->qian_sn)?:$this->qian_sn = M('qianbao_sn');
	!is_null($this->user)?:$this->user = M('user');
	$qianbao = $this->qian_sn;
	$user = $this->user;
	//dump($uid);die;
	$now = time();
	/*今日收入*/
	$beginTime =strtotime(date('Y-m-d', $now));
	$endTime = $beginTime+24*60*60 ;
	$row =  $qianbao->field("sum(income_money) as income_money")->where("add_time>='$beginTime' and add_time<='$endTime' and income_uid = '$uid'")->find();
	if(empty($row[income_money])){
			$row[income_money]=0.00;
	}
	$arr['today_money'] =$row[income_money];
	/*本周订单*/
  	$thisweek_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")));
  	$thisweek_start=strtotime($thisweek_start);
  	//echo $thisweek_start;
	$thisweek_end = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y"))); 
	//echo $thisweek_end;die;
	$thisweek_end=strtotime($thisweek_end);
	$row =  $qianbao->field("sum(income_money) as income_money")->where("add_time>='$thisweek_start' and add_time<='$thisweek_end' and income_uid = '$uid'")->find();
	//echo $order->_sql();die;
	if(empty($row[income_money])){
			$row[income_money]=0.00;
	}
  	$arr['week_money'] =$row[income_money];
  	/*本月 订单*/
  	$thismonth_start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
  	//echo $thismonth_start;
  	$thismonth_start=strtotime($thismonth_start);
	$thismonth_end = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("t"),date("Y"))); 
	$thismonth_end=strtotime($thismonth_end);
	$row =  $qianbao->field("sum(income_money) as income_money")->where("add_time>='$thismonth_start' and add_time<='$thismonth_end' and income_uid = '$uid'")->find();
	//echo $order->_sql();die;
	if(empty($row[income_money])){
			$row[income_money]=0.00;
	}
  	$arr['month_momey'] =$row[income_money];
 	$where['income_uid']=$uid;
 	$data = I('param.');
	//获取请求分页数
	$currentPage = $data['currentPage']?:1;
	//获取每页显示数
	$pageShow = $data['pageShow']?:10;
	//计算当前记录起步索引
	$start = ($currentPage-1)*$pageShow;
	if($start < 0){
		$start = 0;
	}
	//获取当前用户提现总记录数
	$count = $qianbao->where($where)->count();
	//计算当前总页数
	$page = ceil($count/$pageShow);
 	$orderArr= $qianbao->where($where)->limit($start,$pageShow)->select();
 	//echo $qianbao->_sql();die;
 	//$this->dp($orderArr);die;
	foreach ($orderArr as $k => $v) {
		$userwhere['uid']=$v['buyid'];
		$userrow = $user->field("nickname,headimgurl")->where($userwhere)->find();
		$orderArr[$k][nickname]=$userrow['nickname'];
		$orderArr[$k][headimgurl]=$userrow['headimgurl'];
		$orderArr[$k][order_date]= date("m月d日",$v['order_time']).date("H:i",$v['order_time']);

	} 
	//获取用户金额
	$arr['money'] = $user->where(array('uid'=>$uid))->getField('user_money');
	$all['arr']=$arr;
	$all['orderArr']=$orderArr;
	$all['page']=$page;
  //dump($number);die;
  //echo $order->_sql();die;
 	return $all;
}
/*获取快递类型*/
public function shipping_code($order_id){
	$order =D('order_info');
  	$where['order_id']=$order_id;
  	$row = $order->field('shipping_code,shipping_name')->where($where)->find();
  	return $row;
}
/**
 * 获取店铺基本信息
 * @access  public
 * @param   $shop_id 店铺ID
 * @return  array 
 * @date  	2018-7-12
 */
public function shopinfo($shop_id){
	$store =D('the_store');
	$where['id']=$shop_id;
  	$row = $store->field('shop_name,shop_img')->where($where)->find();
  	//$this-Common->dp("login3.txt",$store->_sql());
  	//echo $store->_sql();die;
  	//dump($shiprow);die;
  	return $row;
}
/**
 * 获取首页轮播图数据
 * @access  public
 * @param   $shop_id 店铺ID
 * @return  array 
 * @date  	2018-7-12
 */
public function banner(){
	$banner =D('touch_ad');
	$where['enabled']=1;
  	$bannerArr = $banner -> field(' ad_link,ad_code') -> where($where)->order('order_by desc,ad_id desc')->select();
  		
  	//dump($shiprow);die;
  	return $bannerArr;
}

/**
 * 查询每个等级的分佣比例
 * @access  public
 * @param   $rank 等级
 * @return  array 
 * @date  	2018-7-12
 */
public function commission_rate($rank,$type){
	$commission =D('commission_rate');
	$where['rank']=$rank;
	$where['goods_type']=$type;
  	$row = $commission->find();
  	//dump($shiprow);die;
  	return $row;
}
/**
 * 查询历史浏览过的店铺SHOP-id
 * @access  public
 * @param   $rank 等级
 * @return  array 
 * @date  	2018-7-12
 */
public function history_id($shopid,$uid){
	$history =D('history');
	$where['shop_id']=$shopid;
	$where['uid']=$uid;
  	$row = $history->field('shop_id')->where($where)->find();
  	//dump($shiprow);die;
  	return $row['shop_id'];
}
/**
 * 查询历史浏览过的店铺SHOP-id
 * @access  public
 * @param   $rank 等级
 * @return  array 
 * @date  	2018-7-12
 */
public function new_shop($uid){
	$user =D('user');
	$where['uid']=$uid;
  	$row = $user->field('shop_id')->where($where)->find();
  	//dump($shiprow);die;
  	return $row['shop_id'];
}
/**
 * 查询配置相对应数据
 * @access  public
 * @param   $key 查询变量
 * @return  array 
 * @date  	2018-7-12
 */
public function other_config($key){
	$other_config =D('other_config');
	$where['key']=$key;
  	$row = $other_config->field('value')->where($where)->find();
    $qz  = $row['value'];//订单号的前缀
    return $qz;
}
/**
 * 会员礼包支付成功后的回凋
 * @access  public
 * @param   $log_id 支付记录的ID
 * @param   $order_sn 订单号
 * @return  array 
 * @date  	2018-7-12
 */
public function vippay_respond($log_id,$order_sn,$upOrderId='',$pay_type='0'){
	
	// dump($log_id);
	// dump($order_sn);
	//file_put_contents('result.txt', $log_id.$order_sn."【支付结果回调失败！】:\n".date('Y-m-d H:i:s'),FILE_APPEND);
	// exit;
	//初始化数据表对象
	!is_null($this->pay_log)?:$this->pay_log = M('pay_log');
	!is_null($this->order)?:$this->order = M('order_info');
	!is_null($this->order_goods)?:$this->order_goods = M('order_goods');
	!is_null($this->user)?:$this->user = M('user');
	!is_null($this->goods)?:$this->goods = M('goods');
	!is_null($this->qian_sn)?:$this->qian_sn = M('qianbao_sn');
	!is_null($this->record)?:$this->record = M('shop_earnings_record');
	!is_null($this->shop)?:$this->shop = M('shop_earnings');
	$pay_log=$this->pay_log;	
	$order=$this->order;	
	$order_goods=$this->order_goods;	
	$user=$this->user;	
	$goods=$this->goods;
	$qian_sn=$this->qian_sn;
	$record=$this->record;
	$shop=$this->shop;
	$paywh['log_id']=$log_id;
	$pay = $pay_log->field('number,order_id')->where($paywh)->find();
	// dump($pay);
	// dump($pay_log);
	//$this->dp($pay);die;
	if($pay['number']==0){
		$payarr['number']=1;
		$payarr['is_paid']=1;
		$res = $pay_log->where($paywh)->data($payarr)->save();//更新支付记录为已支付
		if($res){
			$orderarr['pay_status']=1;
			$orderarr['order_status']=1;
			$orderarr['upOrderId']=$upOrderId;
			$orderarr['pay_type']=$pay_type;
			$orderarr['pay_time']=time();
			$orderwh['order_sn']=$order_sn;
			$order_res = $order->where($orderwh)->data($orderarr)->save();//更新订单为已支付
			//echo $order->_sql();die;
			if($order_res){
				$ogwher[order_id]=$pay['order_id'];
				$ogdata = $order_goods->where($ogwher)->select();
				foreach ($ogdata as $k => $v) {//查询订单每个商品的数量，减去产品的库存，增加销量数量
					$where[goods_id] = $v['goods_id'];
					$goodsrow = $goods->field('goods_number,sales_count')->where($where)->find();
					$goodsarr['goods_number']=$goodsrow['goods_number']-$v['goods_number'];
					$goodsarr['sales_count']=$goodsrow['sales_count']+$v['goods_number'];
					$goods_save = $goods->where($where)->data($goodsarr)->save();//
				}
				$ogwher[order_id]=$pay['order_id'];
				$orderinfo = $order->field("user_id,shop_id,sj_uid,ssj_uid,sj_money,ssj_money,shop_money,order_sn,add_time,goods_amount,tel")->where($ogwher)->find();
				//$this->dp($orderinfo);die;
				$user_info = $this->user_info($orderinfo[user_id]);
				$historical_cons['historical_cons']=$user_info['historical_cons']+$orderinfo[goods_amount];
				if(!$user_info['phone']){
					$historical_cons['phone']=$orderinfo['tel'];
				}
				if($user_info['user_rank']==1){
					
					//$historical_cons['reg_time'] = time(); 
					$historical_cons['user_rank'] = 2; 
				}
				
				$userwhere['uid']=$orderinfo['user_id'];
				//获取用户绑定字段，回调判断是否更新绑定
				$userList = $user->field('bind_shop,bind_sj_user')->where($userwhere)->find();
				if($userList['bind_shop'] == 2){
					$historical_cons['bind_shop'] = 1;
					$historical_cons['shop_id'] = $orderinfo['shop_id'];
				}
				if($userList['bind_sj_user'] == 2){
					$historical_cons['bind_sj_user'] = 1;
					$historical_cons['sj_uid'] = $orderinfo['sj_uid'];
				}
				$user->where($userwhere)->data($historical_cons)->save();//更新用户历史消费
				// file_put_contents('result.txt',$historical_cons."【支付结果回调失败！】:\n".date('Y-m-d H:i:s'),FILE_APPEND);
				// if($user_info['user_rank']==1){
					
				// 	$update['reg_time'] = time(); 
				// 	$update['user_rank'] = 2; 
				// 	$user->where("uid = '$orderinfo[user_id]'")->data($update)->save();//更新用户等级
				// }
				//echo die;
				//
				if($orderinfo[sj_uid]!=0){//一级分佣流水记录和会员佣金收益的变化
					$onewhere['uid']=$orderinfo[sj_uid];
					$onerow = $user->field('user_money')->where($onewhere)->find();
					$user_money=$onerow['user_money'];
					$onearr['buyid'] = $orderinfo[user_id];
					$onearr['income_money'] = $orderinfo[sj_money];
					$onearr['money'] = $orderinfo[goods_amount];
					$onearr['income_uid'] = $orderinfo[sj_uid];
					$onearr['shop_id'] = $orderinfo[shop_id];
					$onearr['order_sn'] = $orderinfo[order_sn];
					$onearr['order_time'] = $orderinfo[add_time];
					$onearr['income_status'] = 1;
					$onearr['status'] = 1;
					$onearr['the_money'] = $user_money;
					$onearr['add_time'] = time();
					$oneres = $qian_sn->data($onearr)->add();
					//echo $qian_sn->_sql();die;
					$userarr['user_money']=$orderinfo[sj_money]+$user_money;
					$userarr['the_money']=$user_money;
					$user->where($onewhere)->data($userarr)->save();
					//
				}

				if($orderinfo[ssj_uid]!=0){//二級分佣流水记录和会员佣金收益的变化
					$secondwhere['uid']=$orderinfo[ssj_uid];
					$secondrow = $user->field('user_money')->where($secondwhere)->find();
					$user_money=$secondrow['user_money'];
					$secondarr['buyid'] = $orderinfo[user_id];
					$secondarr['income_money'] = $orderinfo[ssj_money];
					$secondarr['money'] = $orderinfo[goods_amount];
					$secondarr['income_uid'] = $orderinfo[ssj_uid];
					$secondarr['shop_id'] = $orderinfo[shop_id];
					$secondarr['order_sn'] = $orderinfo[order_sn];
					$secondarr['order_time'] = $orderinfo[add_time];
					$secondarr['income_status'] = 2;
					$secondarr['status'] = 1;
					$secondarr['the_money'] = $user_money;
					$secondarr['add_time'] = time();
					$secondres = $qian_sn->data($secondarr)->add();

					$ssjarr['user_money']=$orderinfo[ssj_money]+$user_money;
					$ssjarr['the_money']=$user_money;
					$user->where($secondwhere)->data($ssjarr)->save();
				}
				if($orderinfo[shop_id]!=0){//店鋪的收益流水記錄插入和店鋪金額更新
					//file_put_contents('tg.txt', $log_id.'a'.$order_sn.'a'."\n".date('Y-m-d H:i:s'),FILE_APPEND);
					$shopwhere['shop_id']=$orderinfo[shop_id];
					$shoprow = $shop->field('money,the_money,total_count')->where($shopwhere)->find();
					$shop_money = $shoprow['money'];
					$total_count = $shoprow['total_count'];
					$qbarr['buyid'] = $orderinfo[user_id];
					$qbarr['order_money'] = $orderinfo[goods_amount];
					$qbarr['money'] = $orderinfo[shop_money];
					$qbarr['income_uid'] = $orderinfo[sj_uid];
					$qbarr['shop_id'] = $orderinfo[shop_id];
					$qbarr['order_sn'] = $orderinfo[order_sn];
					$qbarr['order_time'] = $orderinfo[add_time];
					$qbarr['order_status'] = 5;
					$qbarr['order_type'] = 1;
					$qbarr['add_time']  = time();
					$record->data($qbarr)->add();
					//echo $user->_sql();die;
					//以上是插入店铺收益流水记录
					$shoparr['money']     = $orderinfo[shop_money]+$shop_money;
					$shoparr['the_money'] = $shop_money;
					$shoparr['total_count'] = $orderinfo[goods_amount]+$total_count;
					//$shoparr['_string'] = '`total_count` = `total_count`+'.$orderinfo[goods_amount];
					$shoparr['the_time'] = time();
					$shop->where($shopwhere)->data($shoparr)->save();
					
				}
			}
		}
		return true;
	}
}

 /**
 * 返回快递100查询链接 by wang 
 * URL：https://code.google.com/p/kuaidi-api/wiki/Open_API_Chaxun_URL
 */
  public function kuaidi100($invoice_sn,$type){
     		  $url = 'http://m.kuaidi100.com/query?type='.$type.'&id=1&postid=' .$invoice_sn. '&temp='.time();
        	return $url;
    }



/**
 * 得到新订单号s
 * @return  string
 */
public function get_order_sn($union,$user_id)
{
    /* 选择一个随机的方案 */
    //mt_srand((double) microtime() * 1000000);
    //dump($res);
   return $union.date('Ymd').$user_id.date('Hiis');
}

	/*写入文件*/
	public function file_put($file_name,$data){//写入文件
	    file_put_contents( $file_name, "\r\n", FILE_APPEND);
	    file_put_contents( $file_name,$data, FILE_APPEND);
	}
}
