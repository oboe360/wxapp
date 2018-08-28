<?php
namespace Home\Controller;
header('content-type:text/html;charset=utf-8');
header("Access-Control-Allow-Origin:*");
use Think\Controller;
use think\Db;
use Think\Common;
/*引流小程序接口  商品详情页数据 2018-7-10 by tao*/
class GoodsdetailController extends Controller {
  private $goods = NULL;
  private $banner = NULL;
  private $Common = NULL;
  private $dirname = NULL;
  public function _initialize() {
    $this->dirname = $_SERVER['REQUEST_SCHEME'].'://'.dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
    !is_null($this -> goods) ? : $this -> goods = D('goods');
    !is_null($this -> banner) ? : $this -> banner = D('touch_ad');
    !is_null($this -> gallery) ? : $this -> gallery = D('GoodsGallery');
    !is_null($this ->Common ) ? : $this -> Common = new Common();//实例化公共函数库;
    $_REQUEST=I('param.');
    $this-> goods_id =$_REQUEST['goods_id'];//店铺ID
    $this-> uid =$_REQUEST['uid'];//店铺ID
    // if(!session('user_id')){
    //   echo json_encode(array('code'=>2,'msg'=>U('admin/login/login')));
    //   exit;
    // }
    /*//判断token是否存在s或失效s
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
    $goods_id =$this-> goods_id;
     $user_rank = $Common->user_rank($this-> uid);
    $where['goods_id'] = $goods_id;
    $goods = $Common -> get_goods_detail($goods_id);//商品数据
    $detail=$goods['goods_desc'];
     //$Common->dp($goods);die;
    preg_match_all('/<img.*?src="(.*?)".*?>/is',$detail,$array);
    $goods['goods_desc']=$array[1];//单商品详情图
    $goods['sales_count']=$goods['sales_count']+$goods['offline_count'];
   // $Common->dp($goods);die;
    $pictures = $Common-> get_goods_gallery_dersym($goods_id);//轮播图

    $gif_img = M('other_config')->where(array('key'=>'gif'))->getField('img_path');
    $gif_img = json_decode($gif_img,true);
    foreach ($gif_img as $key => $value) {
      $gif_img[$key] = $this->dirname.$value;
    }
     $data['gif_img']=$gif_img;//会员套餐数据
     if(!$pictures){
        $pictures[]['img_url']=$goods['goods_img'];
    }   
    if(!$goods){
          $data['code'] = 0;
          $data['msg'] = "暂无数据";
       }else{
          $data['goods']=$goods;//会员套餐数据
          $data['user_rank']=$user_rank;//会员套餐数据
          $data['pictures']=$pictures;//商品详情轮播图
          $data['code'] = 1;
          $data['msg'] = "返回数据成功";
       }
        //$Common->dp($data);die;
        echo json_encode($data);exit();  
      //$count = $goods -> where($where) -> count();

  }

// //获取分销图片
//   public function distributionImg(){
//     echo json_encode(array('code'=>1,'msg'=>'分销图获取成功','url'=>$_SERVER['REQUEST_SCHEME'].'://'.dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/Public/Home/distribution/distribution.png'));
//             exit;
//   }
//   //获取新手说明图片
//   public function newImg(){
//     $dirname = $_SERVER['REQUEST_SCHEME'].'://'.dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/Public/Home/distribution/';
//     $data = array($dirname.'new11.png',$dirname.'new12.png',$dirname.'new13.png');
//     echo json_encode(array('code'=>1,'msg'=>'说明图获取成功','url'=>$data));
//             exit;
//   }
  //获取分销图片
  public function distributionImg(){
    //获取分享赚钱图片
    $share_money_img = M('other_config')->where(array('key'=>'share_money_img'))->getField('img_path');
    $share_money_img = json_decode($share_money_img,true);
    foreach ($share_money_img as $key => $value) {
      $share_money_img[$key] = $this->dirname.$value;
    }
    echo json_encode(array('code'=>1,'msg'=>'分销图获取成功','url'=>$share_money_img));
    exit;
  }
  //获取新手说明图片
  public function newImg(){
    //获取新手指南图片
    $new_img_list = M('other_config')->where(array('key'=>'new_img'))->getField('img_path');
    $new_img_list = json_decode($new_img_list,true);
    foreach ($new_img_list as $key => $value) {
      $new_img_list[$key] = $this->dirname.$value;
    }
    echo json_encode(array('code'=>1,'msg'=>'说明图获取成功','url'=>$new_img_list));
    exit;
  }

}
