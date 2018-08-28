<?php
namespace Home\Controller;
header('content-type:text/html;charset=utf-8');
header("Access-Control-Allow-Origin:*");
use Think\Controller;
use think\Db;
use Think\Common;
/*引流小程序接口  个人用户中心管理接口 2018-7-10 by tao*/
class UserCenterController extends Controller {
  private $Common = NULL;
  private $uid = NULL;
  private $user = NULL;
  private $order = NULL;
  public function _initialize() {
    !is_null($this -> Common ) ? : $this -> Common = new Common();//实例化公共函数库;
    !is_null($this -> order ) ? : $this -> order =  D('order_info');
    !is_null($this -> user ) ? : $this -> address =  D('user');
    $_REQUEST=I('param.');
    //print_r($_REQUEST);die;
   //  $action=$_REQUEST['act'];
   //$raw_data=file_get_contents("php://input");
    //$data=$_REQUEST;
   //$data = json_decode($raw_data);
    //print_r($data);die;
   //  $userInfo=$data->userInfo;//微信用户的基础信息
    //$get = I('get.');

    //$this-> goods_id =$get['goods_id'];//用户小程序id $data->wid
    //print_r( $this-> goods_id);die;
    $this-> uid =$_REQUEST['uid'];//用户小程序id $data->wids
    $this-> shop_id =$_REQUEST['shop_id'];//用户小程序id $data->wids
    $this-> address_id =$_REQUEST['address_id'];//用户小程序id $data->wids
    $this-> address =$_REQUEST['address'];//用户小程序id $data->wids
    //dump($_REQUEST);die;
   //  $shop_id = $data->shop_id;//拓客宝标识id
   //  $shop_id = $data->shop_id;//拓客宝标识id
    // if(!session('user_id')){
    //   echo json_encode(array('code'=>2,'msg'=>U('admin/login/login')));
    //   exit;
    // }
    /*//判断token是否存在s或失效ss
     * $token = $this->setToken();
     * if($token != cookie('token') || cookie('user_id')){
     *    echo 'NoLogin';
     *    exit;说
     * }
     * */
  }
  /*收货地址列表数据*/
  public function Index() {
    $order = $this -> order;
    $user = $this -> user;
    $Common = $this -> Common;
    $uid = $this -> uid;
    $userinfo=$Common->user_info($uid);
    $order=$Common->order_total($uid);
    $ordernumber=$Common->get_order_number($uid);
    $userinfo['ordernumber']=$ordernumber;
   // $userinfo['order']=$order;
    $userinfo['tel']=$Common->other_config('customer_tel');
   // $Common-> dp($userinfo);die;
    if($userinfo&&$uid){
        $data = array(
            'userinfo'=>$userinfo,
            'order'=>$order,
            'msg'=>'用户基本信息',
            'code'=>1,
        );
    }else{
        $data = array(
            'msg'=>'失敗',
            'code'=>0,
        );
    }
    $data['upgrade_code'] = $this->free_upgrade($uid);
    $this->getUpdateErrorTixian($data);
    echo json_encode($data);
  }
  //获取用户是否需要修改提现信息
  private function getUpdateErrorTixian($data){
    $param = I('param.');
    $param['status'] = 2;
    $bank_list = M('tixian')->field('bank_province')->where($param)->find();
    if(empty($bank_list['bank_province']) && $bank_list){
      $data['code'] = 2;
      $data['msg'] = '由于提现业务系统升级，需要您提供提现银行所属省市信息，您存在未提现记录，请前往修改银行卡信息！';
      return $this->ajaxReturn($data);
    }
  }
  /*收入流水数据数据*/
  public function vip_qianbao_sn() {
      $goods = $this -> goods;
      $Common = $this -> Common;
      $uid = $this -> uid;
      $vip_qianbao_sn= $Common->vip_qianbao_sn($uid);
     // $Common->dp($vip_qianbao_sn);die;
      if(!$vip_qianbao_sn[orderArr]){
          $data['vip_qianbao_sn'] = $vip_qianbao_sn;
          $data['code'] = 0;
          $data['msg'] = "暂无数据";
       }else{
          $data['vip_qianbao_sn'] = $vip_qianbao_sn;
          $data['code'] = 1;
          $data['msg'] = "返回数据成功";
       }
      echo json_encode($data);exit();  
  }

   public function free_upgrade($uid){
    $uid = $_REQUEST['uid']; 
    //$this ->Common->file_put("login2.txt",$uid);
    $user_rank = D('user')->field('user_rank')->where("`uid` = '{$uid}'")->find();
    // $user_rank = 2;

    if($user_rank['user_rank'] == 2) {
      $fans_num = D('user')->where("`sj_uid` = '{$uid}'")->count();
     // echo  $fans_num;
      $upgrade_num = M('other_config')->where('`key` ="upgrade_num"')->getField("value");
      //echo  $upgrade_num;die;
      //$this ->Common->file_put("login2.txt",$upgrade_num);
      $bd_uid = M('the_store')->where('bd_uid ='.$uid)->getField('bd_uid');
      //echo $bd_uid;die;
      if ($fans_num >= $upgrade_num && $bd_uid=="") {
        $data2['upgrade_code'] = '1';
        //echo 111;
      }else{
        $data2['upgrade_code'] = '2';
        // echo json_encode($data);
      }
    }else{
      $data2['upgrade_code'] = '2';
      // echo json_encode($data);
    }
   // echo  $data2['upgrade_code'];die;
    return $data2['upgrade_code'];
  }

}
