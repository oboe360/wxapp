<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Think\Controller;
use Admin\Model\AdminUser;
use Think\Verify;
class LoginController extends Controller {
	private $admin = NULL;
	public function _initialize() {
		!is_null($this -> admin) ?: $this -> admin = D('AdminUser');
	}
	//展示登录页
	public function login() {
		// echo md5('123');
		$this->display('login/index');
	}
	//生成验证码
	public function captcha() {
		$cf = array('imageH' => 40, 'imageW' => 140, 'fontSize' => 20, 'length' => 4, 'fontttf' => '4.ttf', );
		$verify = new Verify($cf);
		$verify -> entry();
	}

	//登录验证
	public function loginCheck() {
		//验证验证码
		$verify = new Verify();
		if (!$verify -> check(I('post.code'))) {
			echo 2;
			exit ;
		}
		//获取用户登录数据，并且验证
		$admin = $this -> admin;
		$data = I('post.');
		unset($data['online']);
		unset($data['code']);
		$data['password'] = md5($data['password']);
		$arr = $admin -> where($data) -> find();
		// dump($arr);
		// exit;
		if ($arr) {
			$admin->save(array('user_id'=>$arr['user_id'],'last_login'=>time(),'last_ip'=>$_SERVER['HTTP_HOST']));
			//将用户id存入session
			session('boss_user_id', $arr['user_id']);
			session('boss_user_name', $arr['user_name']);
			session('boss_nav_list', $arr['nav_list']);
			// $token = $this -> setToken();
			//判断是否设置记住我
			if (I('post.online')) {
				//将用户id存入cookie
				cookie('boss_user_id', $arr['user_id'], 864000);
				cookie('boss_user_name', $arr['user_name'], 864000);
				cookie('boss_nav_list', $arr['nav_list'], 864000);
			}
			//将token存入cookie文件
			// cookie('token', $token, 86400);
			echo 1;
		} else {
			echo 3;
		}
	}

	//退出系统
	public function logout() {
		// session('[destroy]');
		session('boss_user_id', null);
		session('boss_user_name', null);
		session('boss_nav_list', null);
		cookie('boss_user_id', null);
		cookie('boss_user_name', null);
		cookie('boss_nav_list', null);
	
		$this->redirect('/admin/login/login');
	}

}
