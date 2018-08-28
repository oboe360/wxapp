<?php
namespace Home\Controller;
header('content-type:text/html;charset=utf-8');
header("Access-Control-Allow-Origin:*");
use Think\Controller;
use think\Db;
use Think\Common;
/*引流小程序接口 商城首页数据2018-7-10 by tao*/
class HomeController extends Controller {
  private $goods = NULL;
  private $banner = NULL;
  private $Common = NULL;
  public function _initialize() {
    !is_null($this -> goods) ? : $this -> goods = D('goods');
    !is_null($this -> banner) ? : $this -> banner = D('touch_ad');
    // !is_null($this -> gallery) ? : $this -> gallery = D('GoodsGallery'
    !is_null($this ->Common ) ? : $this -> Common = new Common();//实例化公共函数库;
     $_REQUEST=I('param.');
    $this-> shop_id =$_REQUEST['shop_id'];//店铺ID
    
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
  /*首页商品数据*/
  public function Index() {
    $goods = $this -> goods;
    $Common = $this -> Common;
    $where['is_best'] = 1;
    $where['is_on_sale'] = 1;
   $where['is_delete'] = 0;
    $vipgoodsArr = $goods -> field('goods_brief,goods_id,goods_sn,goods_name,goods_baoz,shop_price,market_price,is_on_sale,is_best,goods_number,goods_img,share_img') -> where($where)->select();
      //$data['goods']=$goodsArr;
      $data['vipgoods']=$vipgoodsArr;//会员套餐数据
      $where['is_hot'] = 1;
      $where['is_on_sale'] = 1;
      // $goodsArr = $goods -> field('goods_brief,goods_id,goods_sn,goods_name,goods_baoz,shop_price,market_price,goods_pr,proportion,is_on_sale,is_best,is_new,is_hot,goods_number,original_img') -> where($wh)->select();

      //  $data['newgoods']=$goodsArr;//会员套餐数据
      $where['position_id'] = 2;
      $bannerArr = $Common ->banner();
      $data['banner']=$bannerArr;
       $shop=$Common->shopinfo($this-> shop_id);
       
        $data['shop']= $shop;

       if(!$vipgoodsArr && !$goodsArr){
          $data['code'] = 0;
          $data['msg'] = "暂无数据";
       }else{

          $data['code'] = 1;
          $data['msg'] = "返回数据成功";
       }
        //$Common->dp($data);die;
        echo json_encode($data);exit();  
      //$count = $goods -> where($where) -> count();

  }
  /*轮播图数据*/
 public function banner() { 
    $banner = $this -> banner;
    $Common = $this -> Common;
    $where['position_id'] = 2;
    $bannerArr = $banner -> field(' ad_link,ad_code') -> where($where)->order('ad_id desc')->select();
      $data['banner']=$bannerArr;
       if(!$bannerArr){
          $data['code'] = 0;
          $data['msg'] = "暂无轮播图数据";
       }else{
          $data['code'] = 1;
          $data['msg'] = "返回轮播图数据成功";
       }
        //$Common->dp($data);die;
        echo json_encode($data);exit();  
      //$count = $goods -> where($where) -> count();

  }


  public function cancel(){
    //$this->redis_list->decr("please_not_interface"); Confirmorder
    $error['code'] = '1';
    echo json_encode($error);
  }










}
