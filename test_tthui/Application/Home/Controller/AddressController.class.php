<?php
namespace Home\Controller;
// header('content-type:text/html;charset=utf-8');
// header("Access-Control-Allow-Origin:*");
header('content-type:application/x-www-form-urlencoded');
use Think\Controller;
use think\Db;
use Think\Common;
/*引流小程序接口  收货地址管理接口 2018-7-10 by tao*/
class AddressController extends Controller {
 
  private $Common = NULL;
  private $uid = NULL;
  private $user = NULL;
  private $user_address = NULL;
  private $address = NULL;
  private $HuanQiuMeiTaoApi = NULL;
  public function _initialize() {
    !is_null($this -> Common ) ? : $this -> Common = new Common();//实例化公共函数库;
    !is_null($this -> user_address ) ? : $this -> user_address =  D('user_address');
    !is_null($this -> user ) ? : $this -> user =  D('user');
    $raw_data=file_get_contents("php://input");
    $data = json_decode($raw_data);
    $address=$data->address;//微信用户的基础信息
    //$this -> Common->dp($address);die;
    //$Common->dp($address);die;
    //print_r($this -> user_address);die;
   //  $action=$_REQUEST['act'];
   //$raw_data=file_get_contents("php://input");
    //$data=$_REQUEST;
   //$data = json_decode($raw_data);
    //print_r($data);die;
   //  $userInfo=$data->userInfo;//微信用户的基础信息
    //$get = I('get.');

    //$this-> goods_id =$get['goods_id'];//用户小程序id $data->wid
    //print_r( $this-> goods_id);die;
    $this-> uid =$data->uid;//用户小程序id $data->wids
    $this-> shop_id =$data->shop_id;//用户小程序id $data->wids
    $this-> address_id =$data->address_id;//用户小程序id $data->wids
    $this-> address = $data->address;//微信用户的基础信息;//用户小程序id $data->wids

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
    $Common = $this -> Common;
    $uid = $this -> uid;
    $consignee=$Common->get_all_consignee($uid);
    if($consignee){
        $data = array(
            'consignee'=>$consignee,
            'msg'=>'用户有地址',
            'code'=>1,
        );
    }else{
        $data = array(
            'consignee'=>$consignee,
            'msg'=>'用户没有收货地址',
            'code'=>0,
        );
    }
    echo json_encode($data);
  }
    /*添加地址*/
  public function add_consignee(){
    $Common = $this -> Common;
    $user_address = $this-> user_address;
    $address = $this-> address;
    $user=$this->user;
    $uid = $this -> uid;//
    if($uid){
          $consignee =$Common-> get_default_consignee($uid);//查询收货地址
          //$Common->dp($consignee);die;
          if($consignee){
              $arr = array(
                'uid'           =>$uid,
                'consignee'     =>trim($address->userName),
                'country'       =>trim(1),
                'province'      =>trim( $address->provinceName),
                'city'          =>trim($address->cityName),
                'district'      =>trim( $address->countyName),
                'address'       =>trim( $address->detailInfo),
                'tel'           =>trim($address->telNumber),
                'add_time'      =>time(),
              );
              $where['aid']=$consignee['aid'];
              $res  = $user_address->where($where)->data($arr)->save();
              $arr['aid'] = $where['aid'];
              //echo $user_address->_sql();die;
              if($res){
                    $data = array(
                        'code'=>1,
                        'consignee'=>$arr,
                        'msg'=>'更新地址成功',
                    );
                }else{
                    $data = array(
                         'code'=>0,
                          'msg'=>'更新地址失败',
                    );
                }          
          }else{
             $consignee = array(
              'uid'           =>$uid,
              'consignee'     =>trim($address->userName),
              'country'       =>trim(1),
              'province'      =>trim( $address->provinceName),
              'city'          =>trim($address->cityName),
              'district'      =>trim( $address->countyName),
              'address'       =>trim( $address->detailInfo),
              'tel'           =>trim($address->telNumber),
              'add_time'      =>time(),
            );
            $res  = $user_address->data($consignee)->add();
            $consignee['aid'] = $res;
            //$Common->dp($res);die;
            $arr['wx_address_id']= $res;
            $where['uid'] =$uid;
            $result = $user->where($where)->data($arr)->save();
           // echo $result;die;
            if($res&&$result){
                  $data = array(
                      'code'=>1,
                      'consignee'=>$consignee,
                      'msg'=>'添加地址成功',
                  );
              }else{
                  $data = array(
                       'code'=>0,
                        'msg'=>'添加地址失败',
                  );
              } 
        }
    }else{
       $data = array(
          'code'=>2,
          'msg'=>'uid为空！',
        ); 
    }
     echo json_encode($data);
  }
  
   
  /*编辑收货地址*/
  public function edit_consignee() {
    $Common = $this -> Common;
    $user_address = $this->user_address;
   
    $uid = $this -> uid;
    $address = $this -> address;
    $address_id = $this-> address_id;
    $where['aid']=$address_id;

    $consignee = array(
      'uid'           =>$uid,
      'consignee'     =>trim($address->userName),
      'country'       =>trim(1),
      'province'      =>trim( $address->provinceName),
      'city'          =>trim($address->cityName),
      'district'      =>trim( $address->countyName),
      'address'       =>trim( $address->detailInfo),
      'tel'           =>trim($address->telNumber),
      'add_time'      =>time(),
  ); 
  //    $consignee = array(
  //     'uid'           =>$uid,
  //     'consignee'     =>'淘',
  //     'country'       =>trim(1),
  //     'province'      =>"广东",
  //     'city'          =>"广州",
  //     'district'      =>"天河区",
  //     'address'       =>"dsdgs",
  //     'tel'           =>"15360806016",
  //     'add_time'      =>time(),
  // );
  $res = $user_address->where($where)->data($consignee)->save();
  if($res){
        $data = array(
            'code'=>1,
            'consignee'=>$consignee,
            'msg'=>'编辑地址成功',
        );
    }else{
        $data = array(
             'code'=>0,
              'msg'=>'编辑地址失败',
        );
    }
    echo json_encode($data);
  }

/*选择默认收货地址*/
public function select_consignee()
{
    $Common = $this -> Common;
    $uid = $this -> uid;
    $address_id = $this-> address_id;
    $user = $this-> user;
    $user[wx_address_id] = $address_id;
    $where = 'uid ='.$uid;
    $res = $user->where($where)->data($user)->save();
    if($res){
        $data = array(
            'code'=>1,
            'msg'=>'选择地址成功',
        );
    }else{
       $data = array(
            'code'=>0,
            'msg'=>'选择地址失败',
        );
        
    }
  echo json_encode($data);
}
/*删除收货地址*/
public function del_consignee()
{
    $Common = $this -> Common;
    $user_address = $this-> user_address;
    $uid = $this -> uid;
    $address_id = $this-> address_id;
    $user = $this-> user;
    $where[aid] = $address_id;
    $where[uid] = $uid;
    $res = $user_address->where($where)->delete();
    if($res){
        $data = array(
            'code'=>1,
            'msg'=>'删除地址成功',
        );
        
    }else{
       $data = array(
            'code'=>0,
            'msg'=>'删除地址失败',
        );
        
    }
  echo json_encode($data);
}



}
