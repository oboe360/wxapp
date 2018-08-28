<?php
namespace Home\Controller;
header('content-type:text/html;charset=utf-8');
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
    class WechatAppPays 
    {     
        //接口API URL前缀  
        const API_URL_PREFIX = 'https://api.mch.weixin.qq.com';  
        //下单地址URL  
        const UNIFIEDORDER_URL = "/pay/unifiedorder";  
        //获取access_tokenURL 前缀
        const ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
        //查询订单URL  
        const ORDERQUERY_URL = "/pay/orderquery";  
        //关闭订单URL  
        const CLOSEORDER_URL = "/pay/closeorder";  
        //公众账号ID  
        public $appid;
        //小程序的 app secret  
        private $appsecret;  
        //商户号  
        private $mch_id; 
        //通莞支付参数获取接口  
        private $tg_url = 'https://ipay.833006.net/tgPosp/services/payApi/wxJspay';
        //通莞account商户进件手机号  
        private $tg_account = '15335384563'; 
        // 通莞key
        private $tg_key = '5671b0183f2df34ef7fc8738a681a698'; 
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
        public function __construct($appid, $appsecret, $mch_id)  
        {  
            $this->appid = $appid;  
            $this->appsecret = $appsecret;  
            $this->mch_id = $mch_id;  
            // $this->notify_url = $notify_url;  
            $this->key = $this->tg_key;  
        }  
        //根据code码获取openID
        public function getOpenid($code){
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$this->appid}&secret={$this->appsecret}&js_code={$code}&grant_type=authorization_code";
            // $result = file_get_contents();
            // echo $url;
            $result = $this->curlPost($url);
            $result = json_decode($result, true);
            return $result;
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

        //通莞代收接口
        public function tg_collection($params){
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
            // dump($data);
            // exit;
            //存入session
            // session('sign',$params['sign']);
            //请求获取支付参数
            $res = $this->tg_http_request($this->tg_url, $data);
            return json_decode($res,true);
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
         * 生成签名 
         *  @return 签名 
         */  
        public function MakeSign($params){
            //签名步骤一：按字典序排序数组参数  
            ksort($params);  
            $string = $this->ToUrlParams($params);  
            //签名步骤二：在string后加入KEY  
            $string = $string . "&key=".$this->key;  
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
        public function ToUrlParams( $params ){  
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
                curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');  
                //curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);  
                curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');  
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