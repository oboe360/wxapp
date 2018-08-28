<?php
namespace Home\Controller;
header('content-type:text/html;charset=utf-8');
header("Access-Control-Allow-Origin:*");
use Think\Controller;
use think\Db;
use Think\Common;
/*引流小程序接口  个人用户订单管理接口 2018-7-10 by tao*/
class OrderInfoController extends Controller {
  private $Common = NULL;
  private $uid = NULL;
  private $user = NULL;
  private $order = NULL;
  private $goods = NULL;
  public function _initialize() {
    !is_null($this -> Common ) ? : $this -> Common = new Common();//实例化公共函数库;
    !is_null($this -> order ) ? : $this -> order =  D('order_info');
    !is_null($this -> goods ) ? : $this -> goods =  D('goods');
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
    $this-> order_id =$_REQUEST['order_id'];//用户小程序id 
    $this-> order_type =$_REQUEST['order_type'];//
    $this-> invoice_sn =$_REQUEST['invoice_sn'];//
    $this-> address =$_REQUEST['address'];

  }
  /*订单列表数据*/
  public function Index() {
    $user = $this -> user;
    $Common = $this -> Common;
    $uid = $this -> uid;
    $order_type = $this-> order_type;
    $orderlist=$Common->order_info($uid,$order_type);
    //$Common->dp($orderlist);die;
    if($orderlist){
        $data = array(
            'orderlist'=>$orderlist,
            'msg'=>'返回成功',
            'code'=>1,
        );
    }else{
        $data = array(
            'orderlist'=>$orderlist,
            'msg'=>'暂无数据',
            'code'=>0,
        );
    }
    echo json_encode($data);
  }

 /*订单详情数据*/
  public function order_detail() {
    $Common = $this -> Common;
    $uid = $this -> uid;
    $order_id = $this -> order_id;
    $orderdata=$Common->order_detail($uid,$order_id);
    //$Common->dp($orderdata);die;
    if($orderdata['orderarr']){
        $data = array(
            'orderdata'=>$orderdata,
            'msg'=>'返回成功',
            'code'=>1,
        );
    }else{
        $data = array(
            'orderlist'=>$orderlist,
            'msg'=>'暂无数据',
            'code'=>0,
        );
    }
    echo json_encode($data);
  }
  /*确认收货*/
  public function order_update() {
    $Common = $this -> Common;
    $uid = $this -> uid;
    $order_id = $this -> order_id;
    $res=$Common->order_update($uid,$order_id);
    //$Common->dp($orderdata);die;
    if($res){
        $data = array(
            'msg'=>'收货成功',
            'code'=>1,
        );
    }else{
        $data = array(
            'orderlist'=>$orderlist,
            'msg'=>'收货失败',
            'code'=>0,
        );
    }
    echo json_encode($data);
  }
  /*修改收货地址*/
  public function update_address() {
      $arr = str_replace('&quot;', '"', $_REQUEST);
      $address = $arr['address'];
      $address = json_decode($address,true);
      //print_r($address->userName);die;
    // $data = json_decode($raw_data,true);
    // $address = $data->address;//微信用户的基础信息

     //print_r($raw_data);die;
    $Common = $this -> Common;
    $uid =$address['uid'];
    //print_r($uid);die;
    $order_id = $address['order_id'];
    $res=$Common->update_address($uid,$order_id,$address);
    //$Common->dp($orderdata);die;
    if($res){
        $data = array(
            'msg'=>'编辑成功',
            'code'=>1,
        );
    }else{
        $data = array(
            'msg'=>'编辑失败',
            'code'=>0,
        );
    }
    echo json_encode($data);
  }
 /*查询物流信息*/
  public function check_invoice_sn5() {
    $Common = $this -> Common;
    $uid = $this -> uid;
    $order_id = $this -> order_id;
    $type = $Common->shipping_code($order_id);
    $invoice_sn = $this -> invoice_sn;
    $query_link = $Common->kuaidi100($invoice_sn,$type['shipping_code']);
   //dump($query_link);die;
     if (function_exists('curl_init') == 1){
      $curl = curl_init();
      //dump($curl);die;
      curl_setopt ($curl, CURLOPT_URL, $query_link);
      curl_setopt ($curl, CURLOPT_HEADER, 0);
      curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt ($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
      curl_setopt ($curl, CURLOPT_TIMEOUT, 5);
      $get_content = curl_exec($curl);
      //dump( $get_content);die;
      curl_close ($curl);
    }

    $data['agent_content'] =  json_decode($get_content,ture);
    $data['agent_content']['shipping_name'] = $type['shipping_name'];
  //$Common->dp($data['agent_content']);die;
    if($data['agent_content']['message']=="ok"){
        $data['code']=1;
        $data['agent_content']['invoice_sn']=$this -> invoice_sn;
        $data['agent_content']['shipping_name']=$type['shipping_name'];
        $data['msg']="查询快递信息成功";
    }else{
        $data['code']=0;
        $data['agent_content']['invoice_sn']=$this -> invoice_sn;
        $data['agent_content']['shipping_name']=$type['shipping_name'];
        $data['agent_content']['data']=array();
        $data['msg']="暂无快递信息";
    }
   //dump($data);die;
    echo json_encode($data);
    exit;
  }

  /*查询物流信息*/
  public function check_invoice_sn() {
    $Common = $this -> Common;
    $uid = $this -> uid;
    $order_id = $this -> order_id;
    $type = $Common->shipping_code($order_id);
    $invoice_sn = $this -> invoice_sn;
    //$query_link = $Common->kuaidi100($invoice_sn,$type['shipping_code']);
   //dump($invoice_sn);die;
    $host = "http://goexpress.market.alicloudapi.com";
    $path = "/goexpress";
    $method = "GET";
    $appcode = "4fbdb3dbdd5e41b380c076b6ab9e2be1";
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "no=".$invoice_sn."&type=YD";
    $bodys = "";
    $url = $host . $path . "?" . $querys;
    //print_r( $url);die;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    //curl_setopt($curl, CURLOPT_HEADER, true); 如不输出json, 请打开这行代码，打印调试头部状态码。
    //状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
    //dump(555);die;
    //if (1 == strpos("$".$host, "https://"))
    //{
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        //echo(curl_exec($curl)); die;
        $get_content = curl_exec($curl);
       
        curl_close ($curl);
    //}

    $data['agent_content'] =  json_decode($get_content,ture);

    $data['agent_content']['shipping_name'] =  $data['agent_content']['name'];
    //$data['agent_content']['data'] =  $data['agent_content']['list'];
     //$Common->dp( $data['agent_content']['code']);die;
    foreach ($data['agent_content']['list'] as $k => $v) {
        $data['agent_content']['data'][$k]['context']=$v['content'];
        $data['agent_content']['data'][$k]['time']=$v['time'];
    }
    if($data['agent_content']['code']=="OK"){
        $data['code']=1;
        $data['agent_content']['invoice_sn']=$this -> invoice_sn;
        $data['agent_content']['shipping_name']=$type['shipping_name'];
        $data['msg']="查询快递信息成功";
    }else{
        $data['code']=0;
        $data['agent_content']['invoice_sn']=$this -> invoice_sn;
        $data['agent_content']['shipping_name']=$type['shipping_name'];
        $data['agent_content']['data']=array();
        $data['msg']="暂无快递信息";
    }
   //$Common->dp( $data);die;
    echo json_encode($data);
    exit;
  }

}
