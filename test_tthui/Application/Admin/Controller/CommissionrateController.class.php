<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class CommissionrateController extends BaseController {
	private $commissionRate = NULL;
	public function _initializes(){
		!is_null($this -> commissionRate) ? : $this -> commissionRate = D('commissionRate');
	}
	//获取用户列表
	public function lists() {
		//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? I('get.p') : 1;
		$start = ($p - 1) * $showpage;
		//判断高级搜索
		$data = array_merge(I('get.'),I('post.'));
		//设置查询条件
		$where = array();
		//时间条件
		$timewhere = array();
		//计算指定条件下的用户页数
		$count = $this -> commissionRate -> where($where) -> where($timewhere) -> count();
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$commissionRateArr = $this -> commissionRate -> where($where) -> where($timewhere) -> limit($start, $showpage) -> select();


		$page = new \Think\Page($count, $showpage, $data);
		//展示分页
		$show = $page -> show();
		$this -> assign('show', $show);
		$this -> assign('arr', $commissionRateArr);
		$this -> display('commissionRate/lists');
	}
	 //添加或编辑商品品牌
	public function add(){
		$id = I('get.rate_id');
	    if ($id) {
	        // $sql = "select * from ecs_boss_classroom where id = {$id}";
	        $classArr = $this -> commissionRate -> where('rate_id='.$id) -> find();
	        $this->assign('brand_list', $classArr);
	    }
	    // dump($classArr);
	    $this->display('commissionrate/add');
	}
	//保存商品品牌
	public function saveAdd(){
		//获取commissionRate对象
		$commissionRate = $this -> commissionRate;
		//获取post数据
		$data = $_POST;
		if($data['rate_id']){
			$bool = $commissionRate->save($data);
			if ($bool) {
	        	$this->adminlog('修改用户等级分佣');
	            $this->success('修改成功',U('admin/commissionrate/lists'),1);
	        } else {
	            $this->error('修改失败',U('admin/commissionrate/lists'),1);
	        }
	        exit;
		}else{
			//保存品牌数据
			$bool = $commissionRate->add($data);
			if ($bool) {
	        	$this->adminlog('添加一个用户等级分佣');
	            $this->success('添加成功',U('admin/commissionrate/lists'),1);
	        } else {
	            $this->error('添加失败',U('admin/commissionrate/lists'),1);
	        }
	        exit;
		}

	}

	//删除商品品牌
	public function remove(){
		//获取commissionRate对象
		$commissionRate = $this->commissionRate;
		$data = I('param.rate_id');
		//删除指定品牌id数据
		$bool = $commissionRate->delete($data);
		if ($bool) {
        	$this->adminlog('删除一个用户等级分佣');
            $this->success('删除成功',U('admin/commissionrate/lists'),1);
        } else {
            $this->error('删除失败',U('admin/commissionrate/lists'),1);
        }
	}

}
