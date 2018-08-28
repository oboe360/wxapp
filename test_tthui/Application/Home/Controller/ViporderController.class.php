<?php
namespace Home\Controller;
header('content-type:application/x-www-form-urlencoded');
// header("Access-Control-Allow-Origin:*");
use Think\Controller;
use think\Db;
use Think\Common;//
//use Think\Wxpay;
//use Home\Model\Wxpay;
//use Think\HuanQiuMeiTaoApi;
/*引流小程序接口  会员礼包确认订单能接口 2018-7-10 by tao*/
class ViporderController extends Controller {
  private $goods = NULL;
  private $cart = NULL;
  private $Common = NULL;
  private $uid = NULL;
  private $address = NULL;
  private $Getpayparams = NULL;
  
 // private $HuanQiuMeiTaoApi = NULL;
  private $Wxpay = NULL;
  public function _initialize() {
    !is_null($this -> cart) ? : $this -> cart = D('cart');
    !is_null($this -> goods) ? : $this -> goods = D('goods');
    !is_null($this -> Common ) ? : $this -> Common = new Common();//实例化公共函数库;
    //!is_null($this -> Wxpay ) ? : $this -> Wxpay = new Wxpay();//实例化公共函数库;
    !is_null($this -> address ) ? : $this -> address =  D('user_address');
    $_REQUEST=I('param.');
    // echo json_encode($_REQUEST);die;
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
    $this-> yunshipping_id =$_REQUEST['yunshipping_id'];
    $this-> wareshipping_id =$_REQUEST['wareshipping_id'];
    $this-> code =$_REQUEST['code'];//用户小程序id $data->wids
    $this-> address_id =$_REQUEST['address_id'];//用户小程序id $data->wids
    $this-> pay_id =$_REQUEST['pay_id'];//用户小程序id $data->wids
    $this-> inv_payee =$_REQUEST['inv_payee'];//备注
    $this-> goods_id =$_REQUEST['goods_id'];//备注
    $this-> type =$_REQUEST['type'];//订单类型
    //dump($_REQUEST);die;
    $this-> shop_id = $_REQUEST['shop_id'];//拓客宝标识id
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
  /*确认订单数据*/
  public function Index() {
    $goods = $this -> goods;
    $Common = $this -> Common;
    //$HuanQiuMeiTaoApi = $this -> HuanQiuMeiTaoApi;
    $uid = $this -> uid;
    $Wxpay = $this -> Wxpay;
    //dump(55);die;
    $consignee = $Common->get_default_consignee($uid);//获取用户的默认收货地址
    // if(!$consignee){
    //     $data['code']=2;
    //     $data['msg']="没有收货地址";
    //     echo json_encode($data);
    //       //$Common->dp($data);die;
    //     exit() ;
    // }
    $vip_goods = $Common->vip_goods($this-> goods_id); // 取得礼包商品数据
    //$Common->dp( $vip_goods);die;
    if(!$vip_goods){
        $data['code'] = 0;
        $data['msg'] = "暂无数据";
     }else{
        $data['vip_goods'] = $vip_goods;
        $data['consignee'] = $consignee;
        $data['code'] = 1;
        $data['msg'] = "返回数据成功";
     }
     
        echo json_encode($data);exit();  
      //$count = $goods -> where($where) -> count();
  }

/*提交订单接口*/
public function done(){
  // file_put_contents('result.txt', $_REQUEST['code']);
  // echo json_encode(I('param.'));
  // exit;
    $Common = $this -> Common;
    $uid = $this -> uid;
    $user_rank=$Common->user_rank($uid);//获取用户的当前等级
    //$where['key']='order';
    // $row = $this->other_config->field('value')->where($where)->find();
    $qz  = $Common->other_config('order');//订单号的前缀
    //echo $qz;die;
    //$rate=$Common->commission_rate($user_rank,$goods_type);//查询分佣比例
    $sjuid=$Common->sjuid($uid);//获取用户的上级UID
    if($sjuid!=0 && $user_rank==1){
      $user = D('user');
      $where['uid']=$sjuid;
      $sj_shop_id = $user->where($where)->getField('shop_id');
      if($this-> shop_id!=$sj_shop_id && $sj_shop_id){
        $arr['shop_id'] = $sj_shop_id;
        $where['uid']=$uid;
        $res = $user->where($where)->save($arr);
      }

    }
    $ssjuid=$Common->sjuid($sjuid);//获取用户的上级的上级UID
    $consignee=$Common->get_address($this -> address_id);//获取用户的收货地址
    $vip_goods = $Common->vip_goods($this-> goods_id); // 取得礼包商品数据
    $first_comm=$vip_goods['first_comm']/100;//一级分佣
    $second_comm=$vip_goods['second_comm']/100;//二级分佣
    $shop_comm=$vip_goods['shop_comm']/100;//店主分佣
    //$Common -> dp( $cart_goods);die;
    $order[pay_status]=0;
    $order[goods_amount]=$vip_goods['market_price']?:0;
    $order[order_amount]=$vip_goods['market_price']?:0;
    $order[user_id]=$uid;
    $order[order_status]=0;
    $order[shipping_status]=0;
    $order[pay_status]=0;
    $order[shop_id]=$this-> shop_id;
    $order[consignee]=$consignee[consignee];
    $order[country]=$consignee[country];
    $order[province]=$consignee[province];
    $order[city]=$consignee[city];
    $order[district]=$consignee[district];
    $order[address]=$consignee[address];
    $order[tel]=$consignee[tel];
    $order[pay_id]=1;
    //$order[shipping_code]="yunda";
    $order[pay_name]="小程序微信支付";
    $order[inv_payee]=$this->inv_payee;
    $order[inv_type]=1;
    $order[my_lv]=$user_rank;
    $order[sj_uid]=$sjuid?:0;
    $order[sj_money]=$vip_goods['market_price']*$first_comm;
    $order[ssj_uid]=$ssjuid?:0;
    $order[ssj_money]=$vip_goods['market_price']*$second_comm;
    $order[shop_money]=$vip_goods['market_price']*$shop_comm;
    $order[add_time]=time();
    $order[shipping_time]=time();
    $order[order_sn]=$Common->get_order_sn($qz,$uid);
   // $Wxpay = $this -> Wxpay;
    //$wxdata =$Wxpay->small_app($order,$code);
    //$order_goods=$Common->order_goods($uid);
    // echo json_encode($order);
    // exit;
    $arr =  $Common-> add_vip_order($order,$vip_goods);
    $order['log_id'] = $arr['log_id'];
    $order['goods_id'] = $this-> goods_id;
    //$Common->dp($wxdata);die;
    if($arr['code']==1){//插入数据成功，唤起微信支付
           /* 取得支付信息，生成支付代码 */
      if ($order['order_amount'] > 0){
        //调用获取支付参数接口
        // if($uid==2){
         //$order['order_amount']=0.01;
        // }
        
        !is_null($this -> Getpayparams) ? : $this -> Getpayparams = new \Home\Controller\TgGetpayparamsController();

          $this->Getpayparams->tg_getPayParam($order, $this-> code);
      } 
    }else{//失败
      echo json_encode(array('code'=>0,'msg'=>'下单失败'));
      exit;
    }
}
  /*会员套订单数据*/
  public function vip_order_info() {
      $goods = $this -> goods;
      $Common = $this -> Common;
      $uid = $this -> uid;
       $type=$this-> type;
      $viporder= $Common->vip_order_info($uid,$type);
     //$Common->dp($viporder);die;
      if(!$viporder[orderArr]){
         $data['viporder'] = $viporder;
          $data['code'] = 0;
          $data['msg'] = "暂无数据";
       }else{
          $data['viporder'] = $viporder;
          $data['code'] = 1;
          $data['msg'] = "返回数据成功";
       }
      echo json_encode($data);exit();  
  }



}
