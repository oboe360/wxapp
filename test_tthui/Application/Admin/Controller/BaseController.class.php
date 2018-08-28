<?php
/*
本类属于基础类，奠定所有类基础，保证相同数据统一处理
 */
namespace Admin\Controller;
use Think\Controller;
abstract class BaseController extends Controller {
	public function _initialize() {
		//设置分页页数
        if(I('get.pageshop')){
           cookie('pageshop',I('get.pageshop')); 
        }elseif(!cookie('pageshop')){
           cookie('pageshop',15); 
        }
        //判断是否存在用户登录
        if(cookie('boss_user_id')){
            session('boss_user_id',cookie('boss_user_id'));
        }
        if(cookie('boss_user_name')){
            session('boss_user_name',cookie('boss_user_name'));
        }
        if(cookie('boss_nav_list')){
            session('boss_nav_list',cookie('boss_nav_list'));
        }
		if(!session('boss_user_id')){
            if(CONTROLLER_NAME == 'Goods'){
                echo json_encode(array('code'=>2,'msg'=>U('admin/login/login')));
                exit;
            }else{
                echo '<script type="text/javascript">parent.location="'.U('admin/login/login').'"</script>';
                exit;
            }
            
        }

		//控制器初始化
        if(method_exists($this,'_initializes'))
            $this->_initializes();
	}
	//设置管理员操作日志，记录添加，编辑，删除操作
    public function adminlog($action){
        $user_id = session('boss_user_id');
        $adminlog = D('AdminLog');
        $data = array();
        $data['user_id'] = $user_id;
        $data['log_info'] = $action;
        $data['ip_address'] = $_SERVER['HTTP_HOST'];
        $data['log_time'] = time();
        return $adminlog->add($data);
    }
    public function delQiniu($url, $qiniu_url = 'http://p2y8yvch3.bkt.clouddn.com', $bucket = 'goods-gallery')
    {
        if($url){
            $res = portal_delect(str_replace($qiniu_url.'/','',$url),$qiniu_url,$bucket, D('qiniuFile'));
            if($res){
                return false;
            }else{
                return true;
            }
        }else{
            return true;
        }
        // dump($res);
        // exit;
    }
}
