<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class WxconfigController extends BaseController
{
	private $wxConfig = NULL;
	public function _initializes(){
		!is_null($this->wxConfig)?:$this->wxConfig = D('wxConfig');
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
