<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class TouchadController extends BaseController
{
	private $touchAd = NULL;
	public function _initializes(){
		!is_null($this->touchAd)?:$this->touchAd = D('touchAd');
	}
	//商品品牌列表
    public function lists(){
    	//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? : 1;
		$start = ($p - 1) * $showpage;
		$where = array();
		//判断高级搜索
		$data = I('post.') ? : I('get.');
		//判断订单状态跳转
		$status = I('get.');
		//计算指定条件下的订单页数
		$count = $this -> touchAd -> count();
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$adminlogArr = $this -> touchAd -> where($where) -> order('`order_by` DESC') ->limit($start, $showpage) -> select();

		
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
		$this -> display('touchAd/lists');
    }
    //添加或编辑商品品牌
	public function add(){
		$id = I('get.ad_id');
	    if ($id) {
	        // $sql = "select * from ecs_boss_classroom where id = {$id}";
	        $classArr = $this -> touchAd -> where('ad_id='.$id) -> find();
	        $this->assign('brand_list', $classArr);
	    }
	    // dump($classArr);
	    $this->display('touchAd/add');
	}
	//保存商品品牌
	public function saveAdd(){
		//获取touchAd对象
		$touchAd = $this->touchAd;
		//获取post数据
		$data = $_POST;
		// dump($_FILES);
		// exit;
		if ($_FILES['ad_code']['error'] == 0) {
	        $filePath = $_FILES['ad_code']['tmp_name'];
	        $suffix = $_FILES['ad_code']['name'];
	        $file = portal_qiniu($filePath, 'http://pbonwl969.bkt.clouddn.com', 'app-touch-ad', $suffix);
	        $ad_code = $file['key'];
	    }
	    if($ad_code){
	    	$data['ad_code'] = $ad_code;
	    }
		if($data['ad_id']){
			$bool = $touchAd->save($data);
			if ($bool) {
				if($data['img']){
					$this->delQiniu($data['img'], 'http://pbonwl969.bkt.clouddn.com', 'app-touch-ad');
				}
	        	$this->adminlog('修改一个商品广告位图片');
	            $this->success('修改成功',U('admin/touchad/lists'),1);
	        } else {
	            $this->error('修改失败',U('admin/touchad/lists'),1);
	        }
	        exit;
		}else{
			//保存品牌数据
			$bool = $touchAd->add($data);
			if ($bool) {
	        	$this->adminlog('添加一个商品广告位图片');
	            $this->success('添加成功',U('admin/touchad/lists'),1);
	        } else {
	            $this->error('添加失败',U('admin/touchad/lists'),1);
	        }
	        exit;
		}

	}

	//删除商品品牌
	public function remove(){
		//获取touchAd对象
		$touchAd = $this->touchAd;
		$data = I('param.');
		// dump($data);
		// $data['is_delete'] = 1;
		//删除指定品牌id数据
		$bool = $touchAd->where($data)->delete();
		if ($bool) {
			if($data['img']){
				$this->delQiniu($data['img'], 'http://pbonwl969.bkt.clouddn.com', 'app-touch-ad');
			}
        	$this->adminlog('删除一个商品广告位图片');
            $this->success('删除成功',U('admin/touchad/lists'),1);
        } else {
            $this->error('删除失败',U('admin/touchad/lists'),1);
        }
	}
	
}
