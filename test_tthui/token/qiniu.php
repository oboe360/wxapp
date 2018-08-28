<?php
/**
 * 生成七牛  token
 */
include_once('qiniu-token/autoload.php');
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;
//$filePath = $_FILES['file']['tmp_name'];//'./php-logo.png';  

//print_r(portal_qiniu($filePath));
/**
 * [portal_qiniu 上传图片到七牛云]
 * @param  [type] $filePath  [图片的 tmp_name 值]
 * @param  [key] $key  	[图片的名称]
 * @param  string $qiniu_url [七牛云空间图片的外链域名]
 * @param  string $bucket    [七牛云空间名称]
 * @param  string $accessKey [七牛accesskey值]
 * @param  string $secretKey [七牛secretKey值]
 * @return [type]            [成功或失败信息]
 */
function portal_qiniu($filePath, $qiniu_url='http://ozys7zeq8.bkt.clouddn.com', $bucket='burst-images', $suffix='.jpg', $key='', $accessKey = 'XzB-CZAJTQb_gL0TI251y0IYstitNAEMILtNWa-h', $secretKey = 'rMeWb4cPxtggs7_zMXj2pQuvy6IZxP99sTLqtGyl'){
	
	$auth = new Auth($accessKey, $secretKey);
	// // 生成上传Token
	 $token = $auth->uploadToken($bucket); 
	//var_dump($_FILES);
	$uploadMgr = new UploadManager();  
	//print_r($_FILES['file']['tmp_name']);
	if(empty($key)){
		$key = $type.date('YmdHis').mt_rand(1,10000000000).$suffix; 
	} 
//	echo $key;exit;
	list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);  
	//echo "\n====> putFile result: \n";  
	if ($err !== null) { 
	    return $err;
	} else {
		$ret['key'] = $qiniu_url.'/'.$ret['key'];
//		$ret['filename'] = $key;
	    return $ret;  
	}
}

/**
 * [portal_delect 删除七牛云文件]
 * @param  [type] $filePath  [图片的 tmp_name 值]
 * @param  string $qiniu_url [七牛云空间图片的外链域名]
 * @param  string $bucket    [七牛云空间名称]
 * @param  string $accessKey [七牛accesskey值]
 * @param  string $secretKey [七牛secretKey值]
 * @return [type]            [成功或失败信息]
 */
function portal_delect($filePath, $qiniu_url='http://p7bgdovkn.bkt.clouddn.com', $bucket='live-img', $obj = '', $accessKey = 'XzB-CZAJTQb_gL0TI251y0IYstitNAEMILtNWa-h', $secretKey = 'rMeWb4cPxtggs7_zMXj2pQuvy6IZxP99sTLqtGyl'){
	if($obj){
		//存入七牛数据
		$data = array('add_time'=>time(),'url'=>$filePath,'domain'=>$qiniu_url,'bucket'=>$bucket);
		$obj->add($data);
		// exit;
		return null;
	}
	$auth = new Auth($accessKey, $secretKey);
	
	$config = new \Qiniu\Config();
	$bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
	//print_r($bucketManager);exit;
	// 生成上传Token
	 //$token = $auth->uploadToken($bucket);
	$err = $bucketManager->delete($bucket, $filePath);
	return $err;
}
?>