<?php
namespace Think;
header("Content-type:text/html; charset=UTF-8");
/* *
 * 类名：HuanQiuMeiTaoApi
 * 功能：环球美淘数据中台接口请求类
 * 详细：构造环球美淘数据中台美淘产品接口请求，获取远程HTTP数据
 * 版本：1.0
 * 日期：2018-01-161
 * 编写人：tao
 * 说明：
 * 以下代码只是为了方便客户测试而提供的样例代码，客户可以根据自己网站的需要，按照技术文档自行编写,并非一定要使用该代码。
 * 该代码仅供学习和研究环球美淘数据中台接口使用，只是提供一个参考。
 */
class HuanQiuMeiTaoApi {
		private $appid;
    	private $appkey;
		function  __construct() {
       			$appid = $this->appid();
       			$appkey = $this->appkey();
       			define("API_ACCOUNT",$appid);//
       			define("API_PASSWORD",$appkey);
       			//dump($appid);die;
   			 }
        //环球美淘数据中台美淘产品接口URL,该参数可不用修改
		//const API_SEND_URL='http://192.168.1.69/hqmt_tpadmin/admin/ApiHqmt/index';
		const API_SEND_URL 	 ='http://debug.hqmt360.com/hqmt_tpadmin/home/ApiHqmt/index';
		// const API_ACCOUNT 	 =  APPID;//美淘账号 替换成你自己的账号
		// const API_PASSWORD 	 = 	APPKEY;//美淘密码 替换成你自己的密码
		const API_SINGNATURE ='hqmt';//美淘签名 不可更改，请求美淘中台接口数据的唯一标识

	public function appid() {
		//美淘商品接口参数
		$config = D('config');

		$row = $config->field('appid')->limit(1)->find();
		//return $row['user_rank'];;
		//dump($row);die;
		
		return $row['appid'];
	}
	public function appkey() {
		//美淘商品接口参数
		$config = D('config');

		$row = $config->field('appkey')->limit(1)->find();
	
		return $row['appkey'];
	}
	
	//echo $appkey;die;
	/**
	 * 美淘商品接口
	 * @param string $type 			商品类型
	 * @param string $signature 	美淘签名
	 * @param string $action 		请求数据行为:判断可以是进行那个模块的数据请求
	 */
	public function goods($type,$action) {
		//dump(API_ACCOUNT);die;  
		//美淘商品接口参数
		$postArr = array (
		          'appid' => base64_encode(API_ACCOUNT),
		          'appkey' => $this->MD_5(API_PASSWORD), 
		          'signature' => $this->MD_5(self::API_SINGNATURE.API_PASSWORD),    
		          'type' => $type,
		          'act' => $action,
             );	
          		
			$result = $this->curlPost( self::API_SEND_URL , $postArr);
			return $result;
	}
	/**
	 * update_goods 美淘更新商品接口
	 * @param string $type 			商品类型
	 * @param string $signature 	美淘签名
	 * @param string $action 		请求数据行为:判断可以是进行那个模块的数据请求
	 */
	public function update_goods($time,$action) {
		//美淘商品接口参数
		$postArr = array (
		          'appid' => base64_encode(API_ACCOUNT),
		          'appkey' => $this->MD_5(API_PASSWORD), 
		          'signature' => $this->MD_5(self::API_SINGNATURE.API_PASSWORD),
		          'act' => $action,
		          'time' => $time,
             );	
          	//dump($postArr);die;   	
			$result = $this->curlPost( self::API_SEND_URL , $postArr);
			//dump($result);die;
			return $result;
	}
	/**
	 * 美淘商品更新库存接口
	 * @param string $signature 	美淘签名
	 * @param string $action 		请求数据行为:判断可以是进行那个模块的数据请求
	 */
	public function goods_kucun($action) {
		//查询参数
		$postArr = array( 
		          'appid' => base64_encode(API_ACCOUNT),
		          'appkey' => MD5(API_PASSWORD), 
		          'signature' => $this->MD_5(self::API_SINGNATURE.API_PASSWORD),   
		          'act' => $action,
			);
		//dump($postArr);die;
		$result = $this->curlPost(self::API_SEND_URL, $postArr);
		return $result;
	}
	
	/**
	 * 返回美淘商品运费接口
	 *  * 计算运费
	 * @param   string  $area      			配送区域
	 * @param   float   $goods_weight       商品重量
	 * @param string $signature 	美淘签名
	 * @param string $action 		$action=='shipping_fee' 请求数据行为:判断可以是进行那个模块的数据请
	 */
	public function shipping_fee($goods_weight,$area,$action){
		$postArr = array ( 
          'appid' => base64_encode(API_ACCOUNT),
          'appkey' => MD5(API_PASSWORD),
          'signature' => $this->MD_5(self::API_SINGNATURE.API_PASSWORD),    
          'act' => $action,//$action=='shipping_fee'
          'goods_weight' => $goods_weight,
          'area' => $area,
		);
		$result = $this->curlPost(self::API_SEND_URL, $postArr);
		return $result;
	}
	/**
	 * 美淘商品订单提交接口
	 *  * 计算运费
	 * @param   string  $area      			配送区域
	 * @param   float   $goods_weight       商品重量
	 * @param string $signature 	美淘签名
	 * @param string $action 		$action=='shipping_fee' 请求数据行为:判断可以是进行那个模块的数据请
	 */
	public function order_info($orderdata,$action){
		$postArr = array ( 
          'appid' => base64_encode(API_ACCOUNT),
          'appkey' => MD5(API_PASSWORD),
          'signature' => $this->MD_5(self::API_SINGNATURE.API_PASSWORD),    
          'act' => $action,//$action=='shipping_fee'
          'orderdata' => $orderdata,
      
		);
		$result = $this->curlPost(self::API_SEND_URL, $postArr);
		return $result;
	}
	/**
	 * 美淘返回订单快递单号/物流单号接口
	 * @param string $signature 	美淘签名
	 * @param string $arr 			请求物流单号的订单号
	 * @param string $action 		$action=='shipping_fee' 请求数据行为:判断可以是进行那个模块的数据请
	 */
	public function invoice_no($arr,$action){
		$postArr = array ( 
          'appid' => base64_encode(API_ACCOUNT),
          'appkey' => MD5(API_PASSWORD),
          'signature' => $this->MD_5(self::API_SINGNATURE.API_PASSWORD),    
          'act' => $action,//$action=='shipping_fee'
          'arr' => $arr,//
		);
		//dump($postArr);die;
		$result = $this->curlPost(self::API_SEND_URL, $postArr);
		return $result;
	}
	/**
	 * 美淘中途台订快递信息查询接口
	 * @param string $signature 	美淘签名
	 * @param string $arr 			请求物流单号的订单号
	 * @param string $action 		$action=='shipping_fee' 请求数据行为:判断可以是进行那个模块的数据请
	 */
	public function kuaidi1000($arr,$action,$invoice_no){
		$postArr = array ( 
          'appid' => base64_encode(API_ACCOUNT),
          'appkey' => MD5(API_PASSWORD),
          'signature' => $this->MD_5(self::API_SINGNATURE.API_PASSWORD),    
          'act' => $action,//$action=='shipping_fee'
          'arr' => $arr,//
          'invoice_no' => $invoice_no,//
		);
		//dump($postArr);die;
		$result = $this->curlPost(self::API_SEND_URL, $postArr);
		return $result;
	}
	//语法糖
	public function curlData($postArr){
		//查询参数
		$data = array( 
			          'appid' => base64_encode(API_ACCOUNT),
			          'appkey' => MD5(API_PASSWORD), 
			          'signature' => $this->MD_5(self::API_SINGNATURE.API_PASSWORD),   
					);
		$arr = array_merge($postArr, $data);
		// dump($postArr);
		// exit;
		$result = $this->curlPost(self::API_SEND_URL, $arr);
		return $result;
	}
	/**
	 * 返回加密字符串
	 * $param   string  加密参数
	 * @return  string   	加密数据
	 */
	public function MD_5($param){
		$param=MD5($param);

		return $param;
	}
	/**
	 * 通过CURL发送HTTP请求
	 * @param string $url  //请求URL
	 * @param array $postFields //请求参数
	 */
	private function curlPost($url,$postFields){
		$postFields = http_build_query($postFields);
		//dump($postFields);die;
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields);
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$result = curl_exec ($ch);
		$curlInfo = curl_getinfo($ch);
		curl_close ( $ch );
		//var_dump($result);die;
		return $result;
	}
	
	
}
?>