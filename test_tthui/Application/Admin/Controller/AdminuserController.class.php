<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class AdminuserController extends BaseController
{
	private $adminuser = NULL;
	private $adminaction = NULL;
	public function _initializes(){

		!is_null($this->adminuser)?:$this->adminuser = D('AdminUser');
		!is_null($this->adminaction)?:$this->adminaction = D('AdminAction');
	}
	//展示管理员页面
	public function lists(){
		//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? : 1;
		$start = ($p - 1) * $showpage;
		//判断高级搜索
		$data = I('post.') ? : I('get.');

		//计算指定条件下的订单页数
		$count = $this -> adminuser -> count();
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$adminlogArr = $this -> adminuser -> field('user_id,nav_list,user_name,password,add_time,last_login,last_ip') -> limit($start, $showpage) -> select();

		foreach($adminlogArr as $k=>$v){
			$adminlogArr[$k]['nav_list'] = $this->getAction($v['nav_list']);
		}
		$page = new \Think\Page($count, $showpage, $data);
		//展示分页
		$show = $page -> show();
		$this -> assign('show', $show);
		$this -> assign('data', $data);
		$this -> assign('list', $adminlogArr);
		$this -> display('adminuser/list');
	}
	//添加管理员
	public function add(){
		$list = $this->getPriv();
		if($list){
			foreach($list as $k=>$v){
				//获取对应的子级栏目
				$child = $this->getPriv($v['action_id']);
				$list[$k]['child'] = $child;
			}
			
		}
//		dump($list);
		$this->assign('list',$list);
		$this->display('adminuser/add');
	}
	//编辑管理员
	public function edit(){
		//获取栏目列表
		$list = $this->getPriv();
		if($list){
			foreach($list as $k=>$v){
				//获取对应的子级栏目
				$child = $this->getPriv($v['action_id']);
				$list[$k]['child'] = $child;
			}
			
		}
		//获取管理员信息
		$user_id = I('get.user_id');
		$adminlist = $this->adminuser->field('user_id,nav_list,user_name,password')->find($user_id);
		$nav_list = json_decode($adminlist['nav_list'],TRUE);
//		dump($nav_list);
//		exit;
		$this->assign('adminlist',$adminlist);
		$this->assign('nav_list',$nav_list);
		$this->assign('list',$list);
		$this->display('adminuser/edit');
	}
	//保存管理员
	public function saveadd(){
		$data = I('post.');
		//拼接权限字段
//		$str = '';
//		foreach($data['priv'] as $v){
//			$str .= $v.',';
//		}
		$data['nav_list'] = json_encode($data['priv']);
		$data['password'] = md5($data['password']);
		$data['add_time'] = time();
		$data['last_login'] = time();
		$data['last_ip'] = $_SERVER['HTTP_HOST'];
		$bool = $this->adminuser->add($data);
		if($bool){
			$this->adminlog('添加一位管理员');
			$this->success('添加成功',U('admin/adminuser/lists'),1);
		}else{
			$this->error('添加失败',U('admin/adminuser/lists'),1);
		}
	}
	//保存编辑管理员
	public function saveedit(){
		$data = I('post.');
		//拼接权限字段
//		$str = '';
//		foreach($data['priv'] as $v){
//			$str .= $v.',';
//		}
		$data['nav_list'] = json_encode($data['priv']);
		if($data['newpassword']){
			$data['password'] = md5($data['newpassword']);
		}
//		dump($data);
//		exit;
		$bool = $this->adminuser->save($data);
		if($bool){
			$this->adminlog('修改管理员信息');
			$this->success('编辑成功',U('admin/adminuser/lists'),1);
		}else{
			$this->error('编辑失败',U('admin/adminuser/lists'),1);
		}
	}
	//删除管理员
	public function delete(){
		$data = I('get.');
		$bool = $this->adminuser->where($data)->delete();
		if($bool){
			$this->adminlog('删除一位管理员 '.$data['user_id']);
			$this->success('删除成功',U('admin/adminuser/lists'),1);
		}else{
			$this->error('删除失败',U('admin/adminuser/lists'),1);
		}
	}
	//获取菜单栏目信息
	public function actionName(){
		$arr = $this->getAction(session('boss_nav_list'));
		foreach($arr as $k=>$v){
			$extension = pathinfo($v['action_code'], PATHINFO_EXTENSION);
			if(!$extension && $v['action_code']){
				$arr[$k]['action_code'] = dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/'.$v['action_code'];
			}
			foreach ($v['child'] as $key => $value) {
				// $extension1 = pathinfo($value['action_code'], PATHINFO_EXTENSION);
				// if(!$extension1 && $value['action_code']){

				$arr[$k]['child'][$key]['action_code'] = $_SERVER['REQUEST_SCHEME'].'://'.dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/'.$value['action_code'];
				// }
			}
		}
		return $arr;
//		dump($arr);
		echo json_encode($arr);
	}
	//获取个人信息
	
	//根据parent_id获取菜单栏目列表
	private function getPriv($parent_id=0){
		$list = $this->adminaction->field('action_id,action_name,action_code')->where('parent_id='.$parent_id)->order('`desc` desc')->select();
		return $list;
	}
	//根据id获取菜单栏目信息
	private function getShow($action_id){
		$list = $this->adminaction->field('action_id,action_name,action_code')->where('action_id='.$action_id)->order('`desc` desc')->find();
		return $list;
	}
	//将nav_list字段转化为对应的栏目名称和方法名称
	private function getAction($nav_list){
		if($nav_list == 'all'){
			//获取栏目列表
			$arr = $this->getPriv();
			if($arr){
				foreach($arr as $k=>$v){
					//获取对应的子级栏目
					$child = $this->getPriv($v['action_id']);
					$arr[$k]['child'] = $child;
				}
			}
		}else{
			$nav_list = json_decode($nav_list,TRUE);
			$arr = array();
			foreach($nav_list as $k=>$v){
				$list = $this->getShow($k);
				$str = '';
				foreach($v as $v1){
	//				$list['child'][] = $this->getShow($v1);
					$str .= $v1.',';
				}
				$str = trim($str,',');
				//获取父级分类下的权限子类
				$list['child'] = $this->adminaction->field('action_id,action_name,action_code')->where(array('action_id'=>array('in',$str)))->order('`desc` desc')->select();
				$arr[] = $list;
			}	
		}
		return $arr;
	}
}
