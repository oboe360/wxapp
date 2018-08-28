<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class ConfigController extends BaseController
{
	protected $config = NULL;
	public function _initializes(){
		!is_null($this->config)?:$this->config = D('config');
	}
	//配置列表
	public function lists(){
		$configArr = $this->config->select();
		$this->assign('configArr',$configArr);
		$this->display('config/list');
	}
	//添加配置
	public function add(){
		$id = I('get.id');
		if($id){
			$configArr = $this->config->find($id);
			$this->assign('configArr',$configArr);
		}
		$this->display('config/add');
	}
	//保存添加的配置
	public function saveAdd(){
		$data = I('post.');
		
		// dump($data);
		// exit;
		if($data['id']){
			$bool = $this->config->save($data);
			if($bool){
				$this->success('修改成功',U('admin/config/lists'),1);
			}else{
				$this->error('修改失败',U('admin/config/lists'),1);
			}
			exit;
		}
		$data['time'] = time();
		$bool = $this->config->add($data);
		if($bool){
			$this->success('添加成功',U('admin/config/lists'),1);
		}else{
			$this->error('添加失败',U('admin/config/lists'),1);
		}
	}
	//删除配置
	public function delete(){
		$id = I('get.id');
		$bool = $this->config->delete($id);
		if($bool){
			$this->success('删除成功',U('admin/config/lists'),1);
		}else{
			$this->error('删除失败',U('admin/config/lists'),1);
		}
	}
}
