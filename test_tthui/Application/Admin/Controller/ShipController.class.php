<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class ShipController extends BaseController
{
	private $ship = NULL;
	private $touchShip = NULL;
	public function _initializes(){
		!is_null($this->ship)?:$this->ship = D('ship');
		!is_null($this->touchShip)?:$this->touchShip = D('touchShip');
	}
	//配送区域列表
	public function lists(){
		//设置查询条件
		$where = array();
		$touch_id = 0;
		if(I('param.touch_id')){
			$where['app_touch_ship.touch_id'] = I('param.touch_id');
			$touch_id = I('param.touch_id');
		}
		// dump(I('param.touch_id'));
		$shipArr = $this->ship->field('app_ship.*,app_touch_ship.shipping_name')->join('app_touch_ship on app_touch_ship.touch_id=app_ship.touch_id','inner')->where($where)->select();
		//获取快递列表
		
		$touchShipArr = $this->touchShip->field('touch_id,shipping_name')->select();
		$this->assign('touchShipArr',$touchShipArr);
		$this->assign('shipArr',$shipArr);
		$this->assign('touch_id',$touch_id);
		$this->display('ship/list');
	}
	//添加页面
	public function add(){
		//获取快递列表	
		$touchShipArr = $this->touchShip->field('touch_id,shipping_name')->select();
		$this->assign('touchShipArr',$touchShipArr);
		$this->display('ship/add');
	}
	//保存添加的配送区域
	public function saveAdd(){
		$data = I('post.');
		$data['start'] = $data['start']?1:0;
		$bool = $this->ship->add($data);
		if($bool){
			$this->success('添加成功',U('admin/ship/lists'),1);
		}else{
			$this->error('添加失败',U('admin/ship/lists'),1);
		}
	}
	//编辑配送区域
	public function edit(){
		$id = I('get.ship_id');
		$shipArr = $this->ship->find($id);
		//获取快递列表	
		$touchShipArr = $this->touchShip->field('touch_id,shipping_name')->select();
		$this->assign('touchShipArr',$touchShipArr);
		$this->assign('shipArr',$shipArr);
		$this->display('ship/edit');
	}
	//保存编辑区域
	public function saveEdit(){
		$data = I('post.');
		$data['start'] = $data['start']?1:0;
		$bool = $this->ship->save($data);
		if($bool){
			$this->success('修改成功',U('admin/ship/lists'),1);
		}else{
			$this->error('修改失败',U('admin/ship/lists'),1);
		}
	}
	//删除配送区域
	public function delete(){
		$id = I('get.ship_id');
		$bool = $this->ship->delete($id);
		if($bool){
			$this->success('删除成功',U('admin/ship/lists'),1);
		}else{
			$this->error('删除失败',U('admin/ship/lists'),1);
		}
	}
	//是否启动
	public function change(){
		$data = I('post.');
//		dump($data);
//		exit;
		$data['start'] = $data['start']?0:1;
		$bool = $this->ship->save($data);
		if($bool){
			echo $data['start'];
		}else{
			echo '2';
		}
	}
}
