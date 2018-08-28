<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
use Admin\Controller\HuanQiuMeiTaoApi;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
class GoodsController extends BaseController {
	private $goods = NULL;
	private $member = NULL;
	private $gallery = NULL;
	private $volume = NULL;
	private $goodslog = NULL;
	private $HuanQiuMeiTaoApi = NULL;
	private $qiniu = NULL;
	public function _initializes() {
		!is_null($this -> goods) ? : $this -> goods = D('goods');
		!is_null($this -> gallery) ? : $this -> gallery = D('goodsGallery');
//		!is_null($this -> volume) ? : $this -> volume = D('VolumePrice');
		// !is_null($this -> member) ? : $this -> member = D('MemberPrice');
		// !is_null($this -> goodslog) ? : $this -> goodslog = D('GoodsLog');
		!is_null($this -> qiniu) ? : $this -> qiniu = D('qiniuFile');
		/*//判断token是否存在或失效
		 * $token = $this->setToken();
		 * if($token != cookie('token') || cookie('user_id')){
		 * 		echo 'NoLogin';
		 * 		exit;
		 * }
		 * */
	}
	//商品列表
	public function lists() {
		//分页参数设置
		$showpage = cookie('pageshop')?:15;
		$p = I('post.p')?:1;
		$start = ($p-1)*$showpage;
		//获取商品表模型对象
		$goods = $this -> goods;
		//创建空数组，获取高级搜索对应条件
		$where = array('is_delete' => 0);
		$data = array_merge(I('get.'),I('post.'));
//		dump($data);
		unset($data['p']);
		//判断赋值,用于搜索操作
		if ($data) {
			!$data['cat_id'] ?: $where['cat_id'] = $data['cat_id'];
			!$data['brand_id'] ?: $where['brand_id'] = $data['brand_id'];
			if ($data['type']) {
				$data['type'] != 'is_best' ?: $where['is_best'] = 1;
				$data['type'] != 'is_new' ?: $where['is_new'] = 1;
				$data['type'] != 'is_hot' ?: $where['is_hot'] = 1;
				// !($data['type'] == 'is_on_sale') ? : $data['is_on_sale'] = 1;
			}
			!$data['suppliers_id'] ?: $where['suppliers_id'] = $data['suppliers_id'];
			if($data['is_on_sale'] != 'all'){
				$data['is_on_sale']==1 ? $where['is_on_sale'] = 1 : $where['is_on_sale'] = 0;
			}
			
			!$data['keywords'] ? : $where['keywords'] = array('like', '%' . $data['keywords'] . '%');
			!$data['goods_name'] ? : $where['goods_name'] = array('like', '%' . $data['goods_name'] . '%');
			!$data['goods_sn'] ? : $where['goods_sn'] = $data['goods_sn'];
			$data['goods_sn'] !== '0' ? : $where['goods_sn'] = $data['goods_sn'];
			// !$data['goods_key'] ? : $where['goods_key'] = array('like', '%' . $data['goods_key'] . '%');
			// $data['goods_key'] !== '0' ? : $where['goods_key'] = array('like', '%' . $data['goods_key'] . '%');
			// !$data['is_on_sale'] ? : $where['is_on_sale'] = $data['is_on_sale'];
		}

		$goodsArr = $goods -> field('*') -> order('add_time DESC') -> where($where)-> limit($start,$showpage) -> select();
		foreach ($goodsArr as $k => $v) {
			if($v['is_top'] == 0){
				$goodsArr[$k]['is_top'] = '无子级商品';
			}elseif($v['is_top'] == 1){
				$goodsArr[$k]['is_top'] = '顶级商品';
			}elseif($v['is_top'] == 0){
				$goodsArr[$k]['is_top'] = '子级商品';
			}
		}
		//$data['goods']=$goodsArr;
//		print_r($goodsArr);die;
		$count = $goods -> where($where) -> count();
//		$page = new \Think\Page($count,$showpage,$data);
		$page = ceil($count/$showpage);

		echo json_encode(array('code'=>1,'msg'=>array($goodsArr,$page,$count)));
	}

	//根据goods_key获取商品主图
	private function getZt($goods_id) {
		if($goods_id){
			$arr = $this -> gallery -> field('img_url as url,img_id, img_desc') -> where(array('goods_id'=>$goods_id,'is_delete'=>0)) -> order('img_desc DESC') -> select();
			if(!$arr){
				$arr = '';
			}
		}else{
			$arr = '';
		}
//		dump($arr);
//		$newArr = array();
//		foreach($arr as $v){
//			$newArr[]['url'] = $v['img_url'];
//		}
//		dump($newArr);
		return $arr;
	}

	//根据goods_id获取商品详情
	public function goodsdetail() {
		//获取商品表模型对象
		$goods = $this -> goods;
		//获取商品id
		$goods_id = I('param.goods_id');
		//获取指定商品详情
		$goodsArr = $goods -> where('is_delete=0') -> find($goods_id);
		if($goodsArr){
			// $goodsArr['user_price'] = $this -> getUserPrice($goodsArr['goods_key']);取消
	//		$goodsArr['volume'] = $this -> getVolume($goodsArr['goods_key']);
			$goodsArr['zutu'] = $this -> getZt($goodsArr['goods_id']);
		}
		
//		dump($goodsArr);
//		exit;
		// echo json_encode($goodsArr);
		echo json_encode(array('code'=>1,'msg'=>$goodsArr));
	}
	//获取token
 	public function token(){
 		/**
		 * 生成七牛  token
		 */
		// require 'php-sdk/autoload.php';
		
		$accessKey = C('ACCESS_KEY');
		$secretKey = C('SECRET_KEY');
		$bucket = $_GET['bucket'] ? $_GET['bucket'] : 'burst-images';
		$auth = new Auth($accessKey, $secretKey);
		// 生成上传Token
		$token = $auth->uploadToken($bucket);
		$data = array('uptoken' => $token);
		echo json_encode($data);
 	}
	//获取品牌，类型，供货商信息组
	public function getBra_Cat_Sup() {
		//获取品牌数据
		$brandArr = D('brand') -> where('is_delete=0') -> select();
		//获取类型数据
		$categoryArr = D('category')-> where('is_delete=0') -> select();
		//获取供货商数据
		// $suppliersArr = D('suppliers') -> select();
		//拼接成三维数组
		$codeArr = array('brand' => $brandArr, 'category' => $categoryArr);
		// echo json_encode($codeArr);
		echo json_encode(array('code'=>1,'msg'=>$codeArr));
	}

	//保存添加商品通用信息
	public function saveAdd() {
		if(isset($_POST['cuxiao'])){
			unset($_POST['cuxiao']);
		}
		if(isset($_POST['file'])){
			unset($_POST['file']);
		}
		if(isset($_POST['fileField'])){
			unset($_POST['fileField']);
		}
		
		//获取要保存的商品详情数据
		$data = $_POST;
		$src = $data['editorValue'];
		if(empty($data['goods_lianjie'])){
			unset($data['goods_lianjie']);
		}
		// file_put_contents('result.txt', json_encode($data));
		// dump($data['is_burst']);
		// exit;
		if(isset($data['is_edit']) && $data['is_edit'] == 1){
			if($data['editorValue']){
				$goods_desc = $this->saveDetail($src);
				$data['goods_desc'] = $goods_desc;
			}else{
				$data['goods_desc'] = '';
			}
//			echo $goods_desc;
		}

		//是否为爆品
		$data['is_burst'] = $data['is_burst'] == 'on' ? 1 : 0;
		//判断是否使用外部URL
		$goods_img = $data['goods_img_url'] ? $this -> file_exists_S3($data['goods_img_url']) : $data['goods_img'];
		// $goods_thumb = $data['goods_thumb_url'] ? $this -> file_exists_S3($data['goods_thumb_url']) : $data['goods_thumb'];
		$video_img = $data['video_img_url'] ? $this -> file_exists_S3($data['video_img_url']) : $data['video_img'];
		$hot_image = $data['hot_image'];
		$share_img = $data['share_img'];
		
		//删除不需要字段
		if(isset($data['goods_img_url'])){
			unset($data['goods_img_url']);
		}
		// if(isset($data['goods_thumb_url'])){
		// 	unset($data['goods_thumb_url']);
		// }
		if(isset($data['video_img_url'])){
			unset($data['video_img_url']);
		}

//		上传图片至七里牛
		if ($goods_img) {
//			file_put_contents('a1', $goods_img);
//			$url = $this->uploadImg($goods_img, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			$url = portal_qiniu($goods_img, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			$data['goods_img'] = $url['key'];
			// $data['original_img'] = $url['key'];
			@unlink($goods_img);
		}else{
			unset($data['goods_img']);
		}
// 		if ($goods_thumb) {
// //			file_put_contents('b1', $goods_thumb);
// //			$url = $this->uploadImg($goods_thumb, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
// 			$url = portal_qiniu($goods_thumb, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
// 			$data['goods_thumb'] = $url['key'].'?imageView2/0/w/180/h/180';
// 			@unlink($goods_thumb);
// 		}else{
// 			unset($data['goods_thumb']);
// 		}
		if ($video_img) {
//			file_put_contents('c1', $video_img);
//			$url = $this->uploadImg($video_img, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			$url = portal_qiniu($video_img, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			$data['video_img'] = $url['key'];
			@unlink($video_img);
		}else{
			unset($data['video_img']);
		}
		if ($hot_image) {
//			file_put_contents('d1', $hot_image);
//			$url = $this->uploadImg($hot_image, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			$url = portal_qiniu($hot_image, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			$data['hot_image'] = $url['key'];
			$data['is_burst'] = 1;
		}else{
			unset($data['hot_image']);
		}
		if ($share_img) {
//			file_put_contents('d1', $hot_image);
//			$url = $this->uploadImg($hot_image, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			$url = portal_qiniu($share_img, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			$data['share_img'] = $url['key'];
		}else{
			unset($data['share_img']);
		}
		//获取商品表模型对象
		$goods = $this -> goods;
		
//		dump($data);
//		exit;
		//判断是否为添加
		if ($data['goods_id']) {
			$goods_id = $data['goods_id'];
			$data['add_time'] = time();
//			$goods_key1 = reset($this->goods->field('goods_key')->find($goods_id));
			//保存商品通用信息至数据库
//			dump($data);
			$goodsList = $this->goods->field('goods_thumb,goods_img,video_img,hot_image,share_img,goods_desc,goods_lianjie')->where('goods_id='.$data['goods_id'])->find();
			if($data['is_edit']){
				//编辑操作删除商品详情图片
				$this->delGoodDesc($goodsList['goods_desc'], $data['goods_desc']);
			}
			// if($data['goods_thumb']){
			// 	//编辑操作删除商品详情图片
			// 	$this->delQiniu(strstr($goodsList['goods_thumb'],'?imageView2',true), 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			// }
			if($data['goods_img']){
				//编辑操作删除商品详情图片
				$this->delQiniu($goodsList['goods_img'], 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
				$data['goods_thumb'] = $data['goods_img'].'?imageView2/0/w/200/h/200';
			}
			if($data['video_img']){
				//编辑操作删除商品详情图片
				$this->delQiniu($goodsList['video_img'], 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			}
			if($data['hot_image']){
				//编辑操作删除商品详情图片
				$this->delQiniu($goodsList['hot_image'], 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			}
			if($data['share_img']){
				//编辑操作删除商品详情图片
				$this->delQiniu($goodsList['share_img'], 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
			}
			if($data['goods_lianjie']){
				//编辑操作删除商品视频
				$this->delQiniu($goodsList['goods_lianjie'], 'http://p2y9lnq1a.bkt.clouddn.com', 'goods-video');
			}
			$bool = $goods -> save($data);
			$goods_key = NULL;
			//保存商品主图
			$urlArr = $this -> saveZt($data['zutu'], $data['img_desc'], $goods_id, $goods_key);
			$data['zutu'] = $urlArr;
			//写入操作日志
			$action = 'update';
			// $this->logs($goods_id, $data, $action);
			$this->adminlog('编辑一个商品');
		} else {
			$data['add_time'] = time();
			// $data['last_update'] = time();
			$data['is_new'] = 1;
			$data['goods_sn'] = $data['goods_sn']?:'zt'.time();
			if($data['goods_img']){
				$data['goods_thumb'] = $data['goods_img'].'?imageView2/0/w/200/h/200';
			}
			
			//保存商品通用信息至数据库
			//		dump($data);
			//		exit;
			$bool = $goods -> add($data);

			$goods_id = $bool;
			$goods_key = 'zt'.$goods_id;
			// $data['goods_key'] = $goods_key;
			//添加唯一标识
			// $goods -> save(array('goods_key'=>$goods_key,'goods_id'=>$goods_id));
			//保存商品主图
			$urlArr = $this -> saveZt($data['zutu'], $data['img_desc'], $goods_id, $goods_key);
			$data['zutu'] = $urlArr;
			//写入操作日志
			$action = 'add';
			// $this->logs($goods_id, $data, $action);
			$this->adminlog('添加一个商品');
		}

		if ($bool) {
			//保存会员价格表
			// $this -> saveMember($data['user_price'], $goods_id, $goods_key);取消
			echo json_encode(array('code'=>1,'msg'=>1));
		} else {
			echo json_encode(array('code'=>0,'msg'=>0));
		}
	}

	//保存商品详情描述(图片)
	private function saveDetail($src, $goods_id) {
		//获取要保存的商品数据
		$data = $_POST;
		$goods_desc = '';
		//匹配对应的详情图片链接
		$num = preg_match_all('/\/\w+\/\w+\/\w+\/\w+\/\d+\/\d+\.[a-z]*/', $src, $arr);
		// dump($arr);
		// exit;
		if ($num) {
			//获取当前文件根目录的父目录
			$dir = $_SERVER['DOCUMENT_ROOT'];
			//		//循环将详情图片上传七牛云
			foreach (reset($arr) as $v) {
				$url = portal_qiniu($dir . $v, 'http://p2y8xxzv6.bkt.clouddn.com', 'goods-desc');
				// dump($url);
				// exit;
				// file_put_contents('img.txt',$dir . $v);
				//删除本地图片
				unlink($dir . $v);
				//echo $v;
				//替换详情图片链接
				$src = str_replace($v, $url['key'], $src);
				//			echo $url['key'];
			}
//			$data['goods_desc'] = $src;
			
		}
		return $src;

	}

	//保存指定商品通用信息的修改
	public function saveEdit() {
		$data = I('post.')?:I('get.');
		$arr = I('post.')?:I('get.');
		$newArr = array();
		unset($arr['goods_id']);
		foreach($arr as $k=>$v){
			$statu = $v ? 0 : 1;
			$data[$k] = $statu;
			$newArr['key'] = $k;
			$newArr['value'] = $statu;
//			$sql = "update ecs_goods set {$k}={$statu} where goods_id={$data['goods_id']}";
		}
//		$data['goods_id'] = intval($data['goods_id']);
		//获取商品表模型对象
		$goods = $this -> goods;
		// $data['last_update'] = time();
		if($goods->save($data)){
//			$goods_key = reset($this->goods->field('goods_key')->find($goods_id));
			// echo json_encode($newArr);
			echo json_encode(array('code'=>1,'msg'=>$newArr));
			//写入操作日志
			// $this->logs($data['goods_id'], $data);
			$this->adminlog('编辑一个商品');
		}else{
			echo json_encode(array('code'=>0,'msg'=>0));
		}
	}

	
	//删除指定商品
	public function delete() {
		//获取商品表模型对象
		$goods = $this -> goods;
		//获取商品id
		@$goods_id = I('post.goods_id');
//		$goods_id = array(4,5,6);
		if($goods_id){
			$data = array('goods_id' => $goods_id);
			//获取商品图片链接。删除对应七牛云图片
			$img_list = $goods->field('goods_thumb,goods_img,video_img,hot_image,share_img,goods_desc,goods_lianjie')->where($data)->find();
			// dump($img_list);
			// exit;
			// 删除商品详情图片
			$this->delGoodDesc($img_list['goods_desc']);
			unset($img_list['goods_desc']);
			//删除商品相关图片
			foreach ($img_list as $k=>$v) {
				if($k == 'goods_thumb'){
					// $v = strstr($v,'?imageView2',true);
					continue;
				}elseif($k == 'goods_lianjie'){
					if($v){
						//编辑操作删除商品视频
						$this->delQiniu($v, 'http://p2y9lnq1a.bkt.clouddn.com', 'goods-video');
					}
				}else{
					if($v){
						$this->delQiniu($v, 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
					}
				}
				
			}
			//删除商品轮番图
			$arr = $this->gallery->field('img_url')->where('goods_id='.$goods_id)->select();
			foreach ($arr as $v) {
				if($v['img_url']){
					$this->delQiniu($v['img_url']);
				}
			}
			//删除商品
			$goods->startTrans();
			//写入操作日志
			// $this->logs($goods_id, $data);
			$bool = $goods -> where($data) -> save(array('is_delete'=>1,'last_update'=>time()));
			$bool1 = $this->gallery -> where($data) -> save(array('is_delete'=>1,'add_time'=>time()));
			if($bool){
				echo json_encode(array('code'=>1,'msg'=>1));
				$this->adminlog('删除一个商品');
				$goods->commit();
			}else{
				echo json_encode(array('code'=>0,'msg'=>0));
				$goods->rollback();
			}	
			
		}
	}

	//保存会员价格至会员表
// 	private function saveMember($arr, $goods_id, $goods_key) {
// 		$userArr = array();
// 		foreach ($arr as $key => $v) {
// 			switch ($key) {
// 				case 0 :
// 					$this -> joinMember(1, $v, $goods_id, $goods_key);
// 					break;
// 				case 1 :
// 					$this -> joinMember(101, $v, $goods_id, $goods_key);
// 					break;
// 				case 2 :
// 					$this -> joinMember(102, $v, $goods_id, $goods_key);
// 					break;
// 				case 3 :
// 					$this -> joinMember(103, $v, $goods_id, $goods_key);
// 					break;
// 				case 4 :
// 					$this -> joinMember(104, $v, $goods_id, $goods_key);
// 					break;
// //				case 5 :
// //					$this -> joinMember(104, $v, $goods_id, $goods_key);
// //					break;
// 			}
// 		}
// 	}取消

	//拼接符合会员表的关联数组并保存
	// private function joinMember($user_rank, $user_price, $goods_id, $goods_key) {
	// 	$array = array('user_rank' => $user_rank, 'user_price' => $user_price, 'goods_id' => $goods_id);
		
	// 	if($goods_key == ''){
	// 		$arr = $this->member->field('price_id')->where(array('goods_id'=>$goods_id,'user_rank'=>$user_rank))->select();
	// 		foreach($arr as $v){
	// 			$this->member->where($v)->save($array);
	// 		}
	// 	}else{
	// 		$array['goods_key'] = $goods_key;
	// 		$bool = $this -> member -> add($array);
	// 		return $bool;
	// 	}
		
	// }取消

	//获取优惠数量并保存至对应表
	// private function saveVolume($volume_number, $volume_price, $goods_id, $goods_key) {
	// 	$array = array('volume_number' => $volume_number, 'volume_price' => $volume_price, 'goods_id' => $goods_id, 'price_type'=>1);
	// 	if($goods_key == ''){
	// 		$bool = $this -> volume -> save($array);
	// 	}else{
	// 		$array['goods_key'] = $goods_key;
	// 		$bool = $this -> volume -> add($array);
	// 	}
	// 	return $bool;
	// }取消

	//处理URL图片
	private function file_exists_S3($url) {
		$url = str_replace('https://', 'http://', $url);
		$state = @file_get_contents($url, 0, null, 0, 1);
		//获取网络资源的字符内容
		if ($state) {
			$filename = mt_rand(10, 1000000000) . date("dMYHis") . '.jpg';
			//文件名称生成
			ob_start();
			//打开输出
			readfile($url);
			//输出图片文件
			$img = ob_get_contents();
			//得到浏览器输出
			ob_end_clean();
			//清除输出并关闭
			$size = strlen($img);
			//得到图片大小
			$fp2 = @fopen($filename, "a");
			fwrite($fp2, $img);
			//向当前目录写入图片文件，并重新命名
			fclose($fp2);
			return $filename;
		} else {
			return 0;
		}
	}

	//保存处理商品主图
	private function saveZt($str, $img_desc, $goods_id, $goods_key) {
		$str = str_replace(',d', ';dd', $str);
		$arr = explode(';d', $str);
		$data = array();
		$urlArr = array();
		//循环将详情图片上传七牛云,并将图片链接存至数据表
		if ($str) {
			//判断是否存在排序
			if($img_desc){
				$img_desc = explode('-', $img_desc);
			}
			foreach ($arr as $k=>$v) {
				$url = portal_qiniu($v, 'http://p2y8yvch3.bkt.clouddn.com', 'goods-gallery');
				$data['img_url'] = $url['key'];
				$data['thumb_url'] = $url['key'].'?imageView2/0/w/180/h/180';
				$data['img_original'] = $url['key'];
				$urlArr[] = $url['key'];
				$data['goods_id'] = $goods_id;
				$data['add_time'] = time();
				//添加排序
				$data['img_desc'] = isset($img_desc[$k])? $img_desc[$k] : 0;
				if($goods_key == ''){
					$arr = $this->goods->field('goods_key')->find($goods_id);
					$goods_key = $arr['goods_key'];
				}
				$data['goods_key'] = $goods_key;
				$bool = $this -> gallery -> add($data);
				// $img_key = 'zt'.$bool;
				// $bool1 = $this -> gallery -> save(array('img_id'=>$bool,'img_key'=>$img_key));
			}
			if ($bool) {
				return $urlArr;
			} else {
				return 0;
			}
		}
	}
	//删除指定商品主图
	public function delZt(){
		$data = I('post.');
		$arr = $this->gallery->field('img_url,goods_id')->where('img_id='.$data['img_id'])->find();
		$this->gallery->startTrans();
		$bool = $this->gallery->where('img_id='.$data['img_id'])->save(array('is_delete'=>1,'add_time'=>time()));
		// dump($arr);
		$bool1 = $this->delQiniu($arr['img_url']);
		// dump($bool);
		// dump($bool1);
		if($bool && $bool1){
			$this->gallery->commit();
			echo json_encode(array('code'=>1,'msg'=>1));
			//写入操作日志
			$this->adminlog('删除一个商品主图');
			// $this->logs($arr['goods_id'], $data, $action);
		}else{
			$this->gallery->rollback();
			echo json_encode(array('code'=>0,'msg'=>0));
		}
	}
	//删除指定商品图片
	public function delGoodImg(){
		$data = I('post.');
		$this->goods->startTrans();
		if($data['field'] == 'goods_thumb'){
			// dump($data);
			$data['url'] = stristr($data['url'],'?imageView2',true);
		}
		$bool = $this->goods->where('goods_id='.$data['goods_id'])->save(array($data['field']=>'','last_update'=>time()));
		
		$bool1 = $this->delQiniu($data['url'], 'http://p2f73r77x.bkt.clouddn.com', 'goods-images');
		if($bool && $bool1){
			$this->goods->commit();
			echo json_encode(array('code'=>1,'msg'=>1));
			//写入操作日志
			// $this->logs($data['goods_id'], $data, $action);
			$this->adminlog('删除商品图片！');
		}else{
			$this->goods->rollback();
			echo json_encode(array('code'=>0,'msg'=>0));
		}
	}
	private function logs($goods_id, $data, $action = ''){
		$goods_key = reset($this->goods->field('goods_key')->find($goods_id));
		$action = $action?:ACTION_NAME;
		$arr = array('goods_key'=>$goods_key,'log_time'=>time(),'data'=>json_encode($data),'action'=>$action);
		$this->goodslog->add($arr);
	}
	// private function delQiniu($url, $qiniu_url = 'http://p2y8yvch3.bkt.clouddn.com', $bucket = 'goods-gallery')
	// {
	// 	if($url){
	// 		$res = portal_delect(str_replace($qiniu_url.'/','',$url),$qiniu_url,$bucket, $this -> qiniu);
	// 		if($res){
	// 			return false;
	// 		}else{
	// 			return true;
	// 		}
	// 	}else{
	// 		return true;
	// 	}
	// 	// dump($res);
	// 	// exit;
	// }
	//删除商品详情图片
	private function delGoodDesc($goods, $goods_desc = ''){
		$img_list = '';
		$number = '';
		if($goods_desc){
			//获取七牛云图片链接
			$number = preg_match_all('/com\/\d+\.jpg/', $goods_desc, $img_list);
		}
		// dump($img_list[0]);
		// // dump($goods_desc['goods_desc']);
		// exit;
		if($goods){
			//替换出对应的七牛云链接
			$num = preg_match_all('/com\/\d+\.jpg/', $goods, $arr);
			if($number){
				$arr[0] = array_diff($arr[0], $img_list[0]);
				// dump($arr[0]);
				// dump($img_list[0]);
				// die;
			}
			if($num){
				foreach ($arr[0] as $v) {
					portal_delect(str_replace('com/','',$v), 'http://p2y8xxzv6.bkt.clouddn.com', 'goods-desc', $this -> qiniu);
					
				}
			}
		}
	}
	//同步商品信息数据
	public function goodsData(){
		$act = 'goodsData';
	    $user = new HuanQiuMeiTaoApi();
	    //拼接请求参数u
	    $data = array('act'=>$act);
	    if(I('param.key')){
	        $data['key'] = I('param.key');
	    }
	    if(I('param.value')){
	        $data['value'] = I('param.value');
	    }
	    //请求接口
	    // $url = 'http://192.168.232.1/hqmt_tpadmin/home/material/'.$act;
	    //发送请求，获取结果
	    $res = $user->curlData($data);
	    // dump($res);
	    // 格式化数据结果
	    $result = json_decode($res,true);
	    // dump($result);
	    // exit;
	    if($result && $result['error'] == 0){
	        
	        $list = $result['msg'];
	        //如果存在prent_id。最后更新
	        $prent_list = array();
	        //循环插入数据
	        foreach ($list as $v) {
	            // dump($v['prent_id']);
	            if($v['prent_id'] == '0'){
	                $bool = $this->goodsSync($v);
	                if(!$bool){
	                    $this->errordata('app_goods', 'goods_key', $v['goods_key'], __ACTION__);
	                }
	            }else{
	                // dump($v['prent_id']);
	                // exit;
	                $prent_list[] = $v;
	            }
	            
	        }
	        // dump($prent_list);
	        // exit;
	        if($prent_list){
	            foreach ($prent_list as $v) {
	                // dump($v);
	                // 转换prent_id
	                $list = $this->field('prent_id')->where(array('goods_key'=>$v['prent_id']))->find();
	                // echo $sql;
	                // exit;
	                $v['prent_id'] = $list['prent_id'];
	                $bool = $this->goodsSync($v);
	                if(!$bool){
	                    $this->errordata('app_goods', 'goods_key', $v[goods_key], __ACTION__);
	                }
	            }
	        }
	        // dump($prent_list);
	        $data = array('code'=>1,'msg'=>'数据同步成功！');
	        if(I('param.id')){
	           $syncData = D('syncData');
	           $syncData->startTrans();
	           $row = $syncData->save(array('status'=>1,'id'=>I('param.id')));
	           if($row){
	             $syncData->commit(); 
	           }else{
	             $syncData->rollback();
	           }
	           
	        }
	        echo json_encode($data);
	    }else{  
	        $data = array('code'=>0,'msg'=>$result['msg']);
	        echo json_encode($data);
	    }
	}
	//同步商品库存
	public function goodsKucun(){
		$act = 'goodsKucun';
	    $user = new HuanQiuMeiTaoApi();
	    //拼接请求参数u
	    $data = array('act'=>$act);
	    if(I('param.key')){
	        $data['key'] = I('param.key');
	    }
	    if(I('param.value')){
	        $data['value'] = I('param.value');
	    }
	    //请求接口
	    // $url = 'http://192.168.232.1/hqmt_tpadmin/home/material/'.$act;
	    //发送请求，获取结果
	    $res = $user->curlData($data);
	    // dump($res);
	    // 格式化数据结果
	    $result = json_decode($res,true);
	    // dump($res);
	    // exit;
	    if($result['error'] == 0){
	        
	        $list = $result['msg'];
	        //循环插入数据
	        foreach ($list as $v) {
	            //判断是否存在某个商品
	            $list = $this->goods->field('goods_id')->where(array('goods_key'=>$v['goods_key']))->find();
	            $goods_id = $list['goods_id'];
	            // dump($goods_id);
	            // exit;
	            $this->goods->startTrans();
	            if($goods_id){
	                $row = $this->goods->where(array('goods_id'=>$goods_id))->setInc('goods_number',$v['goods_kucun']);
	                if(!$row){
	                    $this->goods->rollback();
	                    $this->errordata('app_goods', 'goods_key', $v[goods_key], __ACTION__);
	                }else{
	                    $this->goods->commit();
	                }
	            }else{
	                //拼接请求参数u
	                $param = array('act'=>'goodsData','key'=>'goods_key','value'=>$v['goods_key']);
	                
	                //请求接口
	                // $url = 'http://192.168.232.1/hqmt_tpadmin/home/material/goodsData';
	                //发送请求，获取结果
	                $res = $user->curlData($param);
	                // dump($res);
	                // 格式化数据结果
	                $goods_list = json_decode($res,true);
	                // dump($goods_list);
	                // exit;
	                if($goods_list && $goods_list['error'] == 0){
	                    if($goods_list['msg'][0]['prent_id'] != 0){
	                       
	                        $list = $this->goods->field('goods_id')->where(array('goods_key'=>$goods_list['msg'][0]['prent_id']))->find();
	                        $goods_list['msg'][0]['prent_id'] = $list['goods_id'];
	                    }
	                    $bool = $this->goodsSync($goods_list['msg'][0], $v['goods_kucun']);
	                    if(!$bool){
	                       $this->errordata('app_goods', 'goods_key', $v[goods_key], __ACTION__); 
	                    }
	                }else{
	                    // errordata('ecs_goods', 'goods_key', $v[goods_key], 'goods.php?act='.$act, $db); 
	                }
	                
	            }
	            
	        }
	        // dump($prent_list);
	        $data = array('code'=>1,'msg'=>'数据同步成功！');
	        if(I('param.id')){
	           $syncData = D('syncData');
	           $syncData->startTrans();
	           $row = $syncData->save(array('status'=>1,'id'=>I('param.id')));
	           if($row){
	             $syncData->commit(); 
	           }else{
	             $syncData->rollback();
	           }
	           
	        }
	        echo json_encode($data);
	    }else{  
	        $data = array('code'=>0,'msg'=>$result['msg']);
	        echo json_encode($data);
	    }
	}
	//商品同步
	//$v,以为数组，单个商品信息
	private function goodsSync($v, $goods_kucun = 0){
	    $this->goods->startTrans();
	    // unset($v['m_id']);
	    // dump($v);
	    // exit;
	    //查询商品在本地的goods_id
	    $list = $this->goods->field('goods_id')->where(array('goods_key'=>$v['goods_key']))->find();
	    $goods_id = $list['goods_id'];
	    $v['goods_kucun']['goods_kucun'] = $v['goods_kucun']?$v['goods_kucun']['goods_kucun']:$goods_kucun;
	    //如果goods_id不存在，则添加
	    if($goods_id){
	        //执行更新操作
	        
	        // $sql = "UPDATE `app_goods` SET `goods_name`='{$v[goods_name]}',`goods_lianjie`='{$v[goods_lianjie]}',`is_delete`='{$v[is_delete]}',`goods_guige`='{$v[goods_guige]}',`is_top`='{$v[is_top]}',`goods_diqu`='{$v[goods_diqu]}',`goods_weight`='{$v[goods_weight]}',`shop_price`='{$v[shop_price]}',`goods_td`='{$v[goods_td]}',`goods_sn`='{$v[goods_sn]}',`goods_img`='{$v[goods_img]}',`market_price`='{$v[market_price]}',`video_img`='{$v[video_img]}',`goods_thumb`='{$v[goods_thumb]}',`hot_image`='{$v[hot_image]}',`is_burst`='{$v[is_burst]}',`goods_desc`='{$v[goods_desc]}',`prent_id`='{$v[prent_id]}',`name`='{$v[name]}',`keywords`='{$v[keywords]}',`goods_number`=goods_number+".$v['goods_kucun']['goods_kucun']." WHERE `goods_id` = {$goods_id}";
	        $data = array('goods_name'=>$v['goods_name'],'goods_lianjie'=>$v['goods_lianjie'],'is_delete'=>$v['is_delete'],'goods_guige'=>$v['goods_guige'],'is_top'=>$v['is_top'],'goods_diqu'=>$v['goods_diqu']?:'','goods_weight'=>$v['goods_weight'],'shop_price'=>$v['shop_price'],'goods_brief'=>$v['goods_brief'],'goods_td'=>$v['goods_td'],'goods_sn'=>$v['goods_sn'],'goods_img'=>$v['goods_img'],'market_price'=>$v['market_price'],'video_img'=>$v['video_img'],'goods_thumb'=>$v['goods_thumb'],'hot_image'=>$v['hot_image'],'is_burst'=>$v['is_burst'],'goods_desc'=>$v['goods_desc'],'prent_id'=>$v['prent_id'],'name'=>$v['name'],'keywords'=>$v['keywords'],'share_img'=>$v['share_img'],'goods_id'=>$goods_id,'add_time'=>time(),'goods_number'=>array('exp','goods_number+'.$v['goods_kucun']['goods_kucun']));
	        $row = $this->goods->save($data);
	        
	    }else{
	        // $sql = "INSERT INTO `app_goods` (`goods_name`,`is_top`,`goods_key`,`prent_id`,`name`,`goods_lianjie`,`goods_guige`,`goods_diqu`,`goods_weight`,`shop_price`,`goods_td`,`is_burst`,`goods_sn`,`goods_img`,`market_price`,`video_img`,`goods_thumb`,`hot_image`,`goods_desc`,`add_time`,`keywords`,`is_new`,`is_on_sale`,`goods_number`,`is_delete`) VALUES ('{$v[goods_name]}','{$v[is_top]}','{$v[goods_key]}','{$v[prent_id]}','{$v[name]}','{$v[goods_lianjie]}','{$v[goods_guige]}','{$v[goods_diqu]}','{$v[goods_weight]}','{$v[shop_price]}','{$v[goods_td]}','{$v[is_burst]}','{$v[goods_sn]}','{$v[goods_img]}','{$v[market_price]}','{$v[video_img]}','{$v[goods_thumb]}','{$v[hot_image]}','{$v[goods_desc]}','".time()."','".{$v[keywords]}."',1,0,".$v['goods_kucun']['goods_kucun'].",".$v['is_delete'].")";
	        $data = array('goods_name'=>$v['goods_name'],'goods_lianjie'=>$v['goods_lianjie'],'is_delete'=>$v['is_delete'],'goods_guige'=>$v['goods_guige'],'is_top'=>$v['is_top'],'goods_diqu'=>$v['goods_diqu']?:'','goods_weight'=>$v['goods_weight'],'shop_price'=>$v['shop_price'],'goods_brief'=>$v['goods_brief'],'goods_td'=>$v['goods_td'],'goods_sn'=>$v['goods_sn'],'goods_img'=>$v['goods_img'],'market_price'=>$v['market_price'],'video_img'=>$v['video_img'],'goods_thumb'=>$v['goods_thumb'],'hot_image'=>$v['hot_image'],'is_burst'=>$v['is_burst'],'goods_desc'=>$v['goods_desc'],'prent_id'=>$v['prent_id'],'name'=>$v['name'],'goods_key'=>$v['goods_key'],'keywords'=>$v['keywords'],'share_img'=>$v['share_img'],'add_time'=>time(),'goods_number'=>$v['goods_kucun']['goods_kucun']);
	        $goods_id = $this->goods->add($data);
	        if($goods_id){
	        	$row = true;
	        }else{
	        	$row = false;
	        }

	    }
	    //判断是否有轮播图
	    if($v['goods_gallery']){
	        foreach ($v['goods_gallery'] as $key => $value) {
	            //判断是否存在轮播图数据
	            $list = $this->gallery->field('img_id')->where(array('img_key'=>$value['img_key']))->find();
	            $img_id = $list['img_id'];
	            if($img_id){
	                // $sql = "UPDATE `ecs_goods_gallery` SET `goods_id`='{$goods_id}',`goods_key`='{$v[goods_key]}',`img_url`='{$value[img_url]}',`is_delete`='{$value[is_delete]}',`thumb_url`='{$value[thumb_url]}',`img_key`='{$value[img_key]}',`img_original`='{$value[img_original]}',`add_time`='".time()."' WHERE `img_id` = {$img_id}";
	                $data = array('goods_id'=>$goods_id,'goods_key'=>$value['goods_key'],'img_url'=>$value['img_url'],'is_delete'=>$value['is_delete'],'img_key'=>$value['img_key'],'img_desc'=>$value['img_desc'],'add_time'=>time(),'img_id'=>$img_id);
	                $row1 = $this->gallery->save($data);
	            }else{
	                // $sql = "INSERT INTO `ecs_goods_gallery` (`img_url`,`goods_id`,`add_time`,`goods_key`,`img_key`,`is_delete`,`thumb_url`,`img_original`) VALUES ('$value[img_url]','{$goods_id}','".time()."','{$v[goods_key]}','{$value[img_key]}','{$value[is_delete]}','{$value[thumb_url]}','{$value[img_original]}')";
	                $data = array('goods_id'=>$goods_id,'goods_key'=>$value['goods_key'],'img_url'=>$value['img_url'],'is_delete'=>$value['is_delete'],'img_key'=>$value['img_key'],'img_desc'=>$value['img_desc'],'add_time'=>time());
	                $row1 = $this->gallery->add($data);
	            }
	            if(!$row1){
	                break;
	            }
	        }
	        // dump($row);
	        // dump($row1);
	        // exit;
	        if(!$row || !$row1){
	           // $data = array('code'=>0,'msg'=>'数据同步出错！', 'time'=>$v['add_time']);
	           $this->goods->rollback();
	           $this->errordata('app_goods', 'goods_key', $v[goods_key], __ACTION__);
	           return false;           
	        }else{
	           $this->goods->commit();
	           return true;
	        }
	    }else{
	        if(!$row){
	           // $data = array('code'=>0,'msg'=>'数据同步出错！', 'time'=>$v['add_time']);
	           $this->goods->rollback(); 
	           $this->errordata('app_goods', 'goods_key', $v[goods_key], __ACTION__);
	           return false;
	        }else{
	           $this->goods->commit();
	           return true;
	        }
	    }
	}
	//插入同步错误日志表
	private function errordata($table_name, $key, $value, $act){
	    $syncData = D('syncData');
        $syncData->startTrans();
       	$data = array('table_name'=>$table_name,'key'=>$key,'add_time'=>time(),'act'=>$act,'value'=>$value);
	    $bool = $syncData->add($data);
	    if($bool){
	    	$syncData->commit();
	    }else{
	    	$syncData->rollback();
	    }
	}
}
