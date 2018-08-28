<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
class OtherconfigController extends BaseController
{
	protected $otherConfig = NULL;
	private $dirname;//图片的网络路径
	public function _initializes(){
		!is_null($this->otherConfig)?:$this->otherConfig = D('otherConfig');
		$this->dirname = $_SERVER['REQUEST_SCHEME'].'://'.dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
	}
	//配置列表
	public function lists(){
		//获取查询类别
		$where = array('parent_id'=>I('param.parent_id')?:0);
		$otherConfigArr = $this->otherConfig->where($where)->select();
		foreach ($otherConfigArr as $k => $v) {
			if($v['img_path']){
				$otherConfigArr[$k]['img_path'] = json_decode($v['img_path'],true);
				foreach ($otherConfigArr[$k]['img_path'] as $key => $value) {
					$otherConfigArr[$k]['img_path'][$key] = $this->dirname.$value;
				}
				// dump($otherConfigArr[$k]['img_path']);
			}
		}
		$this->assign('otherConfigArr',$otherConfigArr);
		$this->assign('parent_id',I('param.parent_id'));
		$this->display('otherConfig/list');
	}
	//添加配置
	public function add(){
		$id = I('get.id');
		if($id){
			$otherConfigArr = $this->otherConfig->find($id);
			$otherConfigArr['img_path'] = json_decode($otherConfigArr['img_path'],true);
			foreach ($otherConfigArr['img_path'] as $key => $value) {
				$otherConfigArr['img_path'][$key] = $this->dirname.$value;
			}
			// dump($otherConfigArr);
			$this->assign('otherconfigArr',$otherConfigArr);
		}elseif(I('get.parent_id')){
			$this->assign('otherconfigArr',I('get.'));
		}
		$this->assign('url',I('get.url'));
		$this->display('otherConfig/add');
	}
	//配置营销方案
	public function config(){
		$data = I('param.');
		$list = $this->otherConfig->where($data)->select();
		$this->assign('list',$list);
		$this->display('otherConfig/config');
	}
	//保存营销方案的配置
	public function saveConfig(){
		$data = I('param.');
		//配置更新数据
		$save_data = array();
		$save_data['id'] = $data['id'];
		// dump($data);
		// exit;
		if(!isset($data['bind_shop']) || !isset($data['bind_sj_user'])){
			echo json_encode(array('code'=>0,'msg'=>'配置失败'));
			exit;
		}
		$save_data['value'] = json_encode(array('bind_shop'=>$data['bind_shop'],'bind_sj_user'=>$data['bind_sj_user']));
		$bool = $this->otherConfig->save($save_data);
		if($bool){
			echo json_encode(array('code'=>1,'msg'=>'配置成功'));
			// $this->success('修改成功','',1);
		}else{
			echo json_encode(array('code'=>0,'msg'=>'配置失败'));
		}
		exit;
		
	}
	//保存添加的配置
	public function saveAdd(){
		$data = I('param.');
		$app_static_img = $data['app_static_img'];
		//存在文件上传
		if($app_static_img){
			//图片格式
			$img_type = array(
				'png'=>'png',
				'jpe'=>'jpg',
				'jpg'=>'jpg',
				'gif'=>'gif',
				);
			//定义一个空数组存图片链接
			$img_path = array();
			
			$app_static_img = trim(htmlspecialchars_decode($app_static_img),'&');
			// echo $app_static_img;
			$app_static_img = explode('&', $app_static_img);
			// print_r($app_static_img[2]);
			// exit;
			foreach ($app_static_img as $v) {
				$img_name = $this->uploadOne($v);
				if($img_name){
					$img_path[] = $img_name;
				}
			}
			if($img_path){
				$data['img_path'] = json_encode($img_path);
			}
		}
		if($data['id']){
			//如果存在图片上传，删除原有图片
			if($img_path){
				$img_path_list = $this->otherConfig->field('img_path')->where(array('id'=>$data['id']))->find();
				$img_path_list['img_path'] = json_decode($img_path_list['img_path'],true);
				$dir = getcwd();
				foreach ($img_path_list['img_path'] as $v) {
					unlink($dir.$v);
				}
			}
			$bool = $this->otherConfig->save($data);
			if($bool){
				echo json_encode(array('code'=>1,'msg'=>'修改成功'));
				// $this->success('修改成功','',1);
			}else{
				echo json_encode(array('code'=>0,'msg'=>'修改失败'));
			}
			exit;
		}
		$data['time'] = time();
		$bool = $this->otherConfig->add($data);
		if($bool){
			echo json_encode(array('code'=>1,'msg'=>'添加成功'));
			// $this->success('修改成功','',1);
		}else{
			echo json_encode(array('code'=>0,'msg'=>'添加失败'));
		}
	}
	//保存图片
	function uploadOne($file)
	{
	    $base64_image_content = trim($file);
	    //正则匹配出图片的格式
	    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
	        $type = $result[2];//图片后缀
	 
	        $dateFile = '/Public/Home/distribution/';  //创建目录
	        $new_file = getcwd() . $dateFile;
	        if (!file_exists($new_file)) {
	            //检查是否有该文件夹，如果没有就创建，并给予最高权限
	            mkdir($new_file, 0700);
	        }
	 
	        $filename = mt_rand().time() . '_' . uniqid() . ".{$type}"; //文件名
	        $new_file = $new_file . $filename;
	         
	        //写入操作
	        if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
	            return $dateFile . $filename;  //返回文件名及路径
	        } else {
	            return false;
	        }
	    }
	}
	//删除配置
	public function delete(){
		$id = I('get.id');
		$bool = $this->otherConfig->where(array('_logic'=>'or','id'=>$id,'parent_id'=>$id))->delete();
		if($bool){
			$this->success('删除成功','',1);
		}else{
			$this->error('删除失败','',1);
		}
	}
}
