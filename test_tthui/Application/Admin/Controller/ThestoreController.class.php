<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
//use think\Loader;
require_once getcwd().'/yunsms/SmsApi.php';
use Aliyun\DySDKLite\Sms\SmsApi;
use Think\Common;
class ThestoreController extends BaseController {
	private $theStore = NULL;
	private $storeRank = NULL;
	private $otherConfig = NULL;
	private $shopEarnings = NULL;
	private $shopBankCard = NULL;
	private $user = NULL;
	private $Common = NULL;
	private $tixian_status = array('1' => '待对帐', '2' => '待审核', '3' => '已审核', '4' => '已拒绝');
	private $order_type = array('0' => '普通订单类型', '1' => '礼包订单类型');
	private $is_tixian = array('0' => '末提现', '1' => '已提现', '2' => '已注销');
	public $shop_type = array('0' => '纯wed', '1' => '纯app', '2' => 'app,wed已合并', '3' => '已注销');
	public function _initializes(){
		!is_null($this -> theStore) ? : $this -> theStore = D('theStore');
		!is_null($this -> user) ? : $this -> user = D('user');
		!is_null($this -> storeRank) ? : $this -> storeRank = D('storeRank');
		!is_null($this -> Common ) ? : $this -> Common = new Common();//
	}
	//获取用户列表
	public function lists() {
		// !is_null($this -> theStore) ? : $this -> theStore = D('theStore');
		//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? I('get.p') : 1;
		$start = ($p - 1) * $showpage;
		//判断高级搜索
		$data = array_merge(I('get.'),I('post.'));
		// dump($data);
		//设置查询条件
		$where = array();
		//时间条件
		$timewhere = array();
		//判断赋值
		if ($data) {
			$data['is_check'] === '' || $data['is_check'] === NULL ? : $where['is_check'] = $data['is_check'];
			!$data['id'] ? : $where['id'] = $data['id'];
			$data['id'] !== '0' ? : $where['id'] = $data['id'];
			!$data['shop_name'] ? : $where['shop_name'] = $data['shop_name'];
			$data['shop_name'] !== '0' ? : $where['shop_name'] = $data['shop_name'];
			!$data['sj_id'] ? : $where['sj_id'] = $data['sj_id'];
			$data['sj_id'] !== '0' ? : $where['sj_id'] = $data['sj_id'];
			!$data['user_name'] ? : $where['user_name'] = $data['user_name'];
			$data['user_name'] !== '0' ? : $where['user_name'] = $data['user_name'];
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
		$count = $this -> theStore -> where($where) -> where($timewhere) -> count();
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$theStoreArr = $this -> theStore -> where($where) -> where($timewhere) -> order('is_check asc,add_time desc') -> limit($start, $showpage) -> select();
			foreach ($theStoreArr as $k => $v) {
				//取出所有下级的数量
				if($v['shop_rank']==3){
					$resArr = $this -> theStore -> where('zd_shop_id='.$v['id']) -> select();
					$theStoreArr[$k]['num']=count($resArr);
				}else{
					$resArr = $this -> theStore -> where('sj_id='.$v['id']) -> select();
					$theStoreArr[$k]['num']=count($resArr);
				}
				$wh['rank_id']=$v['shop_rank'];
				$row = $this -> storeRank -> where($wh) -> find();
				$theStoreArr[$k]['shop_rank_name']=$row['shop_rank_name'];
				$sjwh['id']=$v['sj_id'];
				$sjrow = $this -> theStore -> field('shop_name') -> where($sjwh) -> find();
				$theStoreArr[$k]['sj_shop_name']=$sjrow['shop_name'];

				$zdwh['id']=$v['zd_shop_id'];
				$zdrow = $this -> theStore -> field('shop_name') -> where($zdwh) -> find();
				$theStoreArr[$k]['zd_shop_name']=$zdrow['shop_name'];
				if($v['bd_uid']!=0){
					$bdwh['uid']=$v['bd_uid'];
					$bdrow = $this -> user -> field('nickname,headimgurl,uid') -> where($bdwh) -> find();
					$theStoreArr[$k]['nickname']=$bdrow['nickname'];
					$theStoreArr[$k]['headimgurl']=$bdrow['headimgurl'];
					$theStoreArr[$k]['uid']=$bdrow['uid'];
				}

		}
		// dump($this -> theStore -> fetchSql() -> where($where) -> where($timewhere) -> order('add_time desc') -> limit($start, $showpage) -> select());

		$page = new \Think\Page($count, $showpage, $data);
		//展示分页
		$show = $page -> show();
		//		echo json_encode($theStoreArr,$page);
				// dump($p);
		//		echo strtotime($data['start']);
		//		exit;
		$rankrow = $this -> storeRank ->where("rank_id !=1") -> select();//查询店铺等级数据
		$this -> assign('rankrow', $rankrow);
		$this -> assign('show', $show);
		$this -> assign('data', $data);
		$this -> assign('arr', $theStoreArr);
		$this -> display('theStore/lists');
	}
//查看详情页面
	public function lists_xj() {
		// !is_null($this -> theStore) ? : $this -> theStore = D('theStore');
		//print_r(I('get.'));die;
		//print_r($_GET);die;
		//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? I('get.p') : 1;
		$start = ($p - 1) * $showpage;
		//判断高级搜索
		$data = array_merge(I('get.'),I('post.'));
		//dump($data);die;
		//设置查询条件
		$where = array();
		//时间条件
		$timewhere = array();
		//判断赋值
		if ($data) {
			!$data['id'] ? : $where['id'] = $data['id'];
			$data['id'] !== '0' ? : $where['id'] = $data['id'];
			!$data['shop_name'] ? : $where['shop_name'] = $data['shop_name'];
			$data['shop_name'] !== '0' ? : $where['shop_name'] = $data['shop_name'];
			
			!$data['user_name'] ? : $where['user_name'] = $data['user_name'];
			$data['user_name'] !== '0' ? : $where['user_name'] = $data['user_name'];
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

		//dump($where);die;
		
		$rank = D('storeRank');
		$maxRankId = $rank -> max('rank_id');
		//查询搜索数
		if($_GET['shop_rank']==$maxRankId){


			$where['zd_shop_id'] = $_GET['sj_id'];
			
		}else{
			!$data['sj_id'] ? : $where['sj_id'] = $data['sj_id'];
			$data['sj_id'] !== '0' ? : $where['sj_id'] = $data['sj_id'];
		}
		//计算指定条件下的用户页数
		$count = $this -> theStore -> where($where) -> where($timewhere) -> count();
		//dump($count);die;
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		$theStoreArr = $this -> theStore -> where($where)-> limit($start, $showpage) -> select();
		//dump($this -> theStore -> fetchSql() -> where(array("sj_id"=>"{$_GET['sj_id']}")) -> select()); 
		//echo M()->getLastsql();die;
			//dump($theStoreArr);die;
			foreach ($theStoreArr as $k => $v) {
				
				//echo M()->getLastsql();die;
				$wh['rank_id']=$v['shop_rank'];
				$row = $this -> storeRank -> where($wh) -> find();
				$theStoreArr[$k]['shop_rank_name']=$row['shop_rank_name'];
				$sjwh['id']=$v['sj_id'];
				$sjrow = $this -> theStore -> field('shop_name') -> where($sjwh) -> find();
				$theStoreArr[$k]['sj_shop_name']=$sjrow['shop_name'];
				$zdwh['id']=$v['zd_shop_id'];
				$zdrow = $this -> theStore -> field('shop_name') -> where($zdwh) -> find();
				$theStoreArr[$k]['zd_shop_name']=$zdrow['shop_name'];
				$user =D('user');
				$wh['shop_id'] =$v['id'];
				$wh['user_rank'] = 2;
				$userdata = $user->field('count(uid) as number')->where($wh)->find();

				$theStoreArr[$k]['number'] =$userdata['number'];

		}
		//dump($this -> theStore -> fetchSql() -> where($where) -> where($timewhere) -> order('add_time desc') -> limit($start, $showpage) -> select());
		$page = new \Think\Page($count, $showpage, $data);
		
		//展示分页
		$show = $page -> show();

		//		echo json_encode($theStoreArr,$page);
			
		//		echo strtotime($data['start']);
		//		exit;
		// $rankrow = $this -> storeRank ->where("rank_id !=1") -> select();//查询店铺等级数据
		// $this -> assign('rankrow', $rankrow);
		$this -> assign('show', $show);
		//$this -> assign('data', $data);
		$this -> assign('arr', $theStoreArr);
		$this -> display('theStore/lists_xj');
	}
	//审核店铺
	public function shenhe(){
		//echo '测试中..';
		// $data = I('param.');
		// $code = '恭喜你在天天惠开店成功，快去登录使用吧';
  //       $duanxin = $this->sendsms($data['tel'], $code);
  //       dump($data);
  //       dump($duanxin);
  //       exit;
		//获取参数
		$data = I('param.');
		$this->theStore->startTrans();
		//进行更新操作
		$bool = $this->theStore->save($data);
		if($data['is_check'] == 1){
			//插入银行卡记录表
			!is_null($this -> shopEarnings) ? : $this -> shopEarnings = D('shopEarnings');
			//插入店铺收益
			!is_null($this -> shopBankCard) ? : $this -> shopBankCard = D('shopBankCard');
			$bool1 = $this->shopEarnings->add(array('shop_id'=>$data['id'],'add_time'=>time()));
			$bool2 = $this->shopBankCard->add(array('shop_id'=>$data['id'],'shop_name'=>$data['shop_name'],'add_time'=>time()));
			// $code = $data['shop_name'];
			$array = Array('code'=>C('BRAND_NAME'));
			$smss = "pass_sms_id";
		}else{
			// $code = $data['shop_name'];
			$array = Array('code'=>C('BRAND_NAME'),'content'=>'重新');
			$smss = "reject_sms_id";
			$bool1 = true;
			$bool2 = true;
		}
		

		// dump($bool);
		// dump($bool1);
		// dump($bool2);
		//dump($data);
		if($bool && $bool1 && $bool2){
			//dump($data);
			//查询是否有绑定Uid
			$shop_data = D('the_store')->field('bd_uid')->where(array('id'=>"{$data['id']}"))->find();
			if($shop_data['bd_uid'] > 0){
				//dump($shop_data);exit;
				$str = $this->relationship($data['id'], $shop_data['bd_uid']);
				//dump($str);exit;
				if($str['error'] != 0){
					$this->theStore->rollback();
					$this->error($str['str'],U('admin/thestore/lists/is_check/'.$data['check']),1);
					exit;
				}
			}
			
            $duanxin = $this->sendsms($data['tel'], $smss, $array);
            if($duanxin){
            	$this->theStore->commit();
            	$this->success('审核成功',U('admin/thestore/lists/is_check/'.$data['check']),1);
            }else{
            	$this->theStore->rollback();
            	exit;
				$this->error('审核失败',U('admin/thestore/lists/is_check/'.$data['check']),1);
            }
			
		}else{
			$this->theStore->rollback();
			$this->error('审核失败',U('admin/thestore/lists'),1);
		}
	}
	//正则匹配手机格式 by wang

    private function mobile($mobile){
        $chars = "/^1[3,4,5,6,7,8,9][0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$/";
        if (preg_match($chars, $mobile)){
            return true;
        }else{
            return false;
        }
    }
	//发送短信
    private function sendsms($phone, $smss = '',$array = ''){
    	//获取对应的配置信息
    	!is_null($this -> otherConfig) ? : $this -> otherConfig = M('otherConfig');
    	$parent_id = $this->otherConfig->where(array('parent_id'=>0,'key'=>'sms'))->getField('id');
    	//根据parent_id获取对应的配置值
    	$list = $this->otherConfig->where(array('parent_id'=>$parent_id,'_string'=>'`key`="'.$smss.'" OR `key`="sms_ak" OR `key`="sms_as" OR `key`="sms_sign"'))->select();
        $sms = new SmsApi($list[1]['value'], $list[2]['value']);
        $response = $sms->sendSms(
            $list[3]['value'], // 短信签名
            $list[0]['value'], // 短信模板编号
            $phone, // 短信接收者
            // Array (  // 短信模板中字段的值
            //     "code"=>$code
                
            // ),
            $array,
            "123"   // 流水号,选填
        );
        // dump($response);
        // dump($smss);
        // exit;
        // return $response;
        if($response->Code == 'OK'){
            return true;
        }else{
            return false;
        }
    }

    //店铺提现列表
	public function shop_tixian(){
		$tixian_list = $this->tixian_list();
		//dump($tixian_list);die;
		$this -> assign('limit', $tixian_list['limit']);
		$this -> assign('tixian', $tixian_list['tixian']);
		$this -> display('theStore/shop_tixian');
	}
	//店铺提现列表异步请求`
	public function shop_tixian_query(){
		$tixian = $this->tixian_list();
		$this->ajaxReturn($tixian);exit;
		//dump($tixian);
	}

	//店铺收益订单详情
	public function shop_tixian_list(){
		$earnings_record = $this->earnings_record();
		//dump($earnings_record);die;
		$this -> assign('type', $earnings_record['type']);
		$this -> assign('tixian', $earnings_record['tixian']);
		$this -> assign('earnings_record', $earnings_record['earnings_record']);
		$this -> assign('order_type', $this->order_type);
		$this->assign('is_tixian', $this->is_tixian);
		$this -> assign('limit', $earnings_record['limit']);
		$this -> display('theStore/shop_tixian_list');
	}

	//店铺收益订单详情ajax返回
	public function shop_tixian_list_query(){
		//echo '123';
		$earnings_record = $this->earnings_record();
		$this->ajaxReturn($earnings_record);exit;
	}

	//所有店铺收益
	public function earnings(){
		$earninge = $this->earnings_list();
		//dump($earninge);
		$this -> assign('limit', $earninge['limit']);
		$this -> assign('shop_earnings', $earninge['shop_earnings']);
		$this -> display('theStore/earnings');
	}
	//所有店铺收益ajax返回
	public function earnings_query(){
		$earninge = $this->earnings_list();
		$this->ajaxReturn($earninge);exit;
	}

	//店铺提现审核通过
	public function approved(){
		if(!empty($_POST['tixian_id'])){
			$tixian = D('shop_tixian')->where("`id`='{$_POST['tixian_id']}'")->find();
			if($tixian){
				M()->startTrans();
				$data['audit_time'] = time();
				$data['status'] = '2';
				$str = M('shop_tixian')->where(array('id'=>"{$_POST['tixian_id']}",'status' => '1'))->data($data)->save();
				if($str){
					M()->commit();
					$arr['error'] = '0';
				}else{
					M()->rollback();
					$arr['error'] = '2';
					$arr['meg'] = '抱歉！修改数据失败，请重新审核！';
				}
			}else{
				$arr['error'] = '1';
				$arr['meg'] = '没有查询到应该提现记录！';
			}
			$this->ajaxReturn($arr);
		}
		//dump($_POST);
	}

	//店铺提现审核通过
	public function approved_ls(){
		if(!empty($_POST['tixian_id'])){
			$tixian = D('shop_tixian')->where("`id`='{$_POST['tixian_id']}'")->find();
			if($tixian){
				M()->startTrans();
				$data['audit_time'] = time();
				$data['status'] = '3';
				$str = M('shop_tixian')->where(array('id'=>"{$_POST['tixian_id']}",'status' => '2'))->data($data)->save();
				if($str){
					M()->commit();
					$arr['error'] = '0';
				}else{
					M()->rollback();
					$arr['error'] = '2';
					$arr['meg'] = '抱歉！修改数据失败，请重新审核！';
				}
			}else{
				$arr['error'] = '1';
				$arr['meg'] = '没有查询到应该提现记录！';
			}
			$this->ajaxReturn($arr);
		}
		//dump($_POST);
	}

	//审核拒绝
	public function refused_to(){
		if(!empty($_POST['tixian_id'])){
			M()->startTrans();
			$data['audit_time'] = time();
			$data['status'] = '4';
			$data['remarks'] = $_POST['judge'];
			$str = M('shop_tixian')->where(array('id'=>"{$_POST['tixian_id']}",'status' => '1'))->data($data)->save();
			if($str){
				M()->commit();
				$arr['error'] = '0';
			}else{
				M()->rollback();
				$arr['error'] = '2';
				$arr['meg'] = '抱歉！修改数据失败，请重新审核！';
			}
			$this->ajaxReturn($arr);
		}
	}

	//店铺银行卡信息的修改
	public function coop_bank_update(){
		$caname = $_GET['caname'];
		$shop_id = $_GET['shop_id'];
		$type = $_GET['type'];
		if(!empty($caname) && !empty($shop_id) && !empty($type)){
			$data[$type] = $caname;
			$err = D('shop_bank_card')->where("`shop_id`={$shop_id}")->data($data)->save();
			if($err){
				$this->ajaxReturn('0');
			}else{
				$this->ajaxReturn('1');
			}
		}else{
			$this->ajaxReturn('1');
		}
	}

	/**
	* ==================提现数据导出=========
	*/
	public function shop_tixian_exprets(){
		Vendor('PHPExcel.PHPExcel');
        Vendor('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
        $tixian_list = $this->tixian_list();
      	$expTitle="店铺提现数据";//表名
      	//dump($expTitle);exit;
        $expCellName = array(
             array('shop_name','审请人(店铺id)店铺名称'),
             array('bank_name','银行卡姓名'),
             array('coop_bank','银行卡号'),
             array('bank_phone','开户电话'), 
             array('bank_address','开户行的详细地址'),
             array('bank_city','开户行城市'),
             array('order_sum','提现订单统计'),
             array('money','提现金额'),
             array('age_money','到帐金额'),
             array('poundage','手续费'),
             array('time','审请时间'),
             array('status','审核状态'),
             array('remarks','备注'),
             array('audit_time','审核时间'),
        );
        $expTableData=array(); 
        for($i = 0; $i < count($tixian_list); $i++){
            array_push($expTableData, array(//这里的需要导出的内容，要注意键名跟上面的字段键名要一致
                'shop_name'=>" ".$tixian_list[$i]['user_name'].'( '.$tixian_list[$i]['shop_id'].' )'.$tixian_list[$i]['shop_name'],
                'bank_name'=>$tixian_list[$i]['bank_name'],
                'coop_bank'=>$tixian_list[$i]['coop_bank'],
                'bank_phone'=>$tixian_list[$i]['bank_phone'],
                'bank_address'=>$tixian_list[$i]['bank_address'],
                'bank_city'=>" ".$tixian_list[$i]['bank_city'],
                'order_sum'=>$tixian_list[$i]['order_sum'] .'( '.$tixian_list[$i]['order_number'].' )',
                'money'=>$tixian_list[$i]['money'],
                'age_money'=>$tixian_list[$i]['age_money'],
                'poundage'=>$tixian_list[$i]['poundage'],
                'time'=>$tixian_list[$i]['time'],
                'status'=>$tixian_list[$i]['status'],
                'remarks'=>$tixian_list[$i]['remarks'],
                'audit_time'=>$tixian_list[$i]['audit_time'],
            ));
        }
       exports($expTitle, $expCellName, $expTableData);
       exit;
		//dump($tixian_list);
	}
	/**
	*-------提现数据处理
	*/
	public function tixian_list(){
		$limit['page_num'] = $_POST['page_num'] ? trim($_POST['page_num']) : '10';
		$limit['page'] = $_POST['page'] ? trim($_POST['page']) : '1';
		$where['status'] = !empty($_POST['status']) ? $_POST['status'] : '';
		$where['shop_id'] = !empty($_POST['shop_id']) ? $_POST['shop_id'] : '';
		$where['shop_name'] = !empty($_POST['shop_name']) ? $_POST['shop_name'] : '';
		$where['bank_name'] = !empty($_POST['bank_name']) ? $_POST['bank_name'] : '';
		$where['end_time'] = !empty($_POST['end_time']) ? $_POST['end_time'] : '';
		$where['sta_time'] = !empty($_POST['sta_time']) ? $_POST['sta_time'] : '';
		$where['exports'] = $_GET['exports'];
		//dump($_POST);
		//dump($where);
		if($where['exports'] != ''){
			$where['status'] = !empty($_GET['status']) ? $_GET['status'] : '';
			$where['shop_id'] = !empty($_GET['shop_id']) ? $_GET['shop_id'] : '';
			$where['shop_name'] = !empty($_GET['shop_name']) ? $_GET['shop_name'] : '';
			$where['bank_name'] = !empty($_GET['bank_name']) ? $_GET['bank_name'] : '';
			$where['end_time'] = !empty($_GET['end_time']) ? $_GET['end_time'] : '';
			$where['sta_time'] = !empty($_GET['sta_time']) ? $_GET['sta_time'] : '';
		}
		$wheres = ' 1 ';
		if(!empty($where['status'])){
			$wheres .= " AND a.`status` = '{$where['status']}'";
		}
		if(!empty($where['shop_id'])){
			$wheres .= " AND a.`shop_id` = '{$where['shop_id']}'";
		}
		if(!empty($where['shop_name'])){
			$wheres .= " AND c.`shop_name` LIKE '%".$where['shop_name']."%'";
		}
		if(!empty($where['bank_name'])){
			$wheres .= " AND b.`bank_name` LIKE '%".$where['bank_name']."'";
		}
		if(!empty($where['end_time'])){
			$wheres .= " AND a.`add_time` <= '".strtotime($where['end_time'])."'";
		}
		if(!empty($where['sta_time'])){
			$wheres .= " AND a.`add_time` >= '".strtotime($where['status'])."'";
		}

		//echo $wheres;
		//判断是否是导出
		if($where['exports'] != 'exports'){
			$count = D()->query("SELECT count(*) as count FROM `app_shop_tixian` AS a INNER JOIN `app_shop_bank_card` AS b ON a.`shop_id` = b.`shop_id` INNER JOIN `app_the_store` AS c ON a.`shop_id` = c.`id` WHERE {$wheres}");// 查询满足要求的总记录数
			$limit['count'] = $count['0']['count'];
			$limit['page_mun'] = ceil($limit['count'] / $limit['page_num']);
			if($limit['page'] < '1'){
		      $limit['page'] = 1;
		    }
		    if($limit['page'] > $limit['page_mun']){
		      $limit['page'] = $limit['page_mun'];
		    }
		    $start = (($limit['page'] - 1) * $limit['page_num']);
		    if($start < 0){
		    	$start = 0;
		    }
		    //echo $limit['page_num'];die;
			//$page = $this->getpage($count, $limit['mun_page']);
		    // $sql = "SELECT a.`money`, a.`add_time` AS `time`, a.`audit_time`, a.`status`, a.`remarks`, a.`id` AS tixian_id, a.`all_order_sn`, a.`order_number`, b.*, c.`user_name` FROM `app_shop_tixian` AS a INNER JOIN `app_shop_bank_card` AS b ON a.`shop_id` = b.`shop_id` INNER JOIN `app_the_store` AS c ON a.`shop_id` = c.`id` WHERE {$wheres} ORDER BY a.`status` DESC, a.`add_time` DESC LIMIT {$start}, {$limit['page_num']}";
		    // 	echo $sql;die; 
			
			$tixian = D()->query("SELECT a.`money`,a.`shop_money`,a.`achievement_money`,a.`achievement_sn`,((a.`achievement_money`+a.`shop_money`)-a.`money`) as kou_money, a.`add_time` AS `time`, a.`audit_time`, a.`status`, a.`remarks`, a.`id` AS tixian_id,a.`all_order_sn`, a.`order_number`, b.*, c.`user_name` FROM `app_shop_tixian` AS a INNER JOIN `app_shop_bank_card` AS b ON a.`shop_id` = b.`shop_id` INNER JOIN `app_the_store` AS c ON a.`shop_id` = c.`id` WHERE {$wheres} ORDER BY a.`status` DESC, a.`add_time` DESC LIMIT {$start}, {$limit['page_num']}");
			// dump($tixian);die;
			for($i = 0; $i < count($tixian); $i++){

				$tixian[$i]['time'] = date("Y-m-d H:i", $tixian[$i]['time']);
				$tixian[$i]['audit_time'] = date("Y-m-d H:i", $tixian[$i]['audit_time']);
				
				$order_sum = D()->query("SELECT SUM(`money`) AS `money` FROM `app_shop_earnings_record` WHERE order_sn in({$tixian[$i]['all_order_sn']}) AND `is_tixian` = '1' LIMIT 1");
				// dump($order_sum);die;
				$tixian[$i]['order_sum'] = $order_sum['0']['money'] ? $order_sum['0']['money'] : '0.00';
				//$tixian[$i]['status'] = $this->tixian_status[$tixian[$i]['status']];
			}
			return array('tixian' => $tixian, 'limit' => $limit);
		}else{
			$sql ="SELECT a.`money`, a.`add_time` AS `time`, a.`audit_time`, a.`status`, a.`remarks`, a.`id` AS tixian_id, a.`all_order_sn`, a.`order_number`, b.*, c.`user_name` FROM `app_shop_tixian` AS a INNER JOIN `app_shop_bank_card` AS b ON a.`shop_id` = b.`shop_id` INNER JOIN `app_the_store` AS c ON a.`shop_id` = c.`id` WHERE {$wheres} ORDER BY a.`status` DESC, a.`add_time` DESC";


			for($i = 0; $i < count($tixian); $i++){
				$tixian[$i]['time'] = date("Y-m-d H:i", $tixian[$i]['time']);
				$tixian[$i]['audit_time'] = date("Y-m-d H:i", $tixian[$i]['audit_time']);
				$tixian[$i]['status'] = $this->tixian_status[$tixian[$i]['status']];
				$order_sum = D()->query("SELECT SUM(`money`) AS `money` FROM `app_shop_earnings_record` WHERE order_sn in({$tixian[$i]['all_order_sn']}) AND `is_tixian` = '1' LIMIT 1");
				//dump($order_sum);
				$tixian[$i]['order_sum'] = $order_sum['0']['money'] ? $order_sum['0']['money'] : '0.00';

			}
			//print_r($tixian);die;
			return $tixian;
		}
	}


	/**
	*-------返回店铺收益的数据处理
	*/
	public function earnings_list(){
		$limit['page_num'] = $_POST['page_num'] ? trim($_POST['page_num']) : '15';
		$limit['page'] = $_POST['page'] ? trim($_POST['page']) : '1';
		$where['status'] = $_POST['status'];
		$where['shop_id'] = !empty($_POST['shop_id']) ? $_POST['shop_id'] : '';
		$where['shop_name'] = !empty($_POST['shop_name']) ? $_POST['shop_name'] : '';
		$where['user_name'] = !empty($_POST['user_name']) ? $_POST['user_name'] : '';
		$where['bank_name'] = !empty($_POST['bank_name']) ? $_POST['bank_name'] : '';
		$where['end_time'] = !empty($_POST['end_time']) ? $_POST['end_time'] : '';
		$where['sta_time'] = !empty($_POST['sta_time']) ? $_POST['sta_time'] : '';
		//dump($_POST);
		//dump($where);
		$wheres = ' 1 ';
		if($where['status'] != ''){
			$wheres .= " AND a.`shop_type` = '{$where['status']}'";
		}
		if(!empty($where['shop_id'])){
			$wheres .= " AND a.`id` = '{$where['shop_id']}'";
		}
		if(!empty($where['shop_name'])){
			$wheres .= " AND a.`shop_name` LIKE '%".$where['shop_name']."%'";
		}
		if(!empty($where['bank_name'])){
			$wheres .= " AND b.`bank_name` LIKE '%".$where['bank_name']."%'";
		}
		if(!empty($where['user_name'])){
			$wheres .= " AND a.`user_name` LIKE '%".$where['user_name']."%'";
		}
		if(!empty($where['end_time'])){
			$wheres .= " AND b.`the_time` <= '".strtotime($where['end_time'])."'";
		}
		if(!empty($where['sta_time'])){
			$wheres .= " AND b.`the_time` >= '".strtotime($where['sta_time'])."'";
		}
		//echo $wheres;

		$count = D()->query("SELECT COUNT(*) AS `count` FROM `app_the_store` AS a INNER JOIN `app_shop_earnings` AS b ON a.`id` = b.`shop_id` WHERE {$wheres}");// 查询满足要求的总记录数
		$limit['count'] = $count['0']['count'];
		$limit['page_mun'] = ceil($limit['count'] / $limit['page_num']);
		if($limit['page'] < '1'){
	      $limit['page'] = 1;
	    }
	    if($limit['page'] > $limit['page_mun']){
	      $limit['page'] = $limit['page_mun'];
	    }
	    $start = (($limit['page'] - 1) * $limit['page_num']);
	    if($start < 0){
	    	$start = 0;
	    }

		$shop_earnings = D()->query("SELECT a.`id` AS `shop_id`, a.`user_name`, a.`shop_name`, a.`shop_type`, b.`money`, b.`the_time` FROM `app_the_store` AS a INNER JOIN `app_shop_earnings` AS b ON a.`id` = b.`shop_id` WHERE {$wheres} LIMIT {$start}, {$limit['page_num']}");
		//dump($shop_earnings);
		
		for($i = 0; $i < count($shop_earnings); $i++){
			//echo $this->shop_type[$shop_earnings[$i]['shop_type']];
			$rearne_list = D()->query("SELECT sum(`money`) AS `money`, `is_tixian` FROM `app_shop_earnings_record` WHERE `shop_id` = '{$shop_earnings[$i][shop_id]}' GROUP BY `is_tixian`");
			for($j = 0; $j < count($rearne_list); $j++){
				$shop_earnings[$i]['record_money'] += $rearne_list[$j]['money'];
				if($rearne_list[$j]['is_tixian'] == '1'){
					$shop_earnings[$i]['no_tixian'] += $rearne_list[$j]['money'];
				}elseif($rearne_list[$j]['is_tixian'] = '0'){
					$shop_earnings[$i]['tixian'] += $rearne_list[$j]['money'];
				}
			}
			$shop_earnings[$i]['record_money'] = $shop_earnings[$i]['record_money'] ? $shop_earnings[$i]['record_money'] : '0';
			$shop_earnings[$i]['no_tixian'] = $shop_earnings[$i]['no_tixian'] ? $shop_earnings[$i]['no_tixian'] : 0;
			$shop_earnings[$i]['tixian'] = $shop_earnings[$i]['tixian'] ? $shop_earnings[$i]['tixian'] : 0;
			$shop_earnings[$i]['the_time'] = empty($shop_earnings[$i]['the_time']) ? '' : date("Y-m-d H:i", $shop_earnings[$i]['the_time']);
			$shop_earnings[$i]['shop_type'] = $this->shop_type[$shop_earnings[$i]['shop_type']];
		}
		//dump($shop_earnings);
		return array('shop_earnings' => $shop_earnings, 'limit' => $limit);
	}

	/**
	*-------返回店铺收益详情的数据处理
	*/
	public function earnings_record(){
		$limit['page_num'] = $_POST['page_num'] ? trim($_POST['page_num']) : '15';
		$limit['page'] = $_POST['page'] ? trim($_POST['page']) : '1';
		$where['order_type'] = $_POST['order_type'];
		$where['buyid'] = !empty($_POST['buyid']) ? $_POST['buyid'] : '';
		$where['is_tixian'] = $_POST['is_tixian'] != '' ? $_POST['is_tixian'] : '';
		$where['end_time'] = !empty($_POST['end_time']) ? $_POST['end_time'] : '';
		$where['sta_time'] = !empty($_POST['sta_time']) ? $_POST['sta_time'] : '';
		//dump($_POST);
		//dump($where);
		$wheres = ' 1 ';
		if($where['order_type'] != ''){
			$wheres .= " AND a.`order_type` = '{$where['order_type']}'";
		}
		if(!empty($where['buyid'])){
			$wheres .= " AND a.`buyid` = '{$where['buyid']}'";
		}
		if($where['is_tixian'] != ''){
			$wheres .= " AND a.`is_tixian` = '".$where['is_tixian']."'";
		}
		if(!empty($where['end_time'])){
			$wheres .= " AND a.`add_time` <= '".strtotime($where['end_time'])."'";
		}
		if(!empty($where['sta_time'])){
			$wheres .= " AND a.`add_time` >= '".strtotime($where['status'])."'";
		}

		if(!empty($_GET['tixian_id']) || $_POST['type'] == 'tixian'){
			$tixian_id = $_GET['tixian_id'] ? $_GET['tixian_id'] : $_POST['id'];
			//dump(123);
			$tixian = D('shop_tixian')->where("id='{$tixian_id}'")->find();
			$tixian['order_count'] = 0;
			/*$order_list = explode(',', $tixian['all_order_sn']);
			$all_order_sn = '';
			for($i = 0; $i < count($order_list); $i++){
				$all_order_sn .= "'".$order_list[$i]."',";
			}
			$all_order_sn = trim($all_order_sn, ',');*/
			$wheres .= " AND a.`order_sn` in({$tixian['all_order_sn']})";
			$type['type'] = 'tixian';
			$type['id'] = $tixian_id;
			
		}
		if(!empty($_GET['shop_id']) ||  $_POST['type'] == 'shop'){
			$shop_id = $_GET['shop_id'] ? $_GET['shop_id'] : $_POST['id'];
			$wheres .= " AND a.`shop_id` = '{$shop_id}'";
			$type['type'] = 'shop';
			$type['id'] = $shop_id;
		}
		//dump($wheres);
		//echo "SELECT COUNT(*) AS `count` FROM `app_shop_earnings_record` AS a WHERE {$wheres}";
		$count = D()->query("SELECT COUNT(*) AS `count` FROM `app_shop_earnings_record` AS a WHERE {$wheres}");// 查询满足要求的总记录数
		$limit['count'] = $count['0']['count'];
		$limit['page_mun'] = ceil($limit['count'] / $limit['page_num']);
		if($limit['page'] < '1'){
	      $limit['page'] = 1;
	    }
	    if($limit['page'] > $limit['page_mun']){
	      $limit['page'] = $limit['page_mun'];
	    }
	    $start = (($limit['page'] - 1) * $limit['page_num']);
	    if($start < 0){
	    	$start = 0;
	    }
		$earnings_record = D()->query("SELECT *, (SELECT `nickname` FROM `app_user` WHERE `uid` = a.`buyid`) AS buy_name FROM `app_shop_earnings_record` AS a WHERE {$wheres} LIMIT {$start}, {$limit['page_num']}");
		// dump($earnings_record);die;

		for($i = 0; $i < count($earnings_record); $i++){
			$tixian['order_count'] += $earnings_record[$i]['money'];
			$earnings_record[$i]['order_type'] = $this->order_type[$earnings_record[$i]['order_type']];
			$earnings_record[$i]['is_tixian'] = $this->is_tixian[$earnings_record[$i]['is_tixian']];
			$earnings_record[$i]['add_time'] = date("Y-m-d H:i", $earnings_record[$i]['add_time']);

			$earnings_record[$i]['ti_time'] = $earnings_record[$i]['ti_time'] ? date("Y-m-d H:i", $earnings_record[$i]['ti_time']):'';
		}
		// dump($earnings_record);die;
		return array('earnings_record' => $earnings_record, 'limit' => $limit, 'tixian' => $tixian, 'type' => $type);
	}
	//是升级店铺功能
	public function change(){
		$data = I('post.');
		$shop_id = $data['shop_id'];
		$rank_id = $data['rank_id'];
		$shop = D('the_store');
		$rank = D('store_rank');
		$maxrank = $rank->max("rank_id");
		$where['id'] = $shop_id;
		$storeRank = $shop->field('shop_rank,zd_shop_id')->where($where)->find();
		if($rank_id > $storeRank['shop_rank']){
			if($rank_id==$maxrank){
				if( $storeRank['shop_rank']==1){
					$arr['shop_rank']=$rank_id;
					$arr['zd_shop_id']=0;
					$res = $shop->where($where)->save($arr);
					if($res){
						echo 1;
					}else{
						echo 2;
					}
				}else{
					$row  = $this->all($shop_id);
					$b = rtrim($row, ',');
					//print_r($b);die;
					$arr['shop_rank']=$rank_id;
					$arr['zd_shop_id']=0;
					$res = $shop->where($where)->save($arr);
					//dump($res);die;
					if($res && $b!=''){
						$updatearr['zd_shop_id'] = $shop_id;
						$wh['id'] = array('in',$b);//cid在这个数组中，
						$result = $shop->where($wh)->save($updatearr);	
						if($result&&$res){
							echo 1;	
						}else{
							echo 2;
						}
					}elseif($res){
						echo 1;
					}else{
						echo 2;
					}
					//$this -> Common ->dp($shop);die;


				}
			}else{
				$arr['shop_rank']=$rank_id;
				$res = $shop->where($where)->save($arr);
				if($res){
					echo 1;
				}else{
					echo 2;
				}
			}



		
		}
		//dump($data);die;
		//		exit;
		// $data['start'] = $data['start']?0:1;
		// $bool = $this->ship->save($data);
		// if($bool){
		// 	echo $data['start'];
		// }else{
		// 	echo '2';
		// }
	}
	public function all($shop_id)
	{
	  global $str;
	  
	 //s$sql = "select sj_id,id from app_the_store where c= $shop_id";
	 //echo $sql;die;
	  $arr = D('the_store')->field('sj_id,id')->where("`sj_id` = '{$shop_id}'")->select();
	 	
	  // dump($arr);die;
	  // if($result && mysql_affected_rows()) {
	  //   while ($arr = mysql_fetch_array($result)) {
	  foreach ($arr as $key => $v) {
	  	 $str .= "" . $v["id"] . ",";
	     $this->all($v["id"]);
	  }
	     
	
	  return $str; 
	}

	//手动修改店铺用户的关系
	public function relationship_list(){
		$data = I('param.');
		$str = $this->relationship($data['id'], $data['user_id']);
		if($str['error'] == '0'){
			$this->success("修改成功，已经成功修改".$str['str']['num'].'个用户',U('admin/thestore/lists/is_check/'.$data['check']),1);
		}else{
			$this->success($str['str'],U('admin/thestore/lists/is_check/'.$data['check']),1);
		}

	}

	//修改店铺用户的关系
    public function relationship($shop_id, $user_id){
      $user = M('user');
      $time = time();
      //$shop_data = D('the_store')->field('id')->where("user_name='{$phone}'")->find();
      //dump($shop_data);exit;
      //$shop_id = $shop_data['id'];
      $user_data = $user->field('uid,user_rank,shop_id,sj_uid,nickname,headimgurl')->where("uid='{$user_id}'")->find();

      //dump($user_data);exit;
      if(empty($user_data)){
        $data['error'] = '1';
        $data['str'] = '没有查询到用户id！';
        return $data;
        exit();
      }
        if($user_data['user_rank']==1){
        $data['error'] = '1';
        $data['str'] = '当前UID不是会员';
        return $data;
        exit();
      }
      M()->startTrans();
      $str = $user->execute("UPDATE `app_user` SET `shop_id` = '{$shop_id}', `sj_uid` = '0', `bd_sj_uid` = '{$user_data[sj_uid]}' WHERE `uid` = '{$user_data[uid]}'");
      $str2 = $user->execute("UPDATE `app_the_store` SET `binding` = '1', `bd_uid` = '{$user_id}',`bd_time` = '{$time}'  WHERE `id` = '{$shop_id}'");

      $sj_user = $user->field('uid,user_rank,shop_id,sj_uid')->where("shop_id='{$user_data[shop_id]}'")->select();
      $array = $this->relationshop_list($user_data['uid'], $sj_user);
      //$arr = trim($array, ',');
      $str1 = '1';
      if($array['num'] != '0'){
        $string = substr($array['str'],0,strlen($array['str'])-1);
        $str1 = $user->execute("UPDATE `app_user` SET `shop_id` = '{$shop_id}' WHERE `uid` in($string)");
      }
      //dump($str1);exit;
      if($str && $str1){
        M()->commit();
        $data['error'] = '0';
        $data['str'] =$array ;
      }else{
        M()->rollback();
        $data['error'] = '1';
        $data['str'] ='修改用户下级关系错误';
      }
       return $data; 
    }

    //递归出店铺用户的下级
    public function relationshop_list($uid, $arr){
      static $str = '';
      static $num = '0';
      for($i = 0; $i < count($arr); $i++){
        if($arr[$i]['sj_uid'] == $uid){
          $str .= "'".$arr[$i]['uid']."',";
          $num ++;
          $this->relationshop_list($arr[$i]['uid'], $arr);
        }
      }
      return array('str' => $str, 'num' => $num);
    }

}
