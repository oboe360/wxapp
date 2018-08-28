<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use think\Controller;
//use think\Loader;
require_once getcwd().'/yunsms/SmsApi.php';
use Aliyun\DySDKLite\Sms\SmsApi;
use Think\Common;
class ThewxregController extends Controller {
	private $theStore = NULL;
	private $storeRank = NULL;
	private $otherConfig = NULL;
	private $shopEarnings = NULL;
	private $shopBankCard = NULL;
	private $user = NULL;
	private $Common = NULL;
	private $tixian_status = array('1' => '待对帐', '2' => '待审核', '3' => '已审核', '4' => '已拒绝');
	private $order_type = array('0' => '普通订单类型', '1' => '礼包订单类型');
	private $is_tixian = array('0' => '末提现', '1' => '已提现', '2' => '已注销');
	public $shop_type = array('0' => '纯wed', '1' => '纯app', '2' => 'app,wed已合并', '3' => '已注销');
	public function _initializes(){
		!is_null($this -> theStore) ? : $this -> theStore = D('theStore');
		!is_null($this -> user) ? : $this -> user = D('user');
		!is_null($this -> storeRank) ? : $this -> storeRank = D('storeRank');
		!is_null($this -> Common ) ? : $this -> Common = new Common();//
	}

	//手动修改店铺用户的关系
	public function relationship_list(){
		$data = I('param.');
		$str = $this->relationship($data['id'], $data['user_id']);
		if($str['error'] == '0'){
			$this->success("修改成功，已经成功修改".$str['str']['num'].'个用户',U('admin/thestore/lists/is_check/'.$data['check']),1);
		}else{
			$this->success($str['str'],U('admin/thestore/lists/is_check/'.$data['check']),1);
		}

	}

	//修改店铺用户的关系
    public function relationship($shop_id, $user_id){
      $user = M('user');
      $time = time();
      //$shop_data = D('the_store')->field('id')->where("user_name='{$phone}'")->find();
      //dump($shop_data);exit;
      //$shop_id = $shop_data['id'];
      $user_data = $user->field('uid,user_rank,shop_id,sj_uid,nickname,headimgurl')->where("uid='{$user_id}'")->find();

      //dump($user_data);exit;
      if(empty($user_data)){
        $data['error'] = '1';
        $data['str'] = '没有查询到用户id！';
        return $data;
        exit();
      }
        if($user_data['user_rank']==1){
        $data['error'] = '1';
        $data['str'] = '当前UID不是会员';
        return $data;
        exit();
      }
      M()->startTrans();
      $str = $user->execute("UPDATE `app_user` SET `shop_id` = '{$shop_id}', `sj_uid` = '0', `bd_sj_uid` = '{$user_data[sj_uid]}' WHERE `uid` = '{$user_data[uid]}'");

      $str2 = $user->execute("UPDATE `app_the_store` SET `binding` = '1', `bd_uid` = '{$user_id}',`bd_time` = '{$time}'  WHERE `id` = '{$shop_id}'");

      $sj_user = $user->field('uid,user_rank,shop_id,sj_uid')->where("shop_id='{$user_data[shop_id]}'")->select();
      $array = $this->relationshop_list($user_data['uid'], $sj_user);
      //$arr = trim($array, ',');
      $str1 = '1';
      if($array['num'] != '0'){
        $string = substr($array['str'],0,strlen($array['str'])-1);
        $str1 = $user->execute("UPDATE `app_user` SET `shop_id` = '{$shop_id}' WHERE `uid` in($string)");
      }
      //dump($str1);exit;
      if($str && $str1){
        M()->commit();
        $data['error'] = '0';
        $data['str'] =$array ;
      }else{
        M()->rollback();
        $data['error'] = '1';
        $data['str'] ='修改用户下级关系错误';
      }
      //dump($data);die;
       return $data;
    }

    //递归出店铺用户的下级
    public function relationshop_list($uid, $arr){
      static $str = '';
      static $num = '0';
      for($i = 0; $i < count($arr); $i++){
        if($arr[$i]['sj_uid'] == $uid){
          $str .= "'".$arr[$i]['uid']."',";
          $num ++;
          $this->relationshop_list($arr[$i]['uid'], $arr);
        }
      }
      return array('str' => $str, 'num' => $num);
    }

}
