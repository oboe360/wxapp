<?php
namespace Home\Controller;
use Think\Controller;
use Admin\Controller\ThewxregController;
header('content-type:text/html;charset=utf-8');
class WxRegisterController extends Controller
{
	private $wxConfig = NULL;
	private $dirname = NULL;
	public function _initialize(){
		!is_null($this->wxConfig)?:$this->wxConfig = D('wxConfig');
		$this->dirname = $_SERVER['REQUEST_SCHEME'].'://'.dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
	}
	public function free_upgrade(){
		$uid = $_REQUEST['uid']; 
		$user_rank = D('user')->field('user_rank')->where("`uid` = '{$uid}'")->find();
		//$user_rank = 2;
		if ($user_rank == 2) {
			$fans_num = D('user')->where("`sj_uid` = '{$uid}'")->count();
			$upgrade_num = M('other_config')->where("key = 'upgrade_num'")->getField("value");
			 $bd_uid = M('the_store')->where('bd_uid ='.$uid)->find();
			if ($fans_num >= $upgrade_num && empty($bd_uid)) {
				$data['code'] = '1';
				echo json_encode($data);
			}else{
				$data['code'] = '2';
				echo json_encode($data);
			}
		}else{
			$data['code'] = '2';
			echo json_encode($data);
		}
	}
	public function testsss()
	{
		//dump($this->dirname);die;
			$erweima_url = M('other_config')->where('`key` ="erweima"')->getField("img_path");
		
			$url =json_decode($erweima_url,ture);
			$imgurl =$url[0];

			$url = $this->dirname.$imgurl;
			//echo $url;die;
		$aaa = new \Admin\Controller\ThewxregController();
		//dump($aaa->relationship("12","5"));die;
	}
	public function wx_register(){
		if (!empty($_REQUEST['mobile'])|| !empty($_REQUEST['recommendCode'])|| !empty($_REQUEST['password'])) {
	        //验证电话号码是否存在
	        $user_info = M("the_store")->where('user_name = '.$_REQUEST['mobile'])->select();
	        if (!empty($user_info)) {
	        	$data['code'] = 4;
	        	$data['msg'] = "此号码已经注册！";
	        	echo json_encode($data);
	            exit();
	        }
	        //验证上级推荐号是否存在
	        $user_info2 = M("the_store")->where('id = '.$_REQUEST['recommendCode'])->find();
	        $user_info3 = M("user")->where('uid = '.$_REQUEST['uid'])->getField("shop_id");
	        if (empty($user_info2) && $user_info3==$_REQUEST['shop_id']) {
	        	$data['code'] = 3;
	        	$data['msg'] = "上级推荐号不正确！";
	        	echo json_encode($data);
	            exit();
	        }
	        $rank = D('store_rank');
	        $maxrank = $rank->max("rank_id");
	        if ($user_info2['shop_rank'] != $maxrank) {
	            $data2['zd_shop_id'] = $user_info2['zd_shop_id'];
	        }else{
	             $data2['zd_shop_id'] = $user_info2['id'];
	        }
	        $User = M("the_store");
	        $data2['user_name'] = $_REQUEST['mobile'];
	        $data2['sj_id'] = $_REQUEST['recommendCode'];
	        $data2['password'] = md5($_REQUEST['password']);
	        $data2['add_time'] = time();
	        $data2['shop_type'] = '0';
	        $data2['is_check'] = '1';
	        // $data['sj_uid'] = $_REQUEST['recommendCode'];
	        $bool = $User->add($data2);

    		$aaa = new \Admin\Controller\ThewxregController();
			// // $aata->relationship($_REQUEST['shop_id'],$_REQUEST['uid']);
			// $erweima_url = M('other_config')->where("key = 'erweima'")->getField("value");
			// $url =json_decode($erweima_url,ture);
			// $imgurl =$url[0];
			$erweima_url = M('other_config')->where('`key` ="erweima"')->getField("img_path");
		
			$url =json_decode($erweima_url,ture);
			$imgurl =$url[0];

			$url = $this->dirname.$imgurl;
			$aaa->relationship($bool,$_REQUEST['uid']);
	        if ($bool) {
	        	$data['code'] = 1;
	        	$data['msg'] = "注册成功！";
	        	$data['img_src'] = $url;
	        	$arr['uid'] = $_REQUEST['uid'];
	        	$arr['user_rank'] = 2;
	        	$arr['shop_id'] = $bool;
	        	$data['arr']=$arr;
	        	echo json_encode($data);
	        }else{
	        	$data['code'] = 2;
	        	$data['msg'] = "注册失败！";
	        	echo json_encode($data);
	        }
		}		
	}
	//配送区域列表
	public function lists(){
		$wxConfigArr = $this->wxConfig->select();
		$this->assign('wxConfigArr',$wxConfigArr);
		$this->display('wxConfig/list');
	}
	//添加页面
	public function add(){
		$id = I('get.id');
		if($id){
			$wxConfigArr = $this->wxConfig->find($id);
			$this->assign('wxConfigArr',$wxConfigArr);
		}
		$this->display('wxConfig/add');
	}
	//保存添加的配送区域
	public function saveAdd(){
		$data = I('post.');
		$data['addtime'] = time();
		// dump($data);
		// exit;
		if($data['id']){
			$bool = $this->wxConfig->save($data);
			if($bool){
				$this->success('修改成功',U('admin/wxconfig/lists'),1);
			}else{
				$this->error('修改失败',U('admin/wxconfig/lists'),1);
			}
			exit;
		}
		$bool = $this->wxConfig->add($data);
		if($bool){
			$this->success('添加成功',U('admin/wxconfig/lists'),1);
		}else{
			$this->error('添加失败',U('admin/wxconfig/lists'),1);
		}
	}
	//删除配送区域
	public function delete(){
		$id = I('get.id');
		$bool = $this->wxConfig->delete($id);
		if($bool){
			$this->success('删除成功',U('admin/wxconfig/lists'),1);
		}else{
			$this->error('删除失败',U('admin/wxconfig/lists'),1);
		}
	}
}
