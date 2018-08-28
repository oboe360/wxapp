<?php
namespace Home\Controller;
header('content-type:text/html;charset=utf-8');
// header("Access-Control-Allow-Origin:*");
use Think\Controller;
use think\Db;
use Think\Common;
/*引流小程序接口  用户授权管理接口 2018-7-10 by tao*/
class LoginController extends Controller {
  private $Common = NULL;
  private $uid = NULL;
  private $user = NULL;
  private $wx_config = NULL;
  private $other_config = NULL;
  private $history = NULL;
  private $store = NULL;
  private $Marketingprogram = NULL;
  public function _initialize() {
    !is_null($this -> Common ) ? : $this -> Common = new Common();//实例化公共函数库;
    // !is_null($this -> Marketingprogram ) ? : $this -> Marketingprogram = A('Marketingprogram');//实例化营销方案表;
    !is_null($this -> wx_config ) ? : $this -> wx_config =  D('wx_config');
    !is_null($this -> other_config ) ? : $this -> other_config =  D('other_config');
    !is_null($this -> user ) ? : $this -> user =  D('user');
    !is_null($this -> history ) ? : $this -> history =  D('history');
    !is_null($this -> store ) ? : $this -> store =  D('the_store');
   // file_put_contents('result.txt', json_encode($_REQUEST).json_encode($_GET).json_encode($_POST).json_encode(I('param.')).json_encode($_SERVER));
    //$_REQUEST=I('param.');

    //print_r($_REQUEST);die;
   //$action=$_REQUEST['act'];
    $raw_data=file_get_contents("php://input");
    //过滤掉符号导致字符的bug
    $rawa_data =  str_replace("'", '', $raw_data);
    $data = json_decode($rawa_data);
    $this-> userInfo=$data->userInfo;//微信用户的基础信息
    $this-> code = $data->code;//登录凭证 code
    $this-> uid = $data->uid;//用户小程序id
    $this-> shop_id = $data->shop_id;//拓客宝标识id;
  }

  /*收货地址列表数据*/
  public function login(){
      $user = $this -> user;
      $Common = $this -> Common;
      $config=$this -> wx_config;
      $history=$this -> history;
      $userInfo=$this -> userInfo;
      $code=$this -> code;
      $uid=$this -> uid?:'false';
      $shop_id= $this -> shop_id;//
      $store=$this -> store;
      // dump($store);
      // exit;
     // 
       // $this ->Common->file_put("login2.txt",$this -> uid);
      if($shop_id != '' && $shop_id != 'undefined'){//查询店铺ID是否存在
          //$twh['id'] = $shop_id;
           //dump($shop_id);die;
          $twh['id'] = $shop_id;
          $row = $store->field('id')->where($twh)->find();
      
         // exit;
          $is_shop_id =  $row['id'];//
          if(empty($is_shop_id)){//如果拓客宝ID不存在默认为1
              //  $data = array( 
              //     'code'=>0,
              //     'shop_id'=>'',
              //     'msg'=>'该店铺ID不存在！',
              //     );   
       
              // echo json_encode($data);
              // exit();
            $shop_id = $this->getDefaultShopId();
          }
    }
    $row = $config->field('appid,appsecret')->limit(1)->find();
    //换成自己的s接口信息
    $appid = trim($row['appid']);
    $appsecret = trim($row['appsecret']);
    $token_url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$code.'&grant_type=authorization_code';
  //$Common->dp($appid);die;

    $token = json_decode(file_get_contents($token_url));
   // 
    if(isset($token->errcode)){
         $data = array(
                'code'=>0,
                'shop_id'=>'',
                'msg'=>'获取openid失败',
                );
         //$Common->dp($data);die;
        echo json_encode($data);
        exit;
    }
    $openid = $token->openid;//获取openid
    $session_key = $token->session_key;//会话密钥session_key
    $where['openid']=$openid;
    $wxuser = $user->where($where)->find();
    
    //判断是否为第一次进入小程序
    if(empty($wxuser)){
      //如果店铺ID不存在，跳广告页
      if(empty($shop_id)){
        // $data = array( 
        //           'code'=>0,
        //           'shop_id'=>"",
        //           'msg'=>'更新用户信息失败，直接跳广告页',
        //         );
        // echo json_encode($data);
        // exit;
        $shop_id = $this->getDefaultShopId();
      }
      //获取配置信息,执行指定营销方案  
      $function = $this->other_config->where(array('key'=>'marketing_program'))->getField('value');
      // dump($this->other_config);
      // dump($this->other_config->fetchsql()->where(array('key'=>'marketing_program'))->getField('value'));
      // exit;
      $function = json_decode($function, true);

      firstIntoApp($shop_id, $wxuser, $userInfo, $Common, $uid, $history, $user, $openid, $session_key,$function);
    }else{
      if(empty($wxuser['shop_id']) && (empty($shop_id) || $shop_id == 'undefined')){
          $shop_id = $this->getDefaultShopId();
      }
      //获取配置信息,执行指定营销方案  
      $wxuser['bind_shop'] != 1 ?: $shop_id = 0;
      $wxuser['bind_sj_user'] != 1 ?: $uid = 0;
      
      // $function = $this->other_config->where(array('key'=>'marketing_program'))->getField('value'); 
      // $this -> Marketingprogram ->$function($shop_id, $wxuser, $Common, $uid, $history, $user); 
      secondIntoApp($shop_id, $wxuser, $Common, $uid, $history, $user);
    }
    
  }
  //获取默认店铺ID
  private function getDefaultShopId(){  
      $shop_id = $this->other_config->where(array('key'=>'default_shop_id'))->getField('value');
      // file_put_contents('result.txt', $shop_id, FILE_APPEND);
      if(empty($shop_id)){
        $data = array( 
                  'code'=>0,
                  'shop_id'=>"",
                  'msg'=>'没有默认店，直接跳广告页',
                );
        echo json_encode($data);
        exit;
      }
      return $shop_id;
  }
  /*重新请求*/
  public function re_login(){
    $user = $this -> user;
    $_REQUEST=I('param.');
    $user_id = $_REQUEST['user_id'];
    // $this ->Common->file_put("login3.txt",$user_id);
    $where['uid']=$user_id;
    $wxuser = $user->where($where)->find();
    $data = array(
              'code'=>1,
              'uid'=> $wxuser['uid'],
              'user_rank'=> $wxuser['user_rank'],
              'nickname'=> $wxuser['nickname'],
              'shop_id'=>$wxuser['shop_id'],
              'msg'=>'获取信息成功',
          );
      echo json_encode($data);
      exit;
  }
}
