<?php
namespace Home\Controller\tg_wechat_pay;
header('content-type:text/html;charset=utf-8');
// header("Access-Control-Allow-Origin:*");
// header('content-type:application/x-www-form-urlencoded');
use Think\Controller;
class GetpayparamssController extends Controller {
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
    public function tg_getPayParam($orderList='',$code=''){
        //获取支付订单id
        // $order_id = I('param.order_id')?:28;
        //查询获取订单数据
        // $orderList = $this->orderinfo->find($order_id);
        // echo json_encode($orderList);
        // exit;
        //获取用户openid
        $openid = $this->user->where(array('uid'=>$orderList['user_id']))->getField('openid')?:'oV9tV48vaqEm-qCZZuIwgsh6zRsM';
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
        //设置请求参数
        $params = array(
            'payMoney'=>'0.01',
            'lowOrderId'=>strval(mt_rand()),
            'body'=>'接口测试',
            'isMinipg'=>'1',
            'notifyUrl'=>strval($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].U('home/Getpayparams/tg_wxpay_notify')),
            'openId'=>$openid,
        );
        $result = $this->wx->tg_collection($params);
        dump($result);
        exit;
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
    }
    /**
     * 会员礼包支付成功后的回凋
     * @access  public
     * @date    2018-7-12
     */
    public function tg_wxpay_notify(){
        file_put_contents('tg.txt',json_encode(I('param.'))."\n",FILE_APPEND);
        file_put_contents('tg.txt',session('sign')."\n",FILE_APPEND);
        // dump(I('param.'));
        exit;
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
        // $list = array('appid'=>'wx79cf6cb06886c5ee','appsecret'=>'5f61d7f65b184d19a1e006bc9bfb6b2f','wxkey'=>'5f61d7f65b184d19a1e006bc9bfb6b2f','wxmchid'=>'13974747474');
        !is_null($this -> wx) ? : $this -> wx = new \Home\Controller\tg_wechat_pay\WechatAppPays($list['appid'], $list['appsecret'], $list['wxmchid']);
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
