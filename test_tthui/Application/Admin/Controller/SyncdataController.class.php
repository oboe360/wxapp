<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class SyncdataController extends BaseController
{
	private $syncData = NULL;
	public function _initializes(){
		!is_null($this->syncData)?:$this->syncData = D('syncData');
	}
	//配送区域列表
	public function lists(){
		$where = array('status'=>0);
		//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('get.p') ? : 1;
		$start = ($p - 1) * $showpage;
		//计算指定条件下的订单页数
		$count = $this -> syncData -> where($where) -> count();
		if(ceil($count/$showpage) < $p && $count/$showpage){
			$start = (ceil($count/$showpage) - 1) * $showpage;
		}
		//查询搜索数据
		$syncDataArr = $this -> syncData -> where($where) -> order('add_time DESC') ->limit($start, $showpage) -> select();

		
		$page = new \Think\Page($count, $showpage);
		//展示分页
		$show = $page -> show();
		$this->assign('syncDataArr',$syncDataArr);
		$this->assign('show',$show);
		$this->display('syncData/list');
	}
	
}
