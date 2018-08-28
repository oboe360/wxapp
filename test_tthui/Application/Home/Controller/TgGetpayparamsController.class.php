<?php
namespace Home\Controller;
header('content-type:text/html;charset=utf-8');
// header("Access-Control-Allow-Origin:*");
// header('content-type:application/x-www-form-urlencoded');
use Think\Controller;

class TgGetpayparamsController extends Controller {
    private $orderinfo = NULL;
    private $wxConfig = NULL;
    private $Common = NULL;
    private $user = NULL;
    private $otherConfig = NULL;
    //公众账号ID  
    private $appid;
    //小程序的 app secret  
    private $appsecret;  
    //商户号  
    private $mch_id; 
    //通莞支付参数获取接口  
    private $tg_url;
    //通莞account商户进件手机号  
    private $tg_account; 
    // 通莞key
    private $tg_key; 
    private $result = array();//支付结果回调应答
    public function _initialize() {     
        !is_null($this -> orderinfo) ? : $this -> orderinfo = M('OrderInfo');
        !is_null($this -> wxConfig) ? : $this -> wxConfig = M('wxConfig');
        !is_null($this -> user) ? : $this -> user = M('user');
        $this->tg_url = C('TG_URL');
        $this->tg_account = C('TG_ACCOUNT');
        $this->tg_key = C('TG_KEY');
        //获取支付参数配置，实例化支付类
        $this->getWxconfig();
    }

    /** 
     * 获取支付参数统一接口 
     * @param   $orderList 订单详情
     * @param   $code 前端识别code码
     */ 
    public function tg_getPayParam($orderList='',$code=''){

        //获取用户openid
        $openid = $this->user->where(array('uid'=>$orderList['user_id']))->getField('openid');
        // echo $this->user->_sql();
        // echo $openid;
        // exit;
        if(!$openid){
            $code = $code?:I('param.code');
            $res = $this->getOpenid($code);
            if($res['openid']){
                // header('Location:'.U('home/index/getPayParams').'?openid='.$res['openid']);
                $openid = $res['openid'];
            }else{
                echo json_encode(array('code'=>0,'msg'=>'openid获取失败'));
                exit;
            }
            // echo json_encode($res);
        }
        $data = array();
        //设置请求参数
        $data['body'] = '商品订单金额扣费';//金额明细
        $data['lowOrderId'] = strval($orderList['order_sn']);//订单流水号
        $data['payMoney'] = strval($orderList['order_amount']);//订单金额
        $data['openId'] = $openid;//唯一识别id
        $data['isMinipg'] = '1';//是否为小程序
        $data['notifyUrl'] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].U('home/TgGetpayparams/tg_wxpay_notify/log_id/'.$orderList['log_id']);//支付结果回调通知
        // echo json_encode($data);
        // exit;
        $result = $this->tg_collection($data);
        // echo json_encode($result);
        // exit;
        if($result['status'] == 101 || empty($result) || empty($result['status'])){
            $Getpayparams = A('Getpayparams');
            $Getpayparams->getPayParam($orderList);
            $array = Array('code'=>BRANDNAME,'content'=>'原生');
            $smss = "sms_pay";
            $res = $this->sendsms(C('TG_ERROR_PHONE'), $smss, $array);
            // dump($res);
            // exit;
            exit;
            // echo json_encode(array('code'=>0,'msg'=>$result['message']));
            // exit;
        }
        $sign = $result['sign'];
        unset($result['sign']);
        $check_sign = $this->MakeSign($result);
        if($check_sign !== $sign){
            echo json_encode(array('code'=>0,'msg'=>'sign签名错误'));
            exit; 
        }
        //拼接参数发送前端
        $param = json_decode($result['pay_info'],true);
        // $param['paySign'] = strval($this->MakeSign($param));
        $param['order_sn'] = $orderList['order_sn'];//订单流水号
        $param['shop_id'] = $orderList['shop_id'];//订单流水号
        $param['uid'] = $orderList['user_id'];//订单流水号
        $param['goods_id'] = $orderList['goods_id'];//礼包商品ID
        // $param['openid'] = I('param.openid');

        // $param['aa'] = $data['notify_url'];
        $returnRes = array('code'=>1,'msg'=>'参数获取成功','data'=>$param);
        // dump($returnRes);
        echo json_encode($returnRes);
        return ;
    }
    //通莞代收接口
    private function tg_collection($params){
        $data = array();
        $data['account'] = $this->tg_account;
        $data['payMoney'] = $params['payMoney'];
        $data['lowOrderId'] = $params['lowOrderId'];
        $data['body'] = $params['body'];
        $data['isMinipg'] = $params['isMinipg'];
        $data['notifyUrl'] = $params['notifyUrl'];
        $data['openId'] = $params['openId'];
        $data['appId'] = $this->appid;
        //设置签名
        $data['sign'] = $this->MakeSign($data);
        // echo json_encode($data);
        // exit;
        //存入session
        // session('sign',$params['sign']);
        //请求获取支付参数
        $res = $this->tg_http_request($this->tg_url, $data);
        return json_decode($res,true);
    }
    /**
     * 会员礼包支付成功后的回凋
     * @access  public
     * @date    2018-7-12
     */
    public function tg_wxpay_notify(){
        
        !is_null($this -> Common ) ? : $this -> Common = new \Think\Common();//实例化公共函数库;
        // $bool = $this->Common->vippay_respond('866','nwt2018080761719323227','1223','1');
        // dump($bool);
        // exit;
        // $this->Common->vippay_respond(1,123);
        // exit;
        $log_type = 'result.txt';  
        //验证通知结果
        $data = json_decode($GLOBALS['HTTP_RAW_POST_DATA'],true);
         
        if($data['state'] === '0'){
            
           //此处应该更新一下订单状态  
           $bool = $this->Common->vippay_respond($_REQUEST['log_id'],$data['lowOrderId'],$data['upOrderId'],'1');
           // file_put_contents($log_type, "【支付结果回调成功！】:\n".date('Y-m-d H:i:s').json_encode($data).json_encode($_REQUEST),FILE_APPEND);
           if($bool){
                echo 'SUCCESS';
                exit;
            }
        }else{
            //此处应该更新一下订单状态，商户自行增删操作  
            file_put_contents($log_type, json_encode($data)."【支付结果回调失败！】:\n".date('Y-m-d H:i:s'),FILE_APPEND);  
        }
    }
    //获取支付授权信息
    private function getWxconfig($type=0){
        $list = $this->wxConfig->where(array('type'=>$type))->find();
        if(empty($list)){
            $data = array('code'=>0,'msg'=>'微信支付配置失败');
            echo json_encode($data);
            exit;
        }
        $this->appid = $list['appid'];
        $this->appsecret = $list['appsecret'];
        $this->mch_id = $list['wxmchid'];
        // $list = array('appid'=>'wx79cf6cb06886c5ee','appsecret'=>'5f61d7f65b184d19a1e006bc9bfb6b2f','wxkey'=>'5f61d7f65b184d19a1e006bc9bfb6b2f','wxmchid'=>'13974747474');
    }
    /** 
     * 生成签名 
     *  @return 签名 
     */  
    private function MakeSign($params){
        //签名步骤一：按字典序排序数组参数  
        ksort($params);  
        $string = $this->ToUrlParams($params);  
        //签名步骤二：在string后加入KEY  
        $string = $string . "&key=".$this->tg_key;  
        // echo $string;
        //签名步骤三：MD5加密  
        $string = md5($string);  
        // dump($string);
        //签名步骤四：所有字符转为大写  
        $result = strtoupper($string);  
        return $result;  
    }  


    /** 
     * 将参数拼接为url: key=value&key=value 
     * @param   $params 
     * @return  string 
     */  
    private function ToUrlParams( $params ){  
        $string = '';  
        if( !empty($params) ){  
            $array = array();  
            foreach( $params as $key => $value ){  
                if($value){
                    $array[] = $key.'='.$value;
                }     
            } 
            // dump($array); 
            $string = implode("&",$array);  
        }  
        return $string;  
    }
    
    /**
     * 通过CURL发送HTTP请求
     * @param string $url  //请求URL
     * @param array $postFields //请求参数json
    */
    private function tg_http_request($url,$myParams){
        //设置请求头
        $headers = array();
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Content-Type: application/json; charset=utf-8';
        $curlobj = curl_init();   // 初始化  
        curl_setopt($curlobj, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlobj,CURLOPT_URL,$url);   //设置访问网页的URL  
        curl_setopt($curlobj,CURLOPT_RETURNTRANSFER,TRUE);//执行之后不直接打印出来
        curl_setopt($curlobj, CURLOPT_HEADER, false); 
        curl_setopt($curlobj, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curlobj, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curlobj, CURLOPT_POST, 1);
        curl_setopt($curlobj, CURLOPT_POSTFIELDS, json_encode($myParams));
        curl_setopt($curlobj, CURLOPT_FOLLOWLOCATION, 1);
        $out_put = curl_exec($curlobj);
        curl_close($curlobj);  
        return $out_put;
    }
    //发送短信
    private function sendsms($phone, $smss = '',$array = ''){
        require_once ('SmsApi.php');
        //获取对应的配置信息
        !is_null($this -> otherConfig) ? : $this -> otherConfig = M('otherConfig');
        $parent_id = $this->otherConfig->where(array('parent_id'=>0,'key'=>'sms'))->getField('id');
        //根据parent_id获取对应的配置值
        $list = $this->otherConfig->where(array('parent_id'=>$parent_id,'_string'=>'`key`="'.$smss.'" OR `key`="sms_ak" OR `key`="sms_as" OR `key`="sms_sign"'))->select();
        $sms = new SmsApi($list[1]['value'], $list[2]['value']);
        foreach ($phone as $value) {
            $response = $sms->sendSms(
                $list[3]['value'], // 短信签名
                $list[0]['value'], // 短信模板编号
                $value, // 短信接收者
                // Array (  // 短信模板中字段的值
                //     "code"=>$code
                    
                // ),
                $array,
                "123"   // 流水号,选填
            );
        }
        
        // dump($response);
        // dump($smss);
        // exit;
        // return $response;
        if($response->Code == 'OK'){
            return true;
        }else{
            return false;
        }
    } 
}
