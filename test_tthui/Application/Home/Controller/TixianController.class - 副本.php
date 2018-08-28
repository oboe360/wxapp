<?php
namespace Home\Controller;
// header('content-type:text/html;charset=utf-8');
// header("Access-Control-Allow-Origin:*");
header('content-type:application/x-www-form-urlencoded');
use Think\Controller;
class TixianController extends Controller
{
	private $tixian = NULL;
	private $userBankCard = NULL;
	private $user = NULL;
	private $qianbao = NULL;
	private $otherConfig = NULL;
	public function _initialize(){
		!is_null($this->tixian)?:$this->tixian = M('tixian');
		!is_null($this->userBankCard)?:$this->userBankCard = M('userBankCard');
		!is_null($this->user)?:$this->user = M('user');
	}
	//获取指定用户的收款信息
    public function userCodeMessage(){
    	
    	//获取用户uid
    	$uid = I('param.uid');
    	if($uid){
    		//查询是否存在二维码
	    	// dump($this->tixian);
	    	// exit;
	    	$user_money = $this->user->where(array('uid'=>$uid))->getField('user_money');
	    	$list = $this->userBankCard->field('user_name,coop_bank,bank_name,bank_phone,bank_address,bank_city')->where(array('uid'=>$uid))->find();
	    	//拼接返回参数
	    	$data = array();
	    	if($list){	
	    		$data['code'] = 1;
	    		$data['msg'] = '获取数据成功';
	    		$data['data'] = $list;
	    		$data['user_money'] = $user_money?:'0.00';
	    		$data['textHint'] = $this->textHint();
	    	}else{
	    		$data['code'] = 0;
	    		$data['user_money'] = $user_money?:'0.00';
	    		$data['textHint'] = $this->textHint();
	    		$data['msg'] = '没有银行卡信息';
	    	}
    	}else{
    		$data['code'] = 0;
    		$data['user_money'] = $user_money?:'0.00';
    		$data['textHint'] = $this->textHint();
	    	$data['msg'] = '用户信息不存在';
    	}
    	// dump($data);
    	echo json_encode($data);
    }
    //获取指定用户的收款信息
    public function userCodeMessages(){
    	
    	//获取用户uid
    	$uid = I('param.uid');
    	if($uid){
    		//查询用户信息
	    	// dump($this->tixian);
	    	// exit;
	    	$user_money = $this->user->where(array('uid'=>$uid))->getField('user_money');
	    	$list = $this->userBankCard->field('user_name,bank_name,coop_bank,id')->where(array('uid'=>$uid))->find();
	    	$list['coop_bank'] = substr($list['coop_bank'], -4);
	    	$procedure = $this->textHint($user_money);
	    	$textHint = $this->textHint();
	    	//拼接返回参数
	    	$data = array();
	    	if($list['bank_name']){	
	    		$data['code'] = 1;
	    		$data['msg'] = '获取数据成功';
	    		$data['data'] = $list;
	    		$data['user_money'] = $user_money?:'0.00';
	    		$data['textHint'] = $textHint;
	    		$data['procedure'] = $procedure;
	    	}else{
	    		$data['code'] = 0;
	    		$data['user_money'] = $user_money?:'0.00';
	    		$data['textHint'] = $textHint;
	    		$data['msg'] = '没有银行卡信息';
	    		$data['procedure'] = $procedure;
	    	}
    	}else{
    		$data['code'] = 0;
    		$data['user_money'] = $user_money?:'0.00';
    		$data['textHint'] = $textHint;
	    	$data['msg'] = '用户信息不存在';
	    	$data['procedure'] = $procedure;
    	}
    	// dump($data);
    	echo json_encode($data,JSON_UNESCAPED_UNICODE);
    }
    //获取指定用户的银行卡信息
    public function userBankCardMessages(){
    	
    	//获取用户uid
    	$uid = I('param.uid');
    	if($uid){
    		//查询用户信息
	    	// dump($this->tixian);
	    	$list = $this->userBankCard->field('user_name,coop_bank,bank_name,bank_phone,bank_address,bank_city,id')->where(array('uid'=>$uid))->find();
	    	//拼接返回参数
	    	$data = array();
	    	if($list['bank_name']){	
	    		$data['code'] = 1;
	    		$data['msg'] = '获取数据成功';
	    		$data['data'] = $list;
	    	}else{
	    		$data['code'] = 0;
	    		$data['msg'] = '没有银行卡信息';
	    	}
    	}else{
    		$data['code'] = 0;
	    	$data['msg'] = '用户信息不存在';
    	}
    	// dump($data);
    	echo json_encode($data,JSON_UNESCAPED_UNICODE);
    }
    //添加或修改银行卡信息
	public function addUserCodeMessages(){
		// echo json_encode(pathinfo($_FILES['file']['name']));
		// exit;
		//获取post数据
		$data = I('param.');
		//拼接返回结果
	    $param = array();
	 //    //判断手机格式
		if(!$this->mobile($data['bank_phone'])){
			$param['code'] = 0;
       		$param['msg'] = '手机号码错误';
       		echo json_encode($param,JSON_UNESCAPED_UNICODE);
			exit;
		}
		//判断银行卡格式
		if(!$this->verifyCard($data['coop_bank'])){
			$param['code'] = 0;
       		$param['msg'] = '银行卡号错误';
       		echo json_encode($param,JSON_UNESCAPED_UNICODE);
			exit;
		}
		if($data['uid']){
			//获取数据判断是否存在修改
			$list = $this->userBankCard->field('user_name,coop_bank,bank_name,bank_phone,bank_address,bank_city,id')->where(array('uid'=>$data['uid']))->find();
			// echo json_encode($list);
			// echo json_encode($data);
			// exit;
			//如果存在数据，判断修改操作
			if($list){
				if($data['user_name'] == $list['user_name'] && $data['coop_bank'] == $list['coop_bank'] && $data['bank_name'] == $list['bank_name'] && $data['bank_phone'] == $list['bank_phone'] && $data['bank_address'] == $list['bank_address'] && $data['bank_city'] == $list['bank_city']){
					$param['code'] = 0;
		        	$param['msg'] = '信息未做修改';
		        	echo json_encode($param,JSON_UNESCAPED_UNICODE);
					exit;
				}
				

				$data['the_time'] = time();
				$data['id'] = $list['id'];
				$bool = $this->userBankCard->save($data);
				if ($bool) {
		        	$param['code'] = 1;
		        	$param['msg'] = '保存信息成功';
		        	// return true;
		        } else {
		            $param['code'] = 0;
		        	$param['msg'] = '保存信息失败';
		        }
			}else{
				$data['add_time'] = time();
				// $data['id'] = $list['id'];
				$bool = $this->userBankCard->add($data);
				if ($bool) {
		        	$param['code'] = 1;
		        	$param['msg'] = '保存信息成功';
		        	// return true;
		        } else {
		            $param['code'] = 0;
		        	$param['msg'] = '保存信息失败';
		        }
			}
			
		}else{
			$param['code'] = 0;
	        $param['msg'] = '信息不存在';
		}
		echo json_encode($param,JSON_UNESCAPED_UNICODE);
		exit;
	}
	//添加或修改身份认证信息
	public function addIDCardMessages(){
		//获取post数据
		$data = I('param.');
		unset($data['face_photo']);
		unset($data['reverse_photo']);
		//拼接返回结果
	    $param = array();
	    //判断身份证格式
		if(!$this->isCreditNo($data['ID_card_no'])){
			$param['code'] = 0;
       		$param['msg'] = '身份证号码错误';
       		echo json_encode($param);
			exit;
		}
		$data['id_card_no'] = $data['ID_card_no'];
		// exit;
		if($data['uid']){
			//保存身份证图片
			if($_FILES){
				if ($_FILES['face_photo']['error'] === 0) {
			        $filePath = $_FILES['face_photo']['tmp_name'];
			       
			        $file = portal_qiniu($filePath, 'http://pc3eytwnu.bkt.clouddn.com', 'code-images');

			        $file = $file['key'];
			        if($file){
				    	$data['face_photo'] = $file;
				    }
			    }
			    if ($_FILES['reverse_photo']['error'] === 0) {
			    	// echo 1;
			    	// exit;
			        $filePath = $_FILES['reverse_photo']['tmp_name'];

			        $file = portal_qiniu($filePath, 'http://pc3eytwnu.bkt.clouddn.com', 'code-images');
			        // dump($file);
			        // exit;
			        $file = $file['key'];
			        if($file){
				    	$data['reverse_photo'] = $file;
				    }
			    }
			}
			// echo json_encode($data);
			// exit;
			//获取原来身份证图片
			$list = $this->userBankCard->field('face_photo,reverse_photo,id')->where(array('uid'=>$data['uid']))->find();
			if($list['id']){
				$data['id'] = $list['id'];
				$bool = $this->userBankCard->save($data);
				if ($bool) {
					if($list['face_photo'] && $data['face_photo']){
						$this->delQiniu($list['face_photo'], 'http://pc3eytwnu.bkt.clouddn.com', 'code-images');
					}
					if($list['reverse_photo'] && $data['reverse_photo']){
						$this->delQiniu($list['reverse_photo'], 'http://pc3eytwnu.bkt.clouddn.com', 'code-images');
					}
		        	$param['code'] = 1;
		        	$param['msg'] = '保存收款信息成功';
		        	// return true;
		        } else {
		            $param['code'] = 0;
		        	$param['msg'] = '保存信息失败';
		        }
			}else{
				$data['add_time'] = time();
				$bool = $this->userBankCard->add($data);
				if ($bool) {
		        	$param['code'] = 1;
		        	$param['msg'] = '保存收款信息成功';
		        	// return true;
		        } else {
		            $param['code'] = 0;
		        	$param['msg'] = '保存信息失败';
		        }
			}
			
		}else{
			$param['code'] = 0;
	        $param['msg'] = '信息不存在';
		}
		echo json_encode($param,JSON_UNESCAPED_UNICODE);
		exit;
	}
	//添加提现记录
	public function addTixianHistorys(){
		// dump(I('param.'));
    	// exit;
		//获取提现信息
		$tixiandata = I('param.');
		$uid = $tixiandata['uid'];
		//判断是否存在提现中记录
		$tixianid = $this->tixian->where(array('uid'=>$uid,'status'=>1))->getField('id');
		if($tixianid){
			$response['code'] = 0;
			$response['msg'] = '存在未审核提现';
			echo json_encode($response,JSON_UNESCAPED_UNICODE);
			exit;
		}
		//执行提现操作
		
		//响应数据
		$response = array();
		//根据uid，获取订单总金额，订单总数
		$orderArr = $this->getQianbaoSn($uid);
		//
		if(empty($orderArr)){
			$response['code'] = 0;
			$response['msg'] = '无可提现金额';
		}else{
			//获取数据判断是否存在修改
			$list = $this->userBankCard->field('user_name,coop_bank,bank_name,bank_phone,bank_address,bank_city,id_card_no,face_photo,reverse_photo,real_name')->where(array('uid'=>$tixiandata['uid']))->find();
			// $list['ID_card_no'] = $list['id_card_no'];
			$orderArr = array_merge($list, $orderArr);
			$orderArr['add_time'] = time();
			// echo $this->tixian->fetchSql()->add($orderArr);
			// dump($orderArr);
			// exit;
			$this->tixian->startTrans();
			//执行添加操作
			$bool = $this->tixian->add($orderArr);
			$bool1 = $this->user->save(array('uid'=>$uid,'user_money'=>0,'the_money'=>$orderArr['money']));
			$bool2 = $this->qianbao->where(array('income_uid'=>$uid))->save(array('is_tixian'=>1));
			// file_put_contents('result.txt', $bool.'a'.$bool1.'a'.$bool2);
			if($bool && $bool1 && $bool2){
				$response['code'] = 1;
				$response['msg'] = '提现成功';
				$this->tixian->commit();
			}else{
				$response['code'] = 0;
				$response['msg'] = '提现失败';
				$this->tixian->rollback();
			}
		}
		echo json_encode($response,JSON_UNESCAPED_UNICODE);
		
	}
	
    //添加或修改收款信息
	public function addUserCodeMessage($data = ''){
		// echo json_encode(pathinfo($_FILES['file']['name']));
		// exit;
		//获取post数据
		// $data = I('param.');
		//拼接返回结果
	    $param = array();
	    //判断手机格式
		if(!$this->mobile($data['bank_phone'])){
			$param['code'] = 0;
       		$param['msg'] = '手机号码错误';
       		echo json_encode($param);
			exit;
		}
		//判断银行卡格式
		if(!$this->verifyCard($data['coop_bank'])){
			$param['code'] = 0;
       		$param['msg'] = '银行卡号错误';
       		echo json_encode($param);
			exit;
		}
		if($data['uid']){
			//获取数据判断是否存在修改
			$list = $this->userBankCard->field('user_name,coop_bank,bank_name,bank_phone,bank_address,bank_city,id')->where(array('uid'=>$data['uid']))->find();
			// dump($list);
			// exit;
			//如果存在数据，判断修改操作
			if($list){
				if($data['user_name'] == $list['user_name'] && $data['coop_bank'] == $list['coop_bank'] && $data['bank_name'] == $list['bank_name'] && $data['bank_phone'] == $list['bank_phone'] && $data['bank_address'] == $list['bank_address'] && $data['bank_city'] == $list['bank_city']){
					return true;
				}
				

				$data['the_time'] = time();
				$data['id'] = $list['id'];
				$bool = $this->userBankCard->save($data);
				if ($bool) {
		        	// $param['code'] = 1;
		        	// $param['msg'] = '保存收款信息成功！';
		        	return true;
		        } else {
		            $param['code'] = 0;
		        	$param['msg'] = '保存信息失败';
		        }
			}else{
				$data['add_time'] = time();
				// $data['id'] = $list['id'];
				$bool = $this->userBankCard->add($data);
				if ($bool) {
		        	// $param['code'] = 1;
		        	// $param['msg'] = '保存收款信息成功！';
		        	return true;
		        } else {
		            $param['code'] = 0;
		        	$param['msg'] = '保存信息失败';
		        }
			}
			
		}else{
			$param['code'] = 0;
	        $param['msg'] = '信息不存在';
		}
		echo json_encode($param);
		exit;
	}
	//添加提现记录
	public function addTixianHistory(){
		// dump(I('param.'));
    	// exit;
		//获取提现信息
		$tixiandata = I('param.');
		$uid = $tixiandata['uid'];
		//判断是否存在提现中记录
		$tixianid = $this->tixian->where(array('uid'=>$uid,'status'=>1))->getField('id');
		if($tixianid){
			$response['code'] = 0;
			$response['msg'] = '存在未审核提现';
			echo json_encode($response);
			exit;
		}
		//保存提现信息
		$this->addUserCodeMessage($tixiandata);
		//执行提现操作
		
		//响应数据
		$response = array();
		//根据uid，获取订单总金额，订单总数
		$orderArr = $this->getQianbaoSn($uid);
		if(empty($orderArr)){
			$response['code'] = 0;
			$response['msg'] = '无可提现金额';
		}else{
			$orderArr['add_time'] = time();
			$this->tixian->startTrans();
			//执行添加操作
			$bool = $this->tixian->add($orderArr);
			$bool1 = $this->user->save(array('uid'=>$uid,'user_money'=>0,'the_money'=>$tixiandata['user_money']?:$orderArr['money']));
			$bool2 = $this->qianbao->where(array('income_uid'=>$uid))->save(array('is_tixian'=>1));
			// file_put_contents('result.txt', $bool.'a'.$bool1.'a'.$bool2);
			if($bool && $bool1 && $bool2){
				$response['code'] = 1;
				$response['msg'] = '提现成功';
				$this->tixian->commit();
			}else{
				$response['code'] = 0;
				$response['msg'] = '提现失败';
				$this->tixian->rollback();
			}
		}
		echo json_encode($response);
		
	}
	//获取提现历史记录
	public function tixianHistory(){
		//获取用户uid
		$data = I('param.');
		//请求条件
		$where = array('uid'=>$data['uid']);
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
		$count = $this->tixian->where($where)->count();
		//计算当前总页数
		$page = ceil($count/$pageShow);
		//获取数据
		$list = $this->tixian->field('money,status,add_time,audit_remarks')->limit($start,$pageShow)->where($where)->select();
		//循环替换数据
		foreach ($list as $k => $v) {
			$list[$k]['status'] = $v['status'] == 1?'等待审核':'提现成功';
			$list[$k]['audit_remarks'] = $v['audit_remarks']?:'';
			$list[$k]['htime'] = date('H:i',$v['add_time']);
			$list[$k]['ytime'] = date('Y-m-d',$v['add_time']);
			unset($list[$k]['add_time']);
		}
		//拼接返回数据
		$response = array();
		if($page){
			$response['page'] = $page;
			$response['code'] = 1;
			$response['msg'] = '数据获取成功';
			$response['data'] = $list;
		}else{
			$response['code'] = 0;
			$response['msg'] = '暂无数据！';
		}
		// dump($response);
		echo json_encode($response);
	}
	//提现文本提示接口
	//param $user_money 金额数量
	//return array
	private function textHint($user_money = 'true'){
		//获取配置信息
		$norm = $this->tixianConfig();
		$procedure = explode('+', $norm[1]['value']);
		$procedure[2] = rtrim($procedure[0],'%')/100;
		//拼接响应数据
		$response = array();
		if($user_money !== 'true'){
			if($user_money > 0){
				$response['procedure'] = $user_money*$procedure[2]+$procedure[1];
				$response['true_money'] = $user_money-$response['procedure'];
			}else{
				$response['procedure'] = '0.00';
				$response['true_money'] = '0.00';
			}
			
			return $response;
		}
		// $response['percent'] = $procedure[0];
		// $response['money'] = $procedure[1];
		// $response['norm'] = $norm[0]['value'];
		$response[] = '平台仅支持全额提现，最低提现金额为'.$norm[0]['value'].'元；';
		$response[] = '提现需要手续费，手续费计算公式为：本金*'.$procedure[0].'+'.$procedure[1].'（例：100*'.$procedure[0].'+'.$procedure[1].'='.(100*$procedure[2]+$procedure[1]).'）';
		return $response;
	}
	private function delQiniu($url, $qiniu_url = 'http://p2y8yvch3.bkt.clouddn.com', $bucket = 'goods-gallery')
    {
        if($url){
            $res = portal_delect(str_replace($qiniu_url.'/','',$url),$qiniu_url,$bucket, D('qiniuFile'));
            if($res){
                return false;
            }else{
                return true;
            }
        }else{
            return true;
        }
    }
    /**
     * 根据uid获取钱包流水信息，组合成需要信息返回
     * @access private
     * @param string $uid 指定要获取信息的用户id
     * @return array
     */
    private function getQianbaoSn($uid){

    	if(empty($uid)){
    		return false;
    	}
    	!is_null($this->qianbao)?:$this->qianbao = M('qianbaoSn');
    	//获取未提现钱包总流水
    	$list = $this->qianbao->field('income_money,order_sn')->where(array('income_uid'=>$uid,'is_tixian'=>0))->select();
    	if(empty($list)){
    		return false;
    	}
    	//初始化
    	$money = 0;
    	$str  = '';
    	//循环拼接需要数组
    	foreach ($list as $v) {
    		$str .= $v['order_sn'].',';
    		$money += $v['income_money'];
    	}
    	$str = rtrim($str, ',');
    	//判断金额是否足够提现
    	$norm = $this->tixianConfig();
    	if($norm[0]['value'] > $money){
    		$response = array();
    		$response['code'] = 0;
			$response['msg'] = '未达到提现额度';
			echo json_encode($response);
			exit;
    	}
    	//手续费
    	$procedure = explode('+', $norm[1]['value']);
    	$procedure[0] = rtrim($procedure[0],'%')/100;
    	$procedure = $money*$procedure[0]+$procedure[1];
    	//实际到账金额
    	$truemoney = $money-$procedure;
    	//获取订单总金额
    	// $money = $this->ordreInfo->field('()')
    	return array('all_order_sn'=>$str, 'order_number'=>count($list), 'money'=>$money,'uid'=>$uid,'procedure'=>$procedure,'truemoney'=>$truemoney);
    }
    //获取提现配置信息
    private function tixianConfig(){
    	!is_null($this->otherConfig)?:$this->otherConfig = M('otherConfig');
    	$parent_id = $this->otherConfig->where(array('parent_id'=>0,'key'=>'tixian'))->getField('id');
    	$norm = $this->otherConfig->where(array('parent_id'=>$parent_id,'_string'=>'`key`="norm" OR `key`="procedure"'))->select();
    	return $norm;
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
    //验证银行卡
    private function verifyCard($cardnum){
    	/**
		 * PHP实现Luhn算法（方式一）
		 * @author：http://nonfu.me
		 */
		// $cardnum = '7432810010473523';
		$arr_no = str_split($cardnum);
		$last_n = $arr_no[count($arr_no)-1];
		krsort($arr_no);
		$i = 1;
		$total = 0;
		foreach ($arr_no as $n){
		    if($i%2==0){
		        $ix = $n*2;
		        if($ix>=10){
		            $nx = 1 + ($ix % 10);
		            $total += $nx;
		        }else{
		            $total += $ix;
		        }
		    }else{
		        $total += $n;
		    }
		    $i++;
		}
		$total -= $last_n;
		$x = 10 - ($total % 10);
		if($x == $last_n){
		    return true;
		}else{
			return false;
		}
	}
	/**
	 * 验证身份证号
	 * @param $vStr
	 * @return bool
	 */
	private function isCreditNo($vStr){
	    $vCity = array(
	        '11','12','13','14','15','21','22',
	        '23','31','32','33','34','35','36',
	        '37','41','42','43','44','45','46',
	        '50','51','52','53','54','61','62',
	        '63','64','65','71','81','82','91'
	    );
	 
	    if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;
	 
	    if (!in_array(substr($vStr, 0, 2), $vCity)) return false;
	 
	    $vStr = preg_replace('/[xX]$/i', 'a', $vStr);
	    $vLength = strlen($vStr);
	 
	    if ($vLength == 18)
	    {
	        $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
	    } else {
	        $vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
	    }
	 
	    if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
	    if ($vLength == 18)
	    {
	        $vSum = 0;
	 
	        for ($i = 17 ; $i >= 0 ; $i--)
	        {
	            $vSubStr = substr($vStr, 17 - $i, 1);
	            $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
	        }
	 
	        if($vSum % 11 != 1) return false;
	    }
	 
	    return true;
	}	
}
