<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class TouchshipController extends BaseController
{
	private $touchShip = NULL;
	public function _initializes(){
		!is_null($this->touchShip)?:$this->touchShip = D('touchShip');
	}
	//配送区域列表
	public function lists(){
		$touchShipArr = $this->touchShip->select();
		$this->assign('touchShipArr',$touchShipArr);
		$this->display('touchShip/list');
	}
	//添加页面
	public function add(){
		$this->display('touchShip/add');
	}
	//保存添加的配送区域
	public function saveAdd(){
		$data = I('post.');
		$data['delivery'] = $data['delivery']?1:0;
		$bool = $this->touchShip->add($data);
		if($bool){
			$this->success('添加成功',U('admin/touchship/lists'),1);
		}else{
			$this->error('添加失败',U('admin/touchship/lists'),1);
		}
	}
	//编辑配送区域
	public function edit(){
		$id = I('get.touch_id');
		$touchShipArr = $this->touchShip->find($id);
		$this->assign('touchShipArr',$touchShipArr);
		$this->display('touchShip/edit');
	}
	//保存编辑区域
	public function saveEdit(){
		$data = I('post.');
		$data['delivery'] = $data['delivery']?1:0;
		$bool = $this->touchShip->save($data);
		if($bool){
			$this->success('修改成功',U('admin/touchship/lists'),1);
		}else{
			$this->error('修改失败',U('admin/touchship/lists'),1);
		}
	}
	//删除配送区域
	public function delete(){
		$id = I('get.touch_id');
		$bool = $this->touchShip->delete($id);
		if($bool){
			$this->success('删除成功',U('admin/touchship/lists'),1);
		}else{
			$this->error('删除失败',U('admin/touchship/lists'),1);
		}
	}
	//是否启动
	public function change(){
		$data = I('post.');
//		dump($data);
//		exit;
		$data['delivery'] = $data['delivery']?0:1;
		$bool = $this->touchShip->save($data);
		if($bool){
			echo $data['delivery'];
		}else{
			echo '2';
		}
	}
}
