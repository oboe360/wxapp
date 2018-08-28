<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
/** 
     * 微信支付服务器端下单 
     * 微信APP支付文档地址:  https://pay.weixin.qq.com/wiki/doc/api/app.php?chapter=8_6 
     * 使用示例 
     *  构造方法参数 
     *      'appid'     =>  //填写微信分配的公众账号ID 
     *      'mch_id'    =>  //填写微信支付分配的商户号 
     *      'notify_url'=>  //填写微信支付结果回调地址 
     *      'key'       =>  //填写微信商户支付密钥 
     *  ); 
     *  统一下单方法 
     *  $WechatAppPay = new wechatAppPay($options); 
     *  $params['body'] = '商品描述';                   //商品描述 
     *  $params['out_trade_no'] = '1217752501201407';   //自定义的订单号，不能重复 
     *  $params['total_fee'] = '100';                   //订单金额 只能为整数 单位为分 
     *  $params['trade_type'] = 'APP';                  //交易类型 JSAPI | NATIVE |APP | WAP  
     *  $wechatAppPay->unifiedOrder( $params ); 
     */  
    class WechatRefundController  extends BaseController
    {     
        //接口API URL前缀  
        const API_URL_PREFIX = 'https://api.mch.weixin.qq.com';  
        //下单地址URL  
        const UNIFIEDORDER_URL = "/pay/unifiedorder"; 
        //退款地址URL  
        const REFUND_URL = "/secapi/pay/refund"; 
        //获取access_tokenURL 前缀
        const ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
        //查询订单URL  
        const ORDERQUERY_URL = "/pay/orderquery";  
        //关闭订单URL  
        const CLOSEORDER_URL = "/pay/closeorder"; 
        //商户证书key路径  
        const hostcert = "/cert/apiclient_cert.pem"; 
        //关闭订单URL  
        const hostkey = "/cert/apiclient_key.pem";  
        //公众账号ID  
        public $appid;
        //小程序的 app secret  
        private $appsecret;  
        //商户号  
        private $mch_id; 
        //openid  
        private $openid;  
        //随机字符串  
        private $nonce_str;  
        //自定义字段  
        private $attach;  
        //签名  
        private $sign;  
        //商品描述  
        private $body;  
        //商户订单号  
        private $out_trade_no;  
        //支付总金额  
        private $total_fee;  
        //终端IP  
        private $spbill_create_ip;  
        //支付结果回调通知地址  
        private $notify_url;  
        //交易类型  
        private $trade_type;  
        //支付密钥  
        private $key;  
        //证书路径  
        private $SSLCERT_PATH;  
        private $SSLKEY_PATH;  
        //所有参数  
        private $params = array();  
        private $wxConfig = NULL;
        private $order = NULL;
        private $order_refund = NULL;
        public function _initializes(){
            !is_null($this->wxConfig)?:$this->wxConfig = D('wxConfig');
            !is_null($this -> order ) ? : $this -> order =  D('order_info');
            !is_null($this -> order_refund ) ? : $this -> order_refund =  D('order_refund');
            $wxConfigArr = $this->wxConfig->select();
            foreach ($wxConfigArr as $v) {
                $this->appid = $v['appid'];  
                $this->appsecret = $v['appsecret'];  
                $this->mch_id = $v['wxmchid'];
                $this->key = $v['wxkey'];
            }
        }
        /**
         * 微信退款(POST)
         * @param string(28) $out_trade_no    //商户订单号 在微信支付的时候,微信服务器生成的订单流水号,在支付通知中有返回
         * @param string $out_refund_no         商品简单描述
         * @param string $total_fee             微信支付的时候支付的总金额(单位:分)
         * @param string $refund_fee             此次要退款金额(单位:分)
         * @return string                        xml格式的数据
         */
        private function refund($out_trade_no,$out_refund_no,$total_fee,$refund_fee){
            //退款参数
            $refundorder = array(
                'appid'            => $this->appid,
                'mch_id'        => $this->mch_id,
                'nonce_str'        => $this->genRandomString(),
                'out_trade_no'=> $out_trade_no,
                'out_refund_no'    => $out_refund_no,
                'total_fee'        => intval($total_fee),
                'refund_fee'    => intval($refund_fee)
            );
            $refundorder['sign'] = $this->makeSign($refundorder);
            // dump($refundorder);
            // exit;
            //请求数据,进行退款
            $xml = $this->data_to_xml($refundorder); 
            $response = $this->curl_post_ssl(self::API_URL_PREFIX.self::REFUND_URL,$xml);
            //请求退款 
            // $response = $this->postXmlCurl($xml, self::API_URL_PREFIX.self::REFUND_URL,true);  
            // dump($response);
            if(!$response){  
                return false;  
            }  
            //获取相应结果
            $result = $this->xml_to_data($response);

            if( !empty($result['result_code']) && !empty($result['err_code']) ){  
                $result['err_msg'] = $this->error_code( $result['err_code'] );  
            }  
            return $result;
        }
        //获取退款订单，执行退款手续
        public function orderRefund(){
            set_time_limit(0);
            $list = $this->order->field('order_sn,order_id,order_amount')->where(array('order_id'=>array('between',array(33,693)),'pay_status'=>2,'order_status'=>1))->select();
            // $list = $this->order->field('order_sn,order_id,order_amount')->where(array('order_id'=>60,'pay_status'=>1))->select();
            // $list = array(array('order_id'=>1000,'order_sn'=>'nwt201808045509191929','order_amount'=>'0.01'));
            dump($list);
            exit;
            // 循环退款
            $this->order->startTrans();
            foreach ($list as $v) {
                $res = $this->refund($v['order_sn'],$v['order_sn'],round($v['order_amount'],2)*100,round($v['order_amount'],2)*100);
                dump($res['refund_id']);
                if($res){
                    $status = '1';
                    $bool = $this->order->save(array('pay_status'=>2,'order_id'=>$v['order_id']));
                    // $bool = 1;
                }else{
                    $status = '0';
                }
                $data = array('add_time'=>time(),'order_id'=>$v['order_id'],'refund_sn'=>$res['refund_id'],'status'=>$status);
                //写入记录
                $bool1 = $this->order_refund->add($data);
                dump($bool);
                dump($bool1);
                file_put_contents('refund.txt',json_encode($res)."\n",FILE_APPEND);
                if($bool && $bool1){
                    $this->order->commit();
                }else{
                    $this->order->rollback();
                }
                // sleep(3);
            }
        }
        public function curl_post_ssl($url, $aHeader){
            $ch = curl_init();
            //超时时间
            curl_setopt($ch,CURLOPT_TIMEOUT,30);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
            //这里设置代理，如果有的话
            //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
            //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
            
            //以下两种方式需选择一种
            
            //第一种方法，cert 与 key 分别属于两个.pem文件
            //默认格式为PEM，可以注释
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT,getcwd().self::hostcert);
            //默认格式为PEM，可以注释
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY,getcwd().self::hostkey);
            
            //第二种方式，两个文件合成一个.pem文件
            // curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');
         
            // if( count($aHeader) >= 1 ){
            //     curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
            // }
         
            curl_setopt($ch,CURLOPT_POST, 1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$aHeader);
            $data = curl_exec($ch);
            // dump($data);
            if($data){
                curl_close($ch);
                return $data;
            }
            else { 
                $error = curl_errno($ch);
                echo "call faild, errorCode:$error\n"; 
                curl_close($ch);
                return false;
            }
        }
        /**
         * 通过CURL发送HTTP请求
         * @param string $url  //请求URL
         * @param array $postFields //请求参数
        */
        private function curlPost($url,$postFields = ''){
            
            //dump($postFields);die;
            $ch = curl_init ();
            curl_setopt ( $ch, CURLOPT_URL, $url );
            curl_setopt ( $ch, CURLOPT_POST, 1 );
            curl_setopt ( $ch, CURLOPT_HEADER, 0 );
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
            if($postFields){
                $postFields = http_build_query($postFields);
                curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields);
            }
            //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec ($ch);
            // $curlInfo = curl_getinfo($ch);
            curl_close ( $ch );
            //var_dump($result);die;
            return $result;
        }
        /**
         * 通过CURL发送HTTP请求
         * @param string $url  //请求URL
         * @param array $postFields //请求参数json
        */
        private function https_request($action,$myParams){
            $curlobj = curl_init();   // 初始化  
            curl_setopt($curlobj,CURLOPT_URL,$action);   //设置访问网页的URL  
            curl_setopt($curlobj,CURLOPT_RETURNTRANSFER,TRUE);//执行之后不直接打印出来
            curl_setopt($curlobj, CURLOPT_HEADER, false); 
            curl_setopt($curlobj, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curlobj, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curlobj, CURLOPT_POST, 1);
            curl_setopt($curlobj, CURLOPT_POSTFIELDS, json_encode($myParams));
            curl_setopt($curlobj, CURLOPT_FOLLOWLOCATION, 1);
            $out_put = curl_exec($curlobj);
            $filepath = md5($myParams['path']).".jpg";
            file_put_contents($filepath, $out_put);
            curl_close($curlobj);
            
            return $filepath;
        }
         /**
         * 通过CURL发送HTTP请求get
         * @param string $action  //请求URL
         * @param array $postFields //请求参数
         */
        private function curlGet($action){
            $curlobj = curl_init();   // 初始化  
            curl_setopt($curlobj,CURLOPT_URL,$action);   //设置访问网页的URL  
            curl_setopt($curlobj,CURLOPT_RETURNTRANSFER,TRUE);//执行之后不直接打印出来
            curl_setopt($curlobj, CURLOPT_HEADER, false); 
            curl_setopt($curlobj, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curlobj, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curlobj, CURLOPT_FOLLOWLOCATION, 1);
            $out_put = curl_exec($curlobj);
            curl_close($curlobj);
            return json_decode($out_put,true);
        }
        /** 
         * 查询订单信息 
         * @param $out_trade_no     订单号 
         * @return array 
         */  
        public function orderQuery( $out_trade_no ){  
            $this->params['appid'] = $this->appid;  
            $this->params['mch_id'] = $this->mch_id;  
            $this->params['nonce_str'] = $this->genRandomString();  
            $this->params['out_trade_no'] = $out_trade_no;  
            //获取签名数据  
            $this->sign = $this->MakeSign( $this->params );  
            $this->params['sign'] = $this->sign;  
            $xml = $this->data_to_xml($this->params);  
            $response = $this->postXmlCurl($xml, self::API_URL_PREFIX.self::ORDERQUERY_URL);  
            if( !$response ){  
                return false;  
            }  
            $result = $this->xml_to_data( $response );  
            if( !empty($result['result_code']) && !empty($result['err_code']) ){  
                $result['err_msg'] = $this->error_code( $result['err_code'] );  
            }  
            return $result;  
        }
          
        /** 
         * 关闭订单 
         * @param $out_trade_no     订单号 
         * @return array 
         */  
        public function closeOrder($out_trade_no){  
            $this->params['appid'] = $this->appid;  
            $this->params['mch_id'] = $this->mch_id;  
            $this->params['nonce_str'] = $this->genRandomString();  
            $this->params['out_trade_no'] = $out_trade_no;  
            //获取签名数据  
            $this->sign = $this->MakeSign($this->params);  
            $this->params['sign'] = $this->sign;  
            $xml = $this->data_to_xml($this->params);  
            $response = $this->postXmlCurl($xml, self::API_URL_PREFIX.self::CLOSEORDER_URL);  
            if( !$response ){  
                return false;  
            }  
            $result = $this->xml_to_data( $response );  
            return $result;  
        }  
        /** 
         *  
         * 获取支付结果通知数据 
         * return array 
         */  
        public function getNotifyData(){
            //获取通知的数据  
            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];  
            $data = array();  
            if( empty($xml) ){  
                return false;  
            }  
            $data = $this->xml_to_data( $xml );  
            if( !empty($data['return_code']) ){  
                if( $data['return_code'] == 'FAIL' ){  
                    return false;  
                }  
            }  
            return $data;  
        }  
        /** 
         * 接收通知成功后应答输出XML数据 
         * @param string $xml 
         */  
        public function replyNotify(){  
            $data['return_code'] = 'SUCCESS';  
            $data['return_msg'] = 'OK';  
            $xml = $this->data_to_xml($data);  
            echo $xml;  
            die();  
        }  
         /** 
          * 生成APP端支付参数 
          * @param  $prepayid   预支付id 
          */  
         public function getAppPayParams( $prepayid ){  
             $data['appid'] = $this->appid;  
             $data['mch_id'] = $this->mch_id;  
             $data['prepayId'] = $prepayid;  
             $data['package'] = 'Sign=WXPay';  
             $data['nonce_str'] = $this->genRandomString();  
             $data['timestamp'] = time();  
             $data['sign'] = $this->MakeSign( $data );   
             $data['key'] = $this->key;  
             return $data;  
         }  
        /** 
         * 生成签名 
         *  @return 签名 
         */  
        public function MakeSign($params){
            //签名步骤一：按字典序排序数组参数  
            ksort($params);  
            $string = $this->ToUrlParams($params);  
            //签名步骤二：在string后加入KEY  
            $string = $string . "&key=".$this->key;  
            //签名步骤三：MD5加密  
            $string = md5($string);  
            //签名步骤四：所有字符转为大写  
            $result = strtoupper($string);  
            return $result;  
        }  
  
  
        /** 
         * 将参数拼接为url: key=value&key=value 
         * @param   $params 
         * @return  string 
         */  
        public function ToUrlParams( $params ){  
            $string = '';  
            if( !empty($params) ){  
                $array = array();  
                foreach( $params as $key => $value ){  
                    $array[] = $key.'='.$value;  
                }  
                $string = implode("&",$array);  
            }  
            return $string;  
        }  
        /** 
         * 输出xml字符 
         * @param   $params     参数名称 
         * return   string      返回组装的xml 
         **/  
        public function data_to_xml( $params ){  
            if(!is_array($params)|| count($params) <= 0)  
            {  
                return false;  
            }  
            $xml = "<xml>";  
            foreach ($params as $key=>$val)  
            {  
                if (is_numeric($val)){  
                    $xml.="<".$key.">".$val."</".$key.">";  
                }else{  
                    $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
                }  
            }  
            $xml.="</xml>";  
            return $xml;   
        }  
        /** 
         * 将xml转为array 
         * @param string $xml 
         * return array 
         */  
        public function xml_to_data($xml){    
            if(!$xml){  
                return false;  
            }  
            //将XML转为array  
            //禁止引用外部xml实体  
            libxml_disable_entity_loader(true);  
            $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);          
            return $data;  
        }  
        /** 
         * 获取毫秒级别的时间戳 
         */  
        private static function getMillisecond(){  
            //获取毫秒的时间戳  
            $time = explode ( " ", microtime () );  
            $time = $time[1] . ($time[0] * 1000);  
            $time2 = explode( ".", $time );  
            $time = $time2[0];  
            return $time;  
        }  
        /** 
         * 产生一个指定长度的随机字符串,并返回给用户  
         * @param type $len 产生字符串的长度 
         * @return string 随机字符串 
         */  
        private function genRandomString($len = 32) {  
            $chars = array(  
                "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",  
                "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",  
                "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",  
                "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",  
                "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",  
                "3", "4", "5", "6", "7", "8", "9"  
            );  
            $charsLen = count($chars) - 1;  
            // 将数组打乱   
            shuffle($chars);  
            $output = "";  
            for ($i = 0; $i < $len; $i++) {  
                $output .= $chars[mt_rand(0, $charsLen)];  
            }  
            return $output;  
        }  
        /** 
         * 以post方式提交xml到对应的接口url 
         *  
         * @param string $xml  需要post的xml数据 
         * @param string $url  url 
         * @param bool $useCert 是否需要证书，默认不需要 
         * @param int $second   url执行超时时间，默认30s 
         * @throws WxPayException 
         */  
        private function postXmlCurl($xml, $url, $useCert = false, $second = 30){         
            $ch = curl_init();  
            //设置超时  
            curl_setopt($ch, CURLOPT_TIMEOUT, $second);  
            curl_setopt($ch,CURLOPT_URL, $url);  
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);  
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);  
            //设置header  
            curl_setopt($ch, CURLOPT_HEADER, FALSE);  
            //要求结果为字符串且输出到屏幕上  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);  
            if($useCert == true){  
                //设置证书  
                //使用证书：cert 与 key 分别属于两个.pem文件  
                curl_setopt($ch,CURLOPT_SSLCERTTYPE,getcwd().self::hostcert);  
                //curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);  
                curl_setopt($ch,CURLOPT_SSLKEYTYPE,getcwd().self::hostkey);  
                //curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);  
            }  
            //post提交方式  
            curl_setopt($ch, CURLOPT_POST, TRUE);  
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);  
            //运行curl  
            $data = curl_exec($ch);  
            //返回结果  
            if($data){  
                curl_close($ch);  
                return $data;  
            } else {   
                $error = curl_errno($ch);  
                curl_close($ch);  
                return false;  
            }  
        }  
        /** 
          * 错误代码 
          * @param  $code       服务器输出的错误代码 
          * return string 
          */  
         public function error_code( $code ){  
             $errList = array(  
                'NOAUTH'                =>  '商户未开通此接口权限',  
                'NOTENOUGH'             =>  '用户帐号余额不足',  
                'ORDERNOTEXIST'         =>  '订单号不存在',  
                'ORDERPAID'             =>  '商户订单已支付，无需重复操作',  
                'ORDERCLOSED'           =>  '当前订单已关闭，无法支付',  
                'SYSTEMERROR'           =>  '系统错误!系统超时',  
                'APPID_NOT_EXIST'       =>  '参数中缺少APPID',  
                'MCHID_NOT_EXIST'       =>  '参数中缺少MCHID',  
                'APPID_MCHID_NOT_MATCH' =>  'appid和mch_id不匹配',  
                'LACK_PARAMS'           =>  '缺少必要的请求参数',  
                'OUT_TRADE_NO_USED'     =>  '同一笔交易不能多次提交',  
                'SIGNERROR'             =>  '参数签名结果不正确',  
                'XML_FORMAT_ERROR'      =>  'XML格式错误',  
                'REQUIRE_POST_METHOD'   =>  '未使用post传递参数 ',  
                'POST_DATA_EMPTY'       =>  'post数据不能为空',  
                'NOT_UTF8'              =>  '未使用指定编码格式',  
             );   
             if( array_key_exists( $code , $errList ) ){  
                return $errList[$code];  
             }  
         }  
    } 

  

?>