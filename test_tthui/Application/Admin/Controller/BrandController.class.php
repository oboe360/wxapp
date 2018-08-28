<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class BrandController extends BaseController
{
	private $brand = NULL;
	public function _initializes(){
		!is_null($this->brand)?:$this->brand = D('brand');
	}
	//商品品牌列表
    public function lists(){
    	//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? : 1;
		$start = ($p - 1) * $showpage;
		$where = array('is_delete'=>0);
		//判断高级搜索
		$data = I('post.') ? : I('get.');
		//判断订单状态跳转
		$status = I('get.');
		//计算指定条件下的订单页数
		$count = $this -> brand -> count();
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$adminlogArr = $this -> brand -> where($where) -> limit($start, $showpage) -> select();

		
		$page = new \Think\Page($count, $showpage, $data);
		//展示分页
		$show = $page -> show();
		//		echo json_encode($adminlogArr,$page);
//				dump($data);
		//		echo strtotime($data['start']);
//				exit;
		$this -> assign('show', $show);
		$this -> assign('data', $data);
		$this -> assign('arr', $adminlogArr);
		$this -> display('brand/lists');
    }
    //添加或编辑商品品牌
	public function add(){
		$id = I('get.brand_id');
	    if ($id) {
	        // $sql = "select * from ecs_boss_classroom where id = {$id}";
	        $classArr = $this -> brand -> where('brand_id='.$id) -> find();
	        $this->assign('brand_list', $classArr);
	    }
	    // dump($classArr);
	    $this->display('brand/add');
	}
	//保存商品品牌
	public function saveAdd(){
		//获取brand对象
		$brand = $this->brand;
		//获取post数据
		$data = $_POST;
		// dump($_FILES);
		// exit;
		if ($_FILES['brand_logo']['error'] == 0) {
	        $filePath = $_FILES['brand_logo']['tmp_name'];
	        $suffix = $_FILES['brand_logo']['name'];
	        $file = portal_qiniu($filePath, 'http://p2f7dyl2h.bkt.clouddn.com', 'brand-logo-images', $suffix);
	        $brand_logo = $file['key'];
	    }
	    if($brand_logo){
	    	$data['brand_logo'] = $brand_logo;
	    }
		if($data['brand_id']){
			$bool = $brand->save($data);
			if ($bool) {
				if($data['img']){
					$this->delQiniu($data['img'], 'http://p2f7dyl2h.bkt.clouddn.com', 'brand-logo-images');
				}
	        	$this->adminlog('修改一个商品品牌');
	            $this->success('修改成功',U('admin/brand/lists'),1);
	        } else {
	            $this->error('修改失败',U('admin/brand/lists'),1);
	        }
	        exit;
		}else{
			//保存品牌数据
			$bool = $brand->add($data);
			if ($bool) {
	        	$this->adminlog('添加一个商品品牌');
	            $this->success('添加成功',U('admin/brand/lists'),1);
	        } else {
	            $this->error('添加失败',U('admin/brand/lists'),1);
	        }
	        exit;
		}

	}

	//删除商品品牌
	public function remove(){
		//获取brand对象
		$brand = $this->brand;
		$data = I('param.');
		$data['is_delete'] = 1;
		//删除指定品牌id数据
		$bool = $brand->save($data);
		if ($bool) {
			if($data['img']){
				$this->delQiniu($data['img'], 'http://p2f7dyl2h.bkt.clouddn.com', 'brand-logo-images');
			}
        	$this->adminlog('删除一个商品品牌');
            $this->success('删除成功',U('admin/brand/lists'),1);
        } else {
            $this->error('删除失败',U('admin/brand/lists'),1);
        }
	}

	
}
