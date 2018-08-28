<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class shoprankController extends BaseController
{
	private $shopRank = NULL;
	public function _initializes(){
		!is_null($this->shopRank)?:$this->shopRank = D('storeRank');
	}
	//配送区域列表
	public function lists(){
		$shopRankArr = $this->shopRank->select();
		foreach ($shopRankArr as $k => $v) {
			$shopRankArr[$k]['add_time'] = date("Y-m-d H:i:s",$v['add_time']);
		}
		$this->assign('shopRankArr',$shopRankArr);
		$this->display('shoprank/list');
	}
	//添加页面
	public function add(){
		$this->display('shoprank/add');
	}
	//保存添加的配送区域
	public function saveAdd(){
		$data = I('post.');
		//$shopRank-;
		$data['add_time'] =time();
		$bool = $this->shopRank->add($data);
		
		if($bool){
			$this->success('添加成功',U('admin/shoprank/lists'),1);
		}else{
			$this->error('添加失败',U('admin/shoprank/lists'),1);
		}
	}
	//编辑配送区域
	public function edit(){
		$id = I('get.rank_id');
		$shopRankArr = $this->shopRank->find($id);
		$this->assign('shopRankArr',$shopRankArr);
		$this->display('shoprank/edit');
	}
	//保存编辑区域
	public function saveEdit(){
		$data = I('post.');
		$data['add_time'] =time();
		
		$bool = $this->shopRank->save($data);
		if($bool){
			$this->success('修改成功',U('admin/shoprank/lists'),1);
		}else{
			$this->error('修改失败',U('admin/shoprank/lists'),1);
		}
	}
	//删除配送区域
	public function delete(){
		$id = I('get.rank_id');
		$bool = $this->shopRank->delete($id);
		if($bool){
			$this->success('删除成功',U('admin/shoprank/lists'),1);
		}else{
			$this->error('删除失败',U('admin/shoprsank/lists'),1);
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
