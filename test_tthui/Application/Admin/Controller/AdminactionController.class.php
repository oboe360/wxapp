<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class AdminactionController extends BaseController
{
	// private $adminlog = NULL;
	private $adminaction = NULL;
	public function _initializes(){
		// !is_null($this->adminlog)?:$this->adminlog = D('AdminLog');
		!is_null($this->adminaction)?:$this->adminaction = D('AdminAction');
	}
	//展示栏目页面
	public function lists(){
		//获取栏目列表
		$list = $this->getPriv();
		if($list){
			foreach($list as $k=>$v){
				//获取对应的子级栏目
				$child = $this->getPriv($v['action_id']);
				$list[$k]['child'] = $child;
			}
		}
		$this->assign('list',$list);
		$this->display('adminaction/list');
	}
	//添加栏目
	public function add(){
		$list = $this->getPriv();
//		dump($list);
		$this->assign('list',$list);
		$this->display('adminaction/add');
	}
	//编辑栏目
	public function edit(){
		//获取顶级栏目列表
		$list = $this->getPriv();
//		dump($list);
		//获取指定栏目列表
		$action_id = I('get.action_id');
		$adminlist = $this->getShow($action_id);
		$this->assign('list',$list);
		$this->assign('adminlist',$adminlist);
		$this->display('adminaction/edit');
	}
	//保存栏目
	public function saveadd(){
		$data = I('post.');
		//拼接权限字段
//		$str = '';
//		foreach($data['priv'] as $v){
//			$str .= $v.',';
//		}
		$bool = $this->adminaction->add($data);
		if($bool){
			$this->adminlog('添加一个栏目菜单');
			$this->success('添加成功',U('admin/adminaction/lists'),1);
		}else{
			$this->error('添加失败',U('admin/adminaction/lists'),1);
		}
	}
	//保存编辑栏目
	public function saveedit(){
		$data = I('post.');
		//拼接权限字段
//		$str = '';
//		foreach($data['priv'] as $v){
//			$str .= $v.',';
//		}
		$bool = $this->adminaction->save($data);
		if($bool){
			$this->adminlog('修改一个栏目菜单');
			$this->success('修改成功',U('admin/adminaction/lists'),1);
		}else{
			$this->error('修改失败',U('admin/adminaction/lists'),1);
		}
	}
	//删除栏目
	public function delete(){
		$data = I('get.');
		$list = $this->getPriv($data['action_id']);
		if($list){
			$this->error('请先删除子栏目',U('admin/adminaction/lists'),1);
		}else{
			$bool = $this->adminaction->where($data)->delete();
			if($bool){
				$this->adminlog('删除一个栏目菜单');
				$this->success('删除成功',U('admin/adminaction/lists'),1);
			}else{
				$this->error('删除失败',U('admin/adminaction/lists'),1);
			}
		}
		
	}
	//根据parent_id获取菜单栏目列表
	private function getPriv($parent_id=0){
		$list = $this->adminaction->where('parent_id='.$parent_id)->select();
		return $list;
	}
	//根据id获取菜单栏目信息
	private function getShow($action_id){
		$list = $this->adminaction->where('action_id='.$action_id)->find();
		return $list;
	}
}
