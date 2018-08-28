<?php
namespace Home\Controller;
header('content-type:text/html;charset=utf-8');
header("Access-Control-Allow-Origin:*");
use Think\Controller;
use think\Db;
use Think\Common;
/*引流小程序接口  购物车功能接口 2018-7-10 by tao*/
class CartController extends Controller {
  private $goods = NULL;
  private $cart = NULL;
  private $Common = NULL;
  private $uid = NULL;
  public function _initialize() {
    !is_null($this -> cart) ? : $this -> cart = D('cart');
    !is_null($this -> goods) ? : $this -> goods = D('goods');
    !is_null($this -> Common ) ? : $this -> Common = new Common();//实例化公共函数库;
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
    $this-> uid =$_REQUEST['uid'];//用户小程序id $data->wid
    $this-> goods_id =$_REQUEST['goods_id'];//用户小程序id $data->wid
    $this-> goods_number =$_REQUEST['goods_number'];//用户小程序id $data->wid
    $this-> cid =$_REQUEST['cid'];//用户小程序id $data->wid
   //  $shop_id = $data->shop_id;//拓客宝标识id
    // if(!session('user_id')){
    //   echo json_encode(array('code'=>2,'msg'=>U('admin/login/login')));
    //   exit;
    // }
    /*//判断token是否存在s或失效ss
     * $token = $this->setToken();
     * if($token != cookie('token') || cookie('user_id')){
     *    echo 'NoLogin';
     *    exit;
     * }
     * */
  }
  /*购物车数据*/
  public function Index() {
    $goods = $this -> goods;
    $Common = $this -> Common;
    $uid = $this -> uid;
    $cart = $Common -> get_cart_goods($uid);//商品数据
    //print_r($cartgoods);die;
      // 
      if(!$cart['cartgoods']){
          $data['code'] = 0;
          $data['msg'] = "暂无数据";
       }else{
          $data['cartgoods']=$cart['cartgoods'];
          $data['total']=$cart['total'];
          $data['code'] = 1;
          $data['msg'] = "返回数据成功";
       }
       //$Common->dp($data);die;
        echo json_encode($data);exit();  
      //$count = $goods -> where($where) -> count();
  }

 /*添加商品到购物车功能*/
  public function add_cart() { 
    //$get = I('get.');
    $goods = $this -> goods;
    $Common = $this -> Common;
    $uid = $this -> uid;
    $goods_id = $this -> goods_id;
    $goods_number = $this -> goods_number;
    $goods_id= $goods_id;
    $number=$goods_number;
    if($number==0){
       $number=1; 
    }
    $where['goods_id'] = $goods_id;
    $res = $Common -> addto_cart($goods_id,$number,$uid);//商品数据
    $res['cart_number'] =$Common->insert_cart_info_number($uid);
    echo json_encode($res);exit(); 
  }

 /*商品购物车增加减少功能*/
  public function ajax_update_cart() {
    $goods = $this -> goods;
    $uid = $this -> uid;
    $cart = $this -> cart;
    $Common = $this -> Common;
    $goods_id = $this -> goods_id;
    $cid = $this -> cid;//需要更新商品的购物车ID
    $goods_number = $this -> goods_number;//更新数量 
    //dump( $goods_number);die;
  if($goods_number <= 0)
    {
        $data['code'] = 2;
        $data['message'] = "购买商品数量不能为零！";            
       echo json_encode($data);
       exit;
    }
    //dump($cid);die;
    $wh['cid']=$cid;
    $wh['user_id']=$uid;
    // $where['user_id']=$uid;
    $where['goods_id']=$goods_id;
    $goods = $goods -> field('goods_number')-> where($where)->find();
    if($goods_number > $goods['goods_number'])
    {
        $data['code'] =3;
        $data['message'] = "购买数量大于库存~！";            
        echo json_encode($data);
        exit;
    }
    //$row = $cart -> field('cid')-> where($where)->find();
    $arr['goods_number'] = $goods_number;
    $arr['addtime']= time();
    $res = $cart->where($wh)->data($arr)->save();
    //echo $cart->_sql();die;
    //$Common->file_put('a.txt',$res.'_'.$cart->_sql());
    if($res){
        $cart_goods =  $Common -> get_cart_goods($uid);
        $data['total_desc'] = $cart_goods['total']['subtotal'];
        $data['total_number'] =$cart_goods['total']['total_number'];
        $data['code'] =1;
        $data['message'] = "更新购物车成功";            
        echo json_encode($data);
        exit;
    }else{
      $data['code'] =4;
      $data['message'] = "更新购物车失败";            
      echo json_encode($data);
      exit;
    }
  }

  /** 删除购物车中的商品*/
   public function drop_cart_goods(){
      $uid = $this -> uid;//用户ID
      $cart = $this -> cart;//购物车
      $Common = $this -> Common;//公共函数
      $goods_id = $this -> goods_id;//商品ID
      $cid = $this -> cid;//需要更新商品的购物车ID
      $res = $Common->drop_cart_goods($cid,$uid);//删除购物车商品
      if($res==1){
            $data['code'] = 1;
            $data['msg'] = "删除成功";
        }else{
            $data['code'] = 2;
            $data['msg'] = "删除失败";
        }
         echo json_encode($data);
         exit;
   }
   

}
