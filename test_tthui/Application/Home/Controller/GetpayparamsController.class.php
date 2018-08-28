<?php
namespace Home\Controller;
// header('content-type:text/html;charset=utf-8');
// header("Access-Control-Allow-Origin:*");
header('content-type:application/x-www-form-urlencoded');
use Think\Controller;
class GetpayparamsController extends Controller {
    private $orderinfo = NULL;
    private $wx = NULL;
    private $wxConfig = NULL;
    private $Common = NULL;
    private $user = NULL;
    private $result = array();//支付结果回调应答
    public function _initialize() {     
        !is_null($this -> orderinfo) ? : $this -> orderinfo = D('OrderInfo');
        !is_null($this -> wxConfig) ? : $this -> wxConfig = D('wxConfig');
        !is_null($this -> user) ? : $this -> user = D('user');
        //获取支付参数配置，实例化支付类
        $this->getWxconfig();
    }

    /** 
     * 获取支付参数统一接口 
     * @param   $orderList 订单详情
     * @param   $code 前端识别code码
     */ 
    public function getPayParam($orderList='',$code=''){
        //获取支付订单id
        // $order_id = I('param.order_id')?:28;
        //查询获取订单数据
        // $orderList = $this->orderinfo->find($order_id);
        // echo json_encode($orderList);
        // exit;
        //获取用户openid
        $openid = $this->user->where(array('uid'=>$orderList['user_id']))->getField('openid');
        // echo $this->user->_sql();
        // echo $openid;
        // exit;
        if(!$openid){
            $code = $code?:I('param.code');
            $res = $this->wx->getOpenid($code);
            // dump($res);
            // echo U('home/index/index').'?openid='.$res['openid'];
            // exit;
            // dump($_SESSION);
            if($res['openid']){
                // header('Location:'.U('home/index/getPayParams').'?openid='.$res['openid']);
                $openid = $res['openid'];
            }else{
                echo json_encode(array('code'=>0,'msg'=>'openid获取失败'));
                exit;
            }
            // echo json_encode($res);
        }
        
        //调取统一下单接口
        //拼接请求参数
        $data = array();
        $data['body'] = '商品订单金额扣费';//金额明细
        $data['out_trade_no'] = $orderList['order_sn'];//订单流水号
        $data['total_fee'] = round($orderList['order_amount'],2)*100;//订单金额
        $data['trade_type'] = 'JSAPI';//支付类型
        $data['openid'] = $openid;//唯一识别id
        $data['attach'] = strval($orderList['log_id']);//记录log_id
        $data['notify_url'] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].U('home/Getpayparams/wxpay_notify');//支付结果回调通知
        $result = $this->wx->unifiedOrder($data);
        // echo json_encode($data);
        
        // exit;
        if(empty($result['prepay_id'])){
            echo json_encode(array('code'=>0,'msg'=>'prepay_id获取失败'));
            exit;
        }
        //拼接参数发送前端
        $param = array(
            'timeStamp'=>strval(time()),
            'nonceStr'=>$result['nonce_str'],
            // 'paySign'=>$result['sign'],
            'signType'=>'MD5',
            'package'=>'prepay_id='.$result['prepay_id'],
            'appId' => $this->wx->appid,
        );
        $param['paySign'] = strval($this->wx->MakeSign($param));
        $param['order_sn'] = $orderList['order_sn'];//订单流水号
        $param['shop_id'] = $orderList['shop_id'];//订单流水号
        $param['uid'] = $orderList['user_id'];//订单流水号
        $param['goods_id'] = $orderList['goods_id'];//礼包商品ID
        // $param['openid'] = I('param.openid');
        unset($param['appId']);

        // $param['aa'] = $data['notify_url'];
        $returnRes = array('code'=>1,'msg'=>'参数获取成功','data'=>$param);
        // dump($returnRes);
        echo json_encode($returnRes);
        return ;
        // //2.创建APP端预支付参数  
        // /** @var TYPE_NAME $result */  
        // $data = @$wechatAppPay->getAppPayParams($result['prepay_id']);  
        // //下边为了拼够参数，多拼了几个给安卓、ios前端  
        // $data['body'] = '充值';  
        // $data['notify_url'] = $notify_url;  
        // $data['total_fee'] = 1;  
        // $data['success'] = 1;  
        // $data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];  
        // $data['out_trade_no'] = $this->ordersn();  
        // $data['trade_type'] = 'JSAPI';  
        // //var_dump($data);die();  
        // // 根据上行取得的支付参数请求支付即可  
        // //print_r($data);  
        // BASETOOL::ajaxResponse($this->error['RETURN_SUCCESS']['code'], $data);
    }
    /**
     * 会员礼包支付成功后的回凋
     * @access  public
     * @date    2018-7-12
     */
    public function wxpay_notify(){
        // dump(I('param.'));
        // exit;
        !is_null($this -> Common ) ? : $this -> Common = new \Think\Common();//实例化公共函数库;
        // $this->Common->vippay_respond(1,123);
        // exit;
        $log_type = 'result.txt';  
        //验证通知结果
        $data = $this->checkSign();
        if (!$data) {
            if ($this->result["return_code"] == "FAIL") {  
                //此处应该更新一下订单状态，商户自行增删操作  
                file_put_contents($log_type, "【支付结果回调失败！】:\n".date('Y-m-d H:i:s'),FILE_APPEND);  
            }  
        }else{
            
           //此处应该更新一下订单状态  
           $bool = $this->Common->vippay_respond($data['attach'],$data['out_trade_no']);
           // file_put_contents($log_type, "【支付结果回调成功！】:\n".date('Y-m-d H:i:s').json_encode($data).json_encode($_REQUEST),FILE_APPEND);
           if($bool){
                echo $this->wx->data_to_xml($this->result);
                exit;
            }
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
        // $list = array('appid'=>'wxb6a9ac30178e85a0','appsecret'=>'6e27531f423778cce0e672cce65e756f','wxkey'=>'yoLTfXNrRQcuW0lmYxcNipv77HfnffhW','wxmchid'=>'1300847301');
        !is_null($this -> wx) ? : $this -> wx = new \Home\Controller\WechatAppPay($list['appid'], $list['appsecret'], $list['wxmchid'],$list['wxkey']);
    }
    //验证支付结果
    private function checkSign(){
        //获取通知的数据  
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];  
        if(empty($xml)){
            $this->result = array('return_code'=>'FAIL');  
            return false;  
        }  
        $data = $this->wx->xml_to_data($xml);  
        if( !empty($data['return_code']) ){  
            if( $data['return_code'] == 'FAIL' ){
                $this->result = array('return_code'=>'FAIL');   
                return false;  
            }  
        }
        $this->result = array('return_code'=>'SUCCESS','return_msg'=>'OK');
        return $data;
    }
}
