<?php
namespace Home\tg_wechat_pay;
header('content-type:text/html;charset=utf-8');
header("Access-Control-Allow-Origin:*");
use Think\Controller;
use think\Db;
use Think\Common;
use Think\HuanQiuMeiTaoApi;
/*引流小程序接口  确认订单能接口 2018-7-10 by tao*/
class ConfirmorderController extends Controller {
  private $goods = NULL;
  private $cart = NULL;
  private $Common = NULL;
  private $uid = NULL;
  private $address = NULL;
  private $HuanQiuMeiTaoApi = NULL;
  public function _initialize() {
    !is_null($this -> cart) ? : $this -> cart = D('cart');
    !is_null($this -> goods) ? : $this -> goods = D('goods');
    !is_null($this -> Common ) ? : $this -> Common = new Common();//实例化公共函数库;
     !is_null($this -> HuanQiuMeiTaoApi ) ? : $this -> HuanQiuMeiTaoApi = new HuanQiuMeiTaoApi();//实例化公共函数库;
    !is_null($this -> address ) ? : $this -> address =  D('user_address');
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
    $this-> yunshipping_id =$_REQUEST['yunshipping_id'];
    $this-> wareshipping_id =$_REQUEST['wareshipping_id'];
    $this-> shop_id =$_REQUEST['shop_id'];//用户小程序id $data->wids
    $this-> code =$_REQUEST['code'];//用户小程序id $data->wids
    $this-> address_id =$_REQUEST['address_id'];//用户小程序id $data->wids
    $this-> pay_id =$_REQUEST['pay_id'];//用户小程序id $data->wids
    $this-> inv_payee =$_REQUEST['inv_payee'];//备注
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
  /*确认订单数据*/
  public function Index() {
    $goods = $this -> goods;
    $Common = $this -> Common;
    $HuanQiuMeiTaoApi = $this -> HuanQiuMeiTaoApi;
    $uid = $this -> uid;
    $res = $Common->check_cart_goods($uid);//商品数据
    if($res==0){
         $data['code']=0;
         $data["status"]=2;
         $data['msg']="购物车为空";
        /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
        echo json_encode($data);
        exit;
    }
    $consignee = $Common->get_default_consignee($uid);//获取用户的默认收货地址
    if(!$consignee){
        $data['code']=0;
        $data['status']=3;
        $data['msg']="没有收货地址";
        echo json_encode($data);
          //$Common->dp($data);die;
        exit() ;
    }
    $cart_goods = $Common->cart_goods($uid); // 取得商品列表，计算合计
    $total_money = $cart_goods['total_money'];//订单商品总价和
    $total_number =$cart_goods['total_number'];//统计商品的总数量
    $_SESSION['total_money']= $total_money;
    $_SESSION['total_number']= $total_number;
    $data['yuncang_list']=$cart_goods['yuncang'];//美淘云倉商品
    $data['warehouse_list']=$cart_goods['warehouse'];//本地倉商品
    foreach ($cart_goods['yuncang'] as $k => $v) {
        $weight['yuncang_toal_weight'] +=$v['goods_weight'];//算出云倉的商品重量
    }
    foreach ($cart_goods['warehouse'] as $k => $v) {
        $weight['warehouse_toal_weight'] +=$v['goods_weight'];//算出本地仓的商品重量
    }
    if($cart_goods['warehouse']){
        $warehouse_shipping = $Common->shipping_list($consignee['province'], $weight['warehouse_toal_weight']);//
        $data['shipping_list']['warehouse']= $warehouse_shipping;//
        $_SESSION['warehouse_shipping_list'] = $warehouse_shipping;
    }
    //$Common->dp($warehouse_shipping);die;
      if($cart_goods['yuncang']){//云仓商品的快递费用
            $company = $HuanQiuMeiTaoApi->shipping_fee($weight['yuncang_toal_weight'],$consignee['province'], 'shipping_fee');
          $shipping_company = json_decode($company,true);
         if($shipping_company[code] != 1){
              $err['error']='1';
              $err['msg']="计算邮费返回错误！";
              echo json_encode($err);
              exit;
          }else{
            if($shipping_company[arr]['type'] == 1){
                //物流
                $shopp_arr['0']['shipping_name'] = $shipping_company[arr][wuliu];
                $shopp_arr['0']['shipping_fee'] = $shipping_company[arr][wuliu_fee] ? floatval($shipping_company[arr][wuliu_fee]) : 0;
                $shopp_arr['0']['shipping_name'] .= '(物流自取货)';
                $shopp_arr['0']['shipping_id'] = '200';
            }elseif($shipping_company[arr]['type'] == 2){
                //申通
                $shopp_arr['0']['shipping_name'] = $shipping_company[arr][st_express];
                $shopp_arr['0']['shipping_fee'] = floatval($shipping_company[arr][st_fee]);
                $shopp_arr['0']['shipping_id'] = '170';
              
                //$data['total']['shipping_fee'] += $shopp_arr['0']['shipping_fee'];
            }elseif($shipping_company[arr]['type'] == 3){
                //申通和物流
                $shopp_arr['0']['shipping_name'] = $shipping_company[arr][st_express];
                $shopp_arr['0']['shipping_fee'] = floatval($shipping_company[arr][st_fee]);
                $shopp_arr['0']['shipping_id'] = '170';
                $shopp_arr['1']['shipping_name'] = $shipping_company[arr][wuliu];
                $shopp_arr['1']['shipping_fee'] = $shipping_company[arr][wuliu_fee ] ? floatval($shipping_company[arr][wuliu_fee]) : 0;
                $shopp_arr['1']['shipping_name'] .= '(物流自取货)';
                $shopp_arr['1']['shipping_id'] = '200';
                //$data['total']['shipping_fee'] += $shopp_arr['0']['shipping_fee'];
            }
            $shopp_arr['0']['type'] = $shipping_company[arr]['type'];
          }
          $data['shipping_list']['yuncang']= $shopp_arr;//云仓商品的快递费用
          //将公司的快递方式传进session 
          $_SESSION['yuncang_shipping_list'] = $shopp_arr;
      }
       
        $data['total']['shipping_fee']= $shopp_arr['0']['shipping_fee']+ $warehouse_shipping['0']['shipping_fee'];//两个仓的费用
        $data['total']['total_money']= $total_money;
        $data['total']['total_number']= $total_number;
     $Common->dp($data);die;
    if(!$data){
        $data['code'] = 0;
        $data['msg'] = "暂无数据";
     }else{
        $data['code'] = 1;
        $data['msg'] = "返回数据成功";
     }
     
        echo json_encode($data);exit();  
      //$count = $goods -> where($where) -> count();
  }

/*提交订单接口*/
public function done(){
    $Common = $this -> Common;
    $HuanQiuMeiTaoApi = $this -> HuanQiuMeiTaoApi;
    $uid = $this -> uid;
 //
    $consignee=$Common->get_address($this -> address_id);//获取用户的收货地址
    
    $cart_goods = $Common->cart_goods($uid); // 取得商品列表，计算合计
    //$Common -> dp( $cart_goods);die;
    $order['goods_amount'] = $cart_goods['total_money'];//订单商品总价和
    $order[yunshipping_id] = $this -> yunshipping_id;//云仓id
    $order[shipping_id] = $this -> wareshipping_id;//本地仓io
    $yuncang_shipping_list = $_SESSION['yuncang_shipping_list'];
    $warehouse_shipping_list = $_SESSION['warehouse_shipping_list'];
    if($order[yunshipping_id]){//选择云仓快递
      foreach ($yuncang_shipping_list as $k => $v){
          if($v['shipping_id']==$order[yunshipping_id]){
              $order[yuncang_shipping_fee] =$v['shipping_fee'];
              $order[yuncang_shipping_name] =$v['shipping_name'];
          }
      }
    }
    if($order[shipping_id]){//选择本地快递
      foreach ($warehouse_shipping_list as $k => $v){
          if($v['touch_id']==$order[shipping_id]){
            $order[warehouse_shipping_fee] =$v['shipping_fee'];
            $order[shipping_name] =$v['shipping_name'];
          }
      }
    }
  //区分开订单的类型
    if($cart_goods['warehouse'] && $cart_goods['yuncang']){
        $order['the_order_category'] = '0';
        //exit();
    }elseif(!$cart_goods['warehouse'] && $cart_goods['yuncang']){
        $order['the_order_category'] = '1';
        //exit();
    }elseif($cart_goods['warehouse'] && !$cart_goods['yuncang']){
        $order['the_order_category'] = '2';
       // exit();
    }
     //$Common->dp($order);die;
    $order['shipping_fee']= $order[warehouse_shipping_fee] + $order[yuncang_shipping_fee];//两个仓的费用
    $order['order_amount'] = $cart_goods['total_money']+$order['shipping_fee'];//订单商品总价和+快递费用
    $order['yuncang_total_amount']= $cart_goods[yuncang_money];//云仓商品价格总和
    $order['warehouse_total_amount']= $cart_goods[warehouse_money];//本地仓商品价格总和
    $user_rank=$Common->user_rank($uid);//获取用户的当前等级
    $order[pay_status]=0;
    $order[user_id]=$uid;
    $order[order_status]=0;
    $order[shipping_status]=0;
    $order[pay_status]=1;
    $order[shop_id]=$this-> shop_id;
    $order[consignee]=$consignee[consignee];
    $order[country]=$consignee[country];
    $order[province]=$consignee[province];
    $order[city]=$consignee[city];
    $order[district]=$consignee[district];
    $order[address]=$consignee[address];
    $order[tel]=$consignee[tel];
    $order[pay_id]=$this-> pay_id;
    $order[pay_name]="小程序微信支付";
    $order[inv_payee]=$this-> inv_payee;
    $order[add_time]=time();
    $order[shipping_time]=time();
    $order[my_lv]=$user_rank;
    $order[order_sn]=$Common->get_order_sn('fls',$uid);
    $order_goods=$Common->order_goods($uid);
    $arr =  $Common-> add_order($order,$order_goods);
    //$Common->dp($arr);die;
    if($arr['code']==1){//插入数据成功，唤起微信支付
           /* 取得支付信息，生成支付代码 */
          if ($order['order_amount'] > 0)
          {   
        
            $wxdata =small_app($order,$code);

            $data=array(
                   'order_id'=>$order_id,
                   'order_sn'=>$order['order_sn'],
                   'wxdata'=>$wxdata,
                  );
                  //dump($data);die;
               echo json_encode($data);
              exit();
          } 
                


    }else{//失败
      echo json_encode($arr);die;
    }
}

/*选择快递接口*/
public function select_shipping(){
    // $goods = $this -> goods;
    $Common = $this -> Common;
    // $HuanQiuMeiTaoApi = $this -> HuanQiuMeiTaoApi;
    // $uid = $this -> uid;
    $yunshipping_id = $this -> yunshipping_id;//云仓id
    $wareshipping_id = $this -> wareshipping_id;//本地仓io
    $yuncang_shipping_list = $_SESSION['yuncang_shipping_list'];
    $warehouse_shipping_list = $_SESSION['warehouse_shipping_list'];
    $total_money = $_SESSION['total_money'];
    $total_number = $_SESSION['total_number'];
    dump($warehouse_shipping_list);
    if($yunshipping_id){//选择云仓快递
      foreach ($yuncang_shipping_list as $k => $v){
          if($v['shipping_id']==$yunshipping_id){
            $yun_shipping_fee =$v['shipping_fee'];
          }
      }
    }else{
      $yun_shipping_fee =$yuncang_shipping_list['0']['shipping_fee'];
    }
    if($wareshipping_id){//选择本地快递
      foreach ($warehouse_shipping_list as $k => $v){
          if($v['touch_id']==$wareshipping_id){
            $ware_shipping_fee =$v['shipping_fee'];
          }
      }
    }else{
       $ware_shipping_fee =$warehouse_shipping_list['0']['shipping_fee'];
     }
  $data['total']['shipping_fee']= $yun_shipping_fee + $ware_shipping_fee;//两个仓的费用

  $data['total']['total_money']= $total_money;
  $data['total']['total_number']= $total_number;
  $data['code']=1;
  $data['msg']="返回数成功";  
 // $Common -> dp($data);die;
  echo json_encode($data);exit();  
}


public function cancel(){
  //$this->redis_list->decr("please_not_interface");
  $error['code'] = '1';
  echo json_encode($error);
}

}
