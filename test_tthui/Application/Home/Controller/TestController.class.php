<?php
namespace Home\Controller;
use Think\Controller;
// header("Access-Control-Allow-Origin: *");
class TestController extends Controller {
    public $income_order_status = array('1' => '待发货', '2' => '已发货', '3' => '待结算', '4' => '已取消', '5' => '已结算');
    public $income_order_type = array('0' => '普通订单', '1' => '礼包订单');
    public $income_is_tixian = array('0' => '末提现', '1' => '已提现', '2' => '已注销');
    public function my_tuoke(){
        session('shop_id');
        if (empty($_SESSION['shop_id'])) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
        //查询出后台配置的信息
        if(empty(session('other_config'))){
            $other_config_ls = D('other_config')->where("`parent_id` = '9'")->select();
            for($i = 0; $i < count($other_config_ls); $i++){
                $other_config[$other_config_ls[$i]['key']] = $other_config_ls[$i]['value'];
            }
            session('other_config', $other_config);
        }
        //session('other_config', '');
        $shop_id = session('shop_id');
        if(I('tixian') == 'tixian'){
            $last_num = I('last_num');
            if(!empty($last_num)){
                if(I('type') == '1'){
                    $where = " AND `is_tixian` = '0'";
                }else{
                    $where = " AND `is_tixian` = '1'";
                }
                $data = D()->query("SELECT a.`shop_id`, a.`buyid`, a.`money`, a.`order_sn`, a.`order_money`, a.`order_status`, a.`order_type`, a.`is_tixian`, a.`add_time`, b.`headimgurl`, b.`phone`, b.`nickname` FROM `app_shop_earnings_record` AS a INNER JOIN `app_user` AS b ON a.`buyid` = b.`uid` WHERE a.`shop_id` = '{$shop_id}' {$where} ORDER BY a.`add_time` DESC LIMIT {$last_num}, 10");
                if($data){
                    $data = $this->formatting($data, 'shop_earnings_record');
                    $arr['error'] = '0';
                    $arr['data'] = $data;
                    $this->ajaxReturn($arr);exit;
                }else{
                    $arr['error'] = '1';
                    $this->ajaxReturn($arr);exit;
                }
            }
        }
        //商品收入
        $res_order = D()->query("SELECT a.`shop_id`, a.`buyid`, a.`money`, a.`order_sn`, a.`order_money`, a.`order_status`, a.`order_type`, a.`is_tixian`, a.`add_time`, b.`headimgurl`, b.`phone`, b.`nickname` FROM `app_shop_earnings_record` AS a INNER JOIN `app_user` AS b ON a.`buyid` = b.`uid` WHERE a.`shop_id` = '{$shop_id}' AND a.`order_type` = '0' ORDER BY a.`add_time` DESC");
        $res_order = $this->formatting($res_order, 'shop_earnings_record');
        // dump($res_order);
        //会员包收入
        $no_res_order = D()->query("SELECT a.`shop_id`, a.`buyid`, a.`money`, a.`order_sn`, a.`order_money`, a.`order_status`, a.`order_type`, a.`is_tixian`, a.`add_time`, b.`headimgurl`, b.`phone`, b.`nickname` FROM `app_shop_earnings_record` AS a INNER JOIN `app_user` AS b ON a.`buyid` = b.`uid` WHERE a.`shop_id` = '{$shop_id}' AND a.`order_type` = '1' ORDER BY a.`add_time` DESC LIMIT 10");
        $record = D('shop_earnings_record');
        $totalwhere['shop_id'] =$shop_id;
        $totalwhere['order_type'] =1;
        $total = $record ->field("count('id') as number")->where($totalwhere)->find();

        $no_res_order = $this->formatting($no_res_order, 'shop_earnings_record');
        // dump($no_res_order);
        //查询店铺信息
        $earnings = D('shop_earnings')->field('money')->where("`shop_id` = '{$shop_id}'")->find();
        $xia_store = D('the_store')->where("`sj_id` = '{$shop_id}'")->count();
        // $shop_user = D('shop_user')->where("`shop_id` = '{$shop_id}'")->count();
        $shop_user = D('history')->join('app_user on app_history.uid = app_user.uid' )->where('app_history.shop_id = '.$shop_id)->group("app_history.uid")->select();
        $shop_user = count($shop_user);
        $shop_info = D('the_store')->where("`id` = '{$shop_id}'")->find();
        // echo D('history')->getLastSql();
        // D()->query("SELECT b.* FROM app_history a LEFT JOIN app_user b on a.uid = b.uid WHERE a.shop_id = ".$shop_id." GROUP BY uid limit 0,10")
        // dump($shop_info);
        $rankwhere['rank_id'] =$shop_info['shop_rank'];
        $shop_rank_name = D('store_rank')->where($rankwhere)->getField('shop_rank_name');
        // print_r($rank_info);die;
       
        //dump($rank_rank);die;色彩
        $performance_Money = D('shop_achievement')->where("`shop_id` = '{$shop_id}' and is_tixian = 0")->SUM('income_money');

        $this->assign('shop_count', array('earnings' => $earnings['money']+$performance_Money, 'xia_store' => $xia_store, 'shop_user' => $shop_user));
        
        $this->assign('total',$total['number']);
        $this->assign('shop_id',$shop_id);
        $this->assign("shop_rank",$shop_info['shop_rank']);
        $this->assign('tix_tixian', $res_order);
        $this->assign('rank_rank',$shop_rank_name);
        $this->assign('shop_rank',$shop_info['shop_rank']);
        $this->assign('not_tixian', $no_res_order);
        $this->assign('shop_info', $shop_info);
        $this->display('index');
    }
    public function tuoke_fans(){
        $shop_id = session('shop_id');
        if (empty($shop_id)) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
        //查询普通用户
        $user_rank = D('the_store')->where('id = '.$shop_id)->getField('shop_rank');
        $fans_list = D()->query("SELECT * FROM app_user WHERE shop_id = ".$shop_id." and user_rank = 1 ORDER BY reg_time DESC limit 0,10");
        $fans_list_count = count(D()->query("SELECT * FROM app_user WHERE shop_id = ".$shop_id." and user_rank = 1"));
        //查询会员用户
        $fans_list_vip = D()->query("SELECT * FROM app_user WHERE shop_id = ".$shop_id." and user_rank = 2 ORDER BY reg_time DESC  limit 0,10");
        $fans_list_vip_count = count(D()->query("SELECT * FROM app_user WHERE shop_id = ".$shop_id." and user_rank = 2 "));
        // dump($fans_list);die;
        $this->assign("fans_list",$fans_list);
        $this->assign("shop_rank",$user_rank);
        $this->assign("fans_list_vip",$fans_list_vip);
        $this->assign("fans_list_count",$fans_list_count);
        $this->assign("fans_list_vip_count",$fans_list_vip_count);
        $this->display("tuoke_fans");
    }

    public function tuoke_fans_pubu(){
        $shop_id = session('shop_id');
        $last_num = I('last_num');
        $type = I('type');
        // dump($last_num);
        if(!empty($last_num)){
            //$last_num --;
            // $User = M("shop_user");
            $data = D()->query("SELECT * FROM  app_user WHERE shop_id = ".$shop_id." and user_rank = ".$type." ORDER BY reg_time DESC limit ".$last_num.",10");
            // dump($data);
            if($data){
                // for($i = 0; $i < count($data); $i++){
                //  $data[$i]['add_time'] = date('Y-m-d H:i', $data[$i]['add_time']);
                // }
                $arr['error'] = '0';
                $arr['data'] = $data;
                $this->ajaxReturn($arr);
            }else{
                $arr['error'] = '1';
                $this->ajaxReturn($arr);
            }
        }          
    }

    //-----下级店铺---------
    public function shop_lower(){
        $shop_id = session('shop_id');
        if (empty($shop_id)) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
        $rank_list = D('store_rank')->order('rank_id asc')->limit(0,3)->select();
            // dump($rank_list);die;
        // $shop_id = 1;
        $user_rank = D('the_store')->where('id = '.$shop_id)->getField('shop_rank');
        // dump($user_rank);die;
        if ($user_rank == $rank_list['2']['rank_id']) {
            $where = " a.zd_shop_id = ".$shop_id;
            // dump($where);die;
        }else if ($user_rank == $rank_list['1']['rank_id']) {
            $where = " a.sj_id = ".$shop_id;
        }else{
            $where = 1;
        }
        $data_rank1 = D()->query("SELECT a.*, (SELECT COUNT(*) FROM `app_user` WHERE shop_id = a.id) AS count_user,(SELECT total_count FROM `app_shop_earnings` WHERE shop_id = a.id) AS total_count FROM `app_the_store` AS a WHERE ".$where." AND `shop_rank` = '1' ORDER BY a.add_time DESC LIMIT 10");
        // echo "SELECT a.*, (SELECT COUNT(*) FROM `app_user` WHERE shop_id = a.id) AS count_user FROM `app_the_store` AS a WHERE ".$where." AND `shop_rank` = '1' ORDER BY a.add_time DESC LIMIT 10";die;
        $data_rank2 = D()->query("SELECT a.*, (SELECT COUNT(*) FROM `app_user` WHERE shop_id = a.id) AS count_user,(SELECT total_count FROM `app_shop_earnings` WHERE shop_id = a.id) AS total_count FROM `app_the_store` AS a WHERE ".$where." AND `shop_rank` = '2' ORDER BY a.add_time DESC LIMIT 10");
        // for($i = 0; $i < count($data); $i++){
        //     $data[$i]['add_time'] = date('Y-m-d H:i', $data[$i]['add_time']);
        // }
        // var_dump($data_rank1);
        $this->assign('shop_lower', $data_rank1);
        $this->assign('shop_lower_ls', $data_rank2);
        // $this->assign('user_rank', $user_rank);
        $this->assign('rank_list',$rank_list);
        $this->display('shop_lower');
    }

    public function shop_lower_return(){
        $shop_id = session('shop_id');
        // $shop_id = 1;
        $rank_list = D('store_rank')->order('rank_id asc')->limit(0,3)->select();
        $last_num = I('last_num');
        $type = I('type');
        //dump($last_num);
        if(!empty($last_num)){
            $user_rank = D('the_store')->where('id = '.$shop_id)->getField('shop_rank');
            // dump($user_rank);die;
            if ($user_rank == $rank_list['2']['rank_id']) {
                $where = " a.zd_shop_id = ".$shop_id;
                // dump($where);die;
            }else if ($user_rank == $rank_list['1']['rank_id']) {
                $where = " a.sj_id = ".$shop_id;
            }else{
                $where = 1;
            }
            $data = D()->query("SELECT a.*, (SELECT COUNT(*) FROM `app_user` WHERE shop_id = a.id) AS count_user,(SELECT total_count FROM `app_shop_earnings` WHERE shop_id = a.id) AS total_count FROM `app_the_store` AS a WHERE ".$where." AND `shop_rank` = ".$type." ORDER BY a.add_time DESC LIMIT {$last_num}, 10");
            //dump($data);
            if($data){
                for($i = 0; $i < count($data); $i++){
                    $data[$i]['add_time'] = date('Y-m-d H:i', $data[$i]['add_time']);
                }
                $arr['error'] = '0';
                $arr['data'] = $data;
                $this->ajaxReturn($arr);
            }else{
                $arr['error'] = '1';
                $this->ajaxReturn($arr);
            }
        }
    }
    public function performance_report()
    {
        if ($_REQUEST['type'] == '1') {
            $last_num = I('last_num');
            $User = M("shop_achievement");
            $shop_id = session('shop_id');
            $report_list = $User
            ->where('shop_id = '.$shop_id)->order('number desc')->limit($last_num,10)->select();
            // dump($report_list);die;
            if ($report_list) {
                $arr['error'] = '0';
                $arr['data'] = $report_list;
                $this->ajaxReturn($arr);
            }else{
                $arr['error'] = '1';
                $this->ajaxReturn($arr);                
            }
        }else{
            $User = M("shop_achievement");
            $shop_id = session('shop_id');
            if (empty($shop_id)) {
                // $data['code'] = '4001';
                // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
                // echo $data['msg'];
                // echo json_encode($data);
                header('Location: '.U('Home/Test/zhuce'));
                exit;
            }
            $report_list = $User
            ->where('shop_id = '.$shop_id)->order('number desc')->limit(0,10)->select();
            $this->assign('report_list',$report_list);
            // dump($report_list);die;
            $this->display("performance_report");
        }
    }
    public function performance_report_data()
    {
        $shop_id = session('shop_id');
        if (empty($shop_id)) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
        if ($_REQUEST['type'] == '1') {
            $last_num = I('last_num');
            $sn_id = I('sn_id');
            $type = I('type');
            $number_id = I('number_id');
            // $number_id = 13;
            // echo $number_id;die;
            $User = M("shop_achievement");
            $report_list_data = $User
            ->where('shop_id = '.$shop_id.' and number='.$number_id)->limit(0,10)->find();
            // dump()$report_list_data;die;
            $User2 = M("shop_achievement_sn");
            $report_list_data2 = $User2
            ->JOIN("app_the_store on app_shop_achievement_sn.shop_id = app_the_store.id")
            ->where('app_shop_achievement_sn.shop_id in ('.$report_list_data['shop_list'].')and app_shop_achievement_sn.a_number = '.$number_id)->limit($last_num,10)->select();
            // echo $report_list_data2;die;
            if ($report_list_data2) {
                $arr['error'] = '0';
                $arr['data'] = $report_list_data2;
                $this->ajaxReturn($arr);
            }else{
                $arr['error'] = '1';
                $this->ajaxReturn($arr);
            }
            // $this->assign('number_id',$number_id);
        }else{
            // dump($_REQUEST);die;
            $User = M("shop_achievement");
            $report_list_data = $User
            ->where('shop_id = '.$shop_id.' and number = '.$_REQUEST['number'])->find();

            $User2 = M("shop_achievement_sn");
            $report_list_data2 = $User2
            ->JOIN("app_the_store on app_shop_achievement_sn.shop_id = app_the_store.id")
            ->where('app_shop_achievement_sn.shop_id in ('.$report_list_data['shop_list'].') and app_shop_achievement_sn.a_number = '.$_REQUEST['number'])->limit(0,10)->select();
            // dump($report_list_data2);die;
            $this->assign('sn_id',$_REQUEST['id']);
            $this->assign('number_id',$_REQUEST['number']);
            $this->assign('report_list_data',$report_list_data2);
            $this->display('performance_report_data');            
        }
    }
    public function tuoke_withdraw(){
        $this->display("tuoke_withdraw");
    }
    public function modify_tuoke_data(){
        $shop_id = session('shop_id');
        $User = M("the_store");
        $shop_info = $User->where('id = '.$shop_id)->find();
        $this->assign("shop_info",$shop_info);
        $this->display("modify_tuoke_data");
    }
    public function modify_nickname(){
        $shop_id = session('shop_id');
        $type = $_REQUEST['type'];
        if ($type == "shopname") {
            $User = M("the_store");
            $shop_info = $User->where('id = '.$shop_id)->getField("shop_name");
        }elseif ($type == "tuoke_tel") {
            $User = M("the_store");
            $shop_info = $User->where('id = '.$shop_id)->getField("shop_phone");
        }elseif ($type == "tuoke_shop_notice") {
            $User = M("the_store");
            $shop_info = $User->where('id = '.$shop_id)->getField("shop_notice");
        }elseif ($type == "tuoke_shop_img") {
    
        }
        // echo "string";
        // print_r($shop_info);
        $this->assign("type",$type);
        $this->assign("shop_info",$shop_info);
        $this->display("modify_nickname");
    }
    public function store_update(){
        // echo "string";die;
        $shop_id = session('shop_id');
        if (empty($shop_id)) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
        // echo $shop_id;die;
        // print_r($_REQUEST);
        if (!empty($_REQUEST['nickname'])) {
            $data['value'] = $_REQUEST['nickname'];
            $User = M("the_store"); 
            $User->shop_name = $_REQUEST['nickname'];
            $User->where('id = '.$shop_id)->save();
        }elseif (!empty($_REQUEST['shop_phone'])) {
            $data['value'] = $_REQUEST['shop_phone'];
            $User = M("the_store"); 
            $User->shop_phone = $_REQUEST['shop_phone'];
            $User->where('id = '.$shop_id)->save();
        }elseif (!empty($_REQUEST['shop_notice'])) {
            $User = M("the_store"); 
            $User->shop_notice = $_REQUEST['shop_notice'];
            $User->where('id = '.$shop_id)->save();
        }
    }
	//上传店铺头像
    public function shop_img_upload()
    {
        // var_dump($_FILES);
        if ($_FILES['shop_img']['name'] == "") {
            header('Location: '.U('Home/Test/modify_nickname').'?type=tuoke_shop_img');
            exit();
        }
        $shop_id = session('shop_id');
        if (empty($shop_id)) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
        $User = M("the_store");
        $shop_info_img = $User->where('id = '.$shop_id)->getField("shop_img");
        if ($shop_info_img) {
            $this->delQiniu($shop_info_img, 'http://p2y8yvch3.bkt.clouddn.com', 'shop-img');
        }
        if ($_FILES['file']['error'] == 0) {
            $filePath = $_FILES['shop_img']['tmp_name'];
            $file = portal_qiniu($filePath, 'http://pcethhc7g.bkt.clouddn.com', 'shop-img');
            $file = $file['key'];
            if($file){
                $data['code_img'] = $file;
                $User = M("the_store"); 
                $User->shop_img = $file;
                $User->where('id='.$shop_id)->save();
                $data['code'] = 1;
                $data['msg'] = '上传成功';
                echo 1;
            }        
        }
        header('Location: '.U('Home/Test/modify_tuoke_data'));
    }
    public function idcard_upload()
    {
        // dump($_REQUEST);
        $shop_id = session('shop_id');
        if (empty($shop_id)) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
        // dump($_FILES);
        if ($_FILES['fileToUpload']['name'] == "" || $_FILES['fileToUpload2']['name'] == "") {
            header('Location: '.U('Home/Test/modify_nickname').'?type=tuoke_shop_img');
            exit();
        }
        if ($_FILES['fileToUpload']['error'] == 0 && $_FILES['fileToUpload2']['error'] == 0) {
            $filePath = $_FILES['fileToUpload']['tmp_name'];
            $filePath2 = $_FILES['fileToUpload2']['tmp_name'];
            $file = portal_qiniu($filePath, 'http://pd2med1qa.bkt.clouddn.com', 'idcard-img');
            $file2 = portal_qiniu($filePath2, 'http://pd2med1qa.bkt.clouddn.com', 'idcard-img');
            $file = $file['key'];
            $file2 = $file2['key'];
            if($file&&$file2){
                $data['code_img'] = $file;
                $User = M("idcard");
                $User->shop_id = $shop_id;
                $User->idcard_front = $file;
                $User->idcard_back = $file2;
                $User->idname = $_REQUEST['idname'];
                $User->idnumber = $_REQUEST['idnumber'];
                $User->where('id='.$shop_id)->add();
                $data['code'] = 1;
                $data['msg'] = '上传成功';
                echo 1;
            }        
        }else{
            echo 2;
            exit();
        }
    }
    private function delQiniu($url, $qiniu_url = 'http://p2y8yvch3.bkt.clouddn.com', $bucket = 'goods-gallery')
    {
        if($url){
            $res = portal_delect(str_replace($qiniu_url.'/','',$url),$qiniu_url,$bucket);
            // var_dump($res);
            if($res){
                return false;
            }else{
                return true;
            }
        }else{
            return true;
        }
    }
    public function showQRCode(){
        $shop_id = session('shop_id');
        $this->test();
        $this->assign('shop_id',$shop_id);
        $this->display("tuoke_qrcode");
    }
    //生成二维码
    public function create_qrcode(){
        $type = empty($_POST['type']) ? "wxacode" : $_POST['type'];
        if(!$_POST['path']) {
            $data = array(
                'code' => 2,
                'msg'  => 'path参数为空'.$_POST['path']
            );
            echo json_encode($data);die;
        }
        // $sql = "select * from ecs_tk_config where id =1";//
        // $config = $GLOBALS['db']->getRow($sql);
        $appid = M('wx_config')->where('id = 1')->getField("appid");
        $appsecret = M('wx_config')->where('id = 1')->getField("appsecret");
        //换成自己的接口信息
        $queryParam = strstr($_POST['path'], '?');
        $path = "pages/home/home".$queryParam;
        $access_token = $this->get_access_token($appid,$appsecret);
        $out_put      = $this->get_small_app_qrcode($access_token,$path,$type);
        // echo $out_put;
    }


//---------------------店铺收益提现---------
    public function shop_tixian(){
        // dump($_REQUEST);die;
        $shop_id = session('shop_id');
        if (empty($shop_id)) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
        $customer_tel = M("other_config")->where('`key` = "customer_tel"')->getField("value");
        $procedure = M("other_config")->where('`key` = "procedure"')->getField("value");
        $arr = explode('+', $procedure);
        $procedure1 = rtrim($arr[0],'%')/100;//手续费的百分比
        $procedure2 = rtrim($arr[1]);//手续费的固定
        //dump($procedure2);die;
    	$bank_card = D('shop_bank_card')->where("`shop_id` = '{$shop_id}'")->find();
 /*   	echo D('shop_bank_card')->getlastsql();
    	dump($bank_card);*/
    	//$ti_order = D('shop_earnings_record')->field('order_sn')->where("is_tixian = '0'")->select();
    	$earnings = D('shop_earnings')->field('money')->where("`shop_id` = '{$shop_id}'")->find();
        $idcard_info = D('idcard')->where("`shop_id` = '{$shop_id}'")->find();
        $performance_money = D('shop_achievement')->where("`shop_id` = '{$shop_id}' and is_tixian = 0")->SUM('income_money');
        // dump( $performance_money);die;
        $performance_info = D('shop_achievement')->where("`shop_id` = '{$shop_id}' and is_tixian = 0")->select();
        // dump($earnings);die;
        if (!empty($performance_info)) {
            foreach ($performance_info as $key => $value2) {
                $achievement_sn .= $value2['id'].',';
            }
            $achievement_sn = trim($achievement_sn, ',');
        }
        $sum_money = sprintf("%.2f",($earnings['money'] + $performance_money));
        // echo $shop_id;
        // dump($achievement_sn);die;
        if($_POST['type'] == 'tixian'){
            if (empty($idcard_info)) {
                $arr['error'] = '7';
                $arr['str'] = '提现需要先实名认证！';
                $this->ajaxReturn($arr);exit;
                // header("location:".U('Home/Test/my_tuoke'));
                // exit;
            }
            if ($bank_card['coop_bank'] == "0") {
                $arr['error'] = '6';
                $arr['str'] = '先绑定银行卡再进行提现！';
                $this->ajaxReturn($arr);exit;
            }
            $morm = M("other_config")->where('`key` = "morm"')->getField("value");
            if($sum_money < $morm){
                $arr['error'] = '1';
                $arr['str'] = '收益金额少于'.$morm.'元,无法进行提现！';
                $this->ajaxReturn($arr);exit;
            }

            $tixians = D()->query("SELECT COUNT(*) AS count, `status` FROM app_shop_tixian WHERE `shop_id` = '{$shop_id}' GROUP BY `status`");
            if($tixians){
                foreach($tixians as $key => $val){
                    $tixian[$val['status']] = $val['count'];
                }
                if($tixian['1'] > '0' || $tixian['2'] > '0'){
                    $arr['error'] = '1';
                    $arr['str'] = '您正有一笔提现正在处理，请到帐后再审请!';
                    $this->ajaxReturn($arr);exit;
                }
            }
            $ti_order = D('shop_earnings_record')->field('order_sn')->where("is_tixian = '0' AND order_status = '5'")->select();
            // dump($ti_order);die;
    		$insert['shop_id'] = $shop_id;
    		$insert['status'] = '1';
    		$insert['add_time'] = time();
            $insert['order_number'] = count($ti_order);
            // dump($insert);die;
            for($k = 0; $k < count($ti_order); $k++){
                // echo $ti_order[$k]['order_sn'];
                $all_sn .= "'".$ti_order[$k]['order_sn']."',";
            }
            // dump($ti_order);die;
            $all_sn = trim($all_sn, ',');
            $insert['all_order_sn'] = $all_sn;
            if(empty($all_sn)){
                $data['error'] = '3';
                $data['str'] = '抱歉!提现出了小小问题,请重新提现或联系客服!';
                $this->ajaxReturn($data);exit;
            }    		
    		$tixis = D()->query("SELECT SUM(`money`) as `money` FROM `app_shop_earnings_record` WHERE `shop_id` = '{$shop_id}' AND `is_tixian` = '0' AND `order_status` = '5' AND `order_sn` IN({$all_sn})");

            // dump($insert);die;
    		if(empty($tixis['0']['money'])&&empty($performance_money)){
    			$data['error'] = '2';
    			$data['str'] = '抱歉!提现出了小小问题,请重新提现或联系客服!';
    			$this->ajaxReturn($data);exit;
    		}
            if (!empty($tixis['0']['money'])&&($earnings['money'] > $tixis['0']['money'])) {
                $data['error'] = '5';
                $data['str'] = '抱歉!提现出了小小问题,请重新提现或联系客服!';
                $this->ajaxReturn($data);exit;
            }
    		//dump($tixis);exit;
    		// $insert['money'] = $earnings['money'] < $tixis['0']['money'] ? $earnings['money'] : $tixis['0']['money'];
            $insert['money'] = sprintf("%.2f",($sum_money - ($sum_money * $procedure1) - $procedure2));
            $insert['shop_money'] = sprintf("%.2f",$earnings['money']);
    		//dump($insert);exit;
    		M()->startTrans();
    		$arr1['money'] = floatval($earnings['money']) - floatval($tixis['0']['money']);
    		$arr1['the_money'] = $earnings['money'];
    		$arr1['the_time'] = time();
    		$res = M('shop_earnings')->where("shop_id='{$shop_id}'")->save($arr1);
            //dump($all_sn);
            if (!empty($tixis['0']['money'])) {
                $res1 = M()->execute("UPDATE `app_shop_earnings_record` SET `is_tixian` = '1', `ti_time` = '{$arr1[the_time]}' WHERE `shop_id` = '{$shop_id}' AND `order_sn` IN({$all_sn})");
            }else{
                $res1 = true;
            }
            if (!empty($performance_money)) {
                $insert['achievement_money'] = sprintf("%.2f",$performance_money);
                $insert['achievement_sn'] = $achievement_sn;
                $res3 = M()->execute("UPDATE `app_shop_achievement` SET `is_tixian` = '1' WHERE `shop_id` = '{$shop_id}' AND `is_tixian` = '0' ");
            }else{
                $res3 = true;
            }
    		$res2 = M('shop_tixian')->data($insert)->add();
    		if($res && $res1 && $res2){
    			M()->commit();
    			$data['error'] = '0';
    			$data['str'] = '您已成功提现了 '.$insert['money'].' 元,将会在1到3个工作日审核到帐!  3秒后返回...';
    			$this->ajaxReturn($data);exit;
    		}else{
    			M()->rollback();
    			$data['error'] = '1';
    			$data['str'] = '抱歉!提现出了小小问题,请重新提现或联系客服!';
    			$this->ajaxReturn($data);exit;
    		}

    	}else{
    		//-----全部订单提现------
    		$earnings['tixian'] = $sum_money;
    		$ti_order = D('shop_earnings_record')->field('order_sn')->where("is_tixian = '0' AND order_status = '5'")->select();
    		// dump($ti_order);die;
    		for($i = 0; $i < count($ti_order); $i++){
    			$earnings['ti_order'] .= $ti_order[$i]['order_sn'].',';
    			//dump($earnings['ti_order']);
    		}
    		$earnings['ti_order'] = trim($earnings['ti_order'], ',');
    		$earnings['earnings_record'] = count($ti_order);
    	}
        
    	$earnings['account'] = sprintf("%.2f",($earnings['tixian'] - ($earnings['tixian'] * $procedure1) - $procedure2));
    	// dump($earnings);die;
        
        $this->assign('bank_card', $bank_card);
    	$this->assign('arr', $arr);
    	$this->assign('customer_tel', $customer_tel);
        $this->assign('earnings', $earnings);
        $this->assign('performance_money',sprintf("%.2f",$performance_money));
        $this->assign('sum_money',sprintf("%.2f",$sum_money));
        $this->assign('idcard_info',$idcard_info);
	    $this->display('shop_tixian');
    }
    //---------------------添加修改银行卡信息---------
    //---------------------添加修改银行卡信息---------
    public function bank_card(){
        $shop_id = session('shop_id');
        if (empty($shop_id)) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
    	$bank_card = D('shop_bank_card')->where("`shop_id` = '{$shop_id}'")->find();
        // dump($bank_card);die;
    	if(!empty($_POST)){
            // dump($bank_card);die;
    		$update['bank_name'] = addslashes($_POST['bank_name']);
    		$update['coop_bank'] = addslashes($_POST['coop_bank']);
    		$update['bank_phone'] = addslashes($_POST['bank_phone']);
    		$update['bank_address'] = addslashes($_POST['bank_address']);
    		$update['bank_city'] = addslashes($_POST['bank_city']);
    		$shop_name = D('the_store')->field('shop_name')->where("`id` = '{$shop_id}'")->find();
    		$update['shop_name'] = $shop_name['shop_name'];
            // dump($update);die;
    		if($bank_card){
    			$update['the_time'] = time();
                // dump($update);die;
    			$inser = D('shop_bank_card')->where("`shop_id`='{$shop_id}'")->data($update)->save();
    		}else{
    			$update['add_time'] = time();
    			$update['shop_id'] = $shop_id;
    			$inser = D('shop_bank_card')->data($update)->add();
    		}
    		if($inser){	
    			header("location:".U('Home/Test/shop_tixian'));
    		}else{
    			header("location:".U('Home/Test/shop_tixian_list'));
    		}
    	}else{
    		$this->assign('bank_card', $bank_card);
    		$this->display('bank_card');
    	}
    }
    //绑定身份证
    public function tuoke_idcard($value='')
    {
        $this->display('tuoke_idcard');
    }
    //---------------------格式化字符串---------
    public function formatting($arr, $style){
        switch($style){
            case 'shop_earnings_record':
                for($i = 0; $i < count($arr); $i++){
                    $arr[$i]['order_status'] = $this->income_order_status[$arr[$i]['order_status']];
                    $arr[$i]['order_type'] = $this->income_order_type[$arr[$i]['order_type']];
                    $arr[$i]['tixian'] = $this->income_is_tixian[$arr[$i]['is_tixian']];
                    $arr[$i]['add_time'] = date("Y-m-d H:i", $arr[$i]['add_time']);
                }
                return $arr;
            break;
        }
    }

    //---------------------店铺按订单提现和收益流水-----------
    public function shop_tixian_order(){
        $shop_id = session('shop_id');
        $res_order = D()->query("SELECT a.`shop_id`, a.`buyid`, a.`money`, a.`order_sn`, a.`order_money`, a.`order_status`, a.`order_type`, a.`is_tixian`, a.`add_time`, b.`headimgurl`, b.`phone`, b.`nickname` FROM `app_shop_earnings_record` AS a INNER JOIN `app_user` AS b ON a.`buyid` = b.`uid` WHERE a.`shop_id` = '{$shop_id}' AND a.`is_tixian` = '0' ORDER BY a.`add_time` DESC");
        //dump($res_order);exit;
        for($i = 0; $i < count($res_order); $i++){
            $res_order[$i]['order_status'] = $this->income_order_status[$res_order[$i]['order_status']];
            $res_order[$i]['order_type'] = $this->income_order_type[$res_order[$i]['order_type']];
            $res_order[$i]['tixian'] = $this->income_is_tixian[$res_order[$i]['is_tixian']];
            $res_order[$i]['add_time'] = date("Y-m-d H:i", $res_order[$i]['add_time']);
        }
        $incom_sn = D()->query("SELECT SUM(`money`) AS count_money, is_tixian FROM `app_shop_earnings_record` AS a INNER JOIN `app_user` AS b ON a.`buyid` = b.`uid` WHERE a.`shop_id` = '{$shop_id}' AND `is_tixian` != '2' GROUP BY is_tixian");
        $income_count = array('to_money' => '0.00', 'no_money' => '0.00');
        for($i = 0; $i < count($incom_sn); $i++){
            if($incom_sn[$i]['is_tixian'] == '0'){
                $income_count['no_money'] = $incom_sn[$i]['count_money'];
            }elseif($incom_sn[$i]['is_tixian'] == '1'){
                $income_count['to_money'] = $incom_sn[$i]['count_money'];
            }
        }
        $income_count['count_money'] = sprintf("%.2f", floatval($income_count['to_money']) + floatval($income_count['no_money']));
        //dump($income_count);
        $this->assign('income_count', $income_count);
        $this->assign('res_order', $res_order);
        $this->display('shop_tixian_order');
    }

        //--------------------店铺的提现记录---------
    public function shop_tixian_list(){
        $shop_id = session('shop_id');
        if (empty($shop_id)) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
    	//echo $shop_id;
    	$earnings = D('shop_earnings')->field('money')->where("`shop_id` = '{$shop_id}'")->find();
        $bank_card = D('shop_bank_card')->where("`shop_id` = '{$shop_id}'")->find();
    	if(I("post.style") == 'tixian'){
    		//查询金额是否足够提现
            $morm = M("other_config")->where('`key` = "morm"')->getField("value");
    		if($earnings['money'] < $morm){
    			$arr['error'] = '1';
    			$arr['str'] = '收益金额少于'.$morm.'元,无法进行提现！';
    			$this->ajaxReturn($arr);exit;
    		}
    		//查询是否有绑定银行卡信息
    		// $bank_card = D('shop_bank_card')->field('coop_bank', 'bank_name', 'bank_address','bank_city', 'bank_phone')->where("`shop_id` = '{$shop_id}'")->find();
            // dump($bank_card);die;
    		$arr['bank'] = $bank_card;
    		if($bank_card['coop_bank'] = 0 || empty($bank_card['bank_name']) || empty($bank_card['bank_address']) || empty($bank_card['bank_city']) || empty($bank_card['bank_phone'])){
    			$arr['error'] = '3';
				$this->ajaxReturn($arr);exit;
    		}else{
    			$tixians = D()->query("SELECT COUNT(*) AS count, `status` FROM app_shop_tixian WHERE `shop_id` = '{$shop_id}' GROUP BY `status`");
    			if($tixians){
    				foreach($tixians as $key => $val){
    					$tixian[$val['status']] = $val['count'];
    				}
    				if($tixian['1'] > '0' || $tixian['2'] > '0'){
    					$arr['error'] = '1';
    					$arr['str'] = '您正有一笔提现正在处理，请到帐后再审请!';
    					$this->ajaxReturn($arr);exit;
    				}else{
    					if($tixian['3'] > '0'){
    						$arr['error'] = '0';
    					}else{
    						$arr['error'] = '2';
    					}
    					$this->ajaxReturn($arr);exit;
    				}
    			}else{
    				$arr['error'] = '0';
    				$this->ajaxReturn($arr);exit;
    			}
    		}
    	}else{
	    	$tixian_count = D()->query("SELECT sum(`money`) AS `money` FROM `app_shop_tixian` WHERE `shop_id` = '{$shop_id}' AND `status` < '4'");
	    	$tixian = D()->query("SELECT *, from_unixtime(`add_time`, '%Y-%m-%d %H:%i') AS `time` FROM `app_shop_tixian` WHERE `shop_id` = '{$shop_id}'");
	        // dump($tixian);die;
	        $this->assign('tixian_count', $tixian_count['0']['money']);
	        $this->assign('earnings', $earnings['money']);
	        $this->assign('tixian', $tixian);
            $this->assign('bank_info',$bank_card);
	        $this->display('shop_tixian_list');
    	}
    }

    public function get_access_token($appid,$appsecret) 
    {
        // wxf3f8ac50755e501a
        // 20ed2279e1427f3aff5e480683793e97
        $action  = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
        $curlobj = curl_init();   // 初始化  
        curl_setopt($curlobj,CURLOPT_URL,$action);   //设置访问网页的URL  
        curl_setopt($curlobj,CURLOPT_RETURNTRANSFER,TRUE);//执行之后不直接打印出来
        curl_setopt($curlobj, CURLOPT_HEADER, false); 
        curl_setopt($curlobj, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curlobj, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curlobj, CURLOPT_FOLLOWLOCATION, 1);
        $out_put = json_decode(curl_exec($curlobj),true);
        curl_close($curlobj);
        $access_token = $out_put['access_token'];
        return $access_token;
    }

    public function get_small_app_qrcode($access_token,$path,$type) {
        $shop_id = session('shop_id');
        $action  = $type == "wxacode" ? "https://api.weixin.qq.com/wxa/getwxacode?access_token=".$access_token : "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;
        $myParams['path'] = $path;
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
        file_put_contents(ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'.png', $out_put);
        curl_close($curlobj);
        $data = [ROOT_PATH."Public/Home/Test/apk/".$shop_id.".png"];
        return json_encode($data);
    }
    public function test(){

        // $this->create_qrcode();
        $shop_id = session('shop_id');
        if (empty($shop_id)) {
            // $data['code'] = '4001';
            // $data['msg'] = '没有登录没有权限打开此页面，请登录！';
            // echo $data['msg'];
            // echo json_encode($data);
            header('Location: '.U('Home/Test/zhuce'));
            exit;
        }
        $User = M("the_store");
        $sms = M("sms_config");
        $shop_name = $User->where('id = '.$shop_id)->getField("shop_name");
        $sms = $sms->find("shop_name");

        if (!$shop_name) {
            $shop_name = "我的";
        }
        $bigImgPath = ROOT_PATH.'Public/Home/Test/image/tuoke_qrcode.png';
        $qCodePath = ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'.png';
        
        $bigImg = imagecreatefromstring(file_get_contents($bigImgPath));

        list($width, $height, $qCodeType) = getimagesize($qCodePath);
        $percent = '0.46';
        $new_width = $width * $percent;
        $new_height = $height * $percent;
        // 重新取样
        $image_p = imagecreatetruecolor($new_width, $new_height);
        $image = imagecreatefromjpeg($qCodePath);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagepng($image_p,ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'_2.png');
        unlink(ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'.png');

        // list($qCodeWidth, $qCodeHight, $qCodeType) = getimagesize('D:/phpStudy/PHPTutorial/WWW/test/Public/Home/Test/apk/test6.png');
        $qCodeImg = imagecreatefromstring(file_get_contents(ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'_2.png'));
         
        list($qCodeWidth, $qCodeHight, $qCodeType) = getimagesize(ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'_2.png');
        // imagecopymerge使用注解
        imagecopymerge($bigImg, $qCodeImg, 221, 592, 0, 0, $qCodeWidth, $qCodeHight, 100);
         
        list($bigWidth, $bigHight, $bigType) = getimagesize($bigImgPath);
         
         
        switch ($bigType) {
            case 1: //gif
                header('Content-Type:image/gif');
                imagegif($bigImg);
                break;
            case 2: //jpg
                header('Content-Type:image/jpg');
                // imagejpeg($bigImg);
                imagepng($bigImg,ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'_3.png');

                $img = imagecreatefromstring(file_get_contents(ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'_3.png'));
                $font = ROOT_PATH.'Public/Home/Test/css/xx.ttf';//字体
                $black = imagecolorallocate($img, 0, 0, 0);//字体颜色 RGB178 34 34 112 128 144
                $black2 = imagecolorallocate($img, 255, 0, 0);//字体颜色 RGB178 34 34 112 128 144
                $fontSize = 35;   //字体大小
                $circleSize = 0; //旋转角度
                $left = 160;      //左边距
                $top = 350;       //顶边距
                imagefttext($img, $fontSize, $circleSize, $left, $top, $black, $font, $shop_name.'小店');
                imagefttext($img, 17, $circleSize, 240, 980, $black2, $font, '长按保存分享图片');
                // imagefttext($img, $fontSize, $circleSize, 20, 300, $black, $font, '2');
                // imagejpeg($img);
                imagepng($img,ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'_3.png');
                unlink(ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'_2.png');
                break;
            case 3: //jpg
                header('Content-Type:image/png');
                // echo "string";die;
                // echo $bigImg;
                // file_put_contents('D:/phpStudy/PHPTutorial/WWW/test/Public/Home/Test/apk/test1.png', $bigImg);
                imagepng($bigImg,ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'_3.png');
                unlink(ROOT_PATH.'Public/Home/Test/apk/'.$shop_id.'_2.png');
                // imagepng($bigImg);
                break;
            default:
                # code...
                break;
        }
        // echo "string";
        // echo $bigImg;
        imagedestroy($bigImg);
        imagedestroy($qcodeImg);
    }
    public function testimg()
    {
        $this->display("tuoke_is_check");
    }
    public function shop_check()
    {
        if ($_REQUEST['is_check'] == 0) {
            $this->display("tuoke_is_check");
        }elseif ($_REQUEST['is_check'] == 2) {
            $this->display("tuoke_no_check");
        }
    }
    // public function shop_lower(){
    //  $this->display("shop_lower");
    // }
    //$filepath图片路径,$percent缩放百分比
    public function imagepress($filepath,$percent='0.5'){
    // 图片类型
    header('Content-Type: image/jpeg');
    // 获得新的图片大小
    list($width, $height) = getimagesize($filepath);
    $new_width = $width * $percent;
    $new_height = $height * $percent;
    // 重新取样
    $image_p = imagecreatetruecolor($new_width, $new_height);
    $image = imagecreatefromjpeg($filepath);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    // 输出
    return imagejpeg($image_p, null, 100);
    }
    public function shouquan()
    {
        $this->display("shouquan");
    }
    public function zhuce()
    {
        if ($_SESSION['shop_id']) {
            // header('Location: https://hqmt360.com/bossapp/Home/Test/my_tuoke');
            $this->display("login");
        }else{
            $this->display("login");           
        }
        
    }
    public function act_register()
    {
        //验证电话号码是否存在
        $user_info = M("user")->where('phone = '.$_REQUEST['mobile'])->select();
        if ($user_info) {
            echo 4;
            exit();
        }
        //验证上级推荐号是否存在
        $user_info = M("the_store")->where('id = '.$_REQUEST['recommendCode'])->select();
        if ($user_info) {
            echo 3;
            exit();
        }

        $User = M("user");
        $data['phone'] = $_REQUEST['mobile'];
        $data['sj_uid'] = $_REQUEST['recommendCode'];
        $bool = $User->add($data);
        if ($bool) {
            $_SESSION['user_id'] = $_REQUEST['mobile'];
            echo 1;
        }else{
            echo 2;
        }
        
    }
    public function login()
    {
        $user_info = M("the_store")->where('user_name = '.$_REQUEST['username'])->select();
        if (!$user_info) {
            echo 2;
            exit();
        }
        $password = M("the_store")->where('user_name = '.$_REQUEST['username'])->getField("password");
        if ($password == md5($_REQUEST['password'])) {
            $is_check = M("the_store")->where('user_name = '.$_REQUEST['username'])->getField("is_check");
            if ($is_check == 0) {
                echo 4;exit();
            }elseif ($is_check == 2) {
                echo 5;exit();
            }
            $shop_id = M("the_store")->where('user_name = '.$_REQUEST['username'])->getField("id");
            $_SESSION['shop_id'] = $shop_id;
            cookie('shop_id',$shop_id,31536000);
            // echo $_COOKIE["cookiename"];die;
            // echo "string";
            // echo $_SESSION['shop_id'];die;
            echo 1;
        }else{
            echo 3;
            exit();
        }
    }
    public function yanzhengma()
    {
        $image = imagecreate(100, 40);
        $color = imagecolorallocate($image, 255, 255, 255);
        // imagefill($image,0, 0, $color);
     
        //创建验证码
        $code = '';
        for($i=0;$i<4;$i++){
            $fontsize = 9;
            $fontcolor = imagecolorallocate($image, rand(0,120), rand(0,120), rand(0,120));
            $x = $i * 25 + 10;
            $y = rand(5,10);
            $num = (string)rand(0,9);
            $code .= $num;
            imagestring($image, $fontsize, $x, $y, $num, $fontcolor);
        }

        //验证码记录到session

        $_SESSION['code'] = $code;
        if(empty($_SESSION['code'])){
            $_SESSION['code'] = $code;
        }
        //增加干扰元素点
        for ($i=0; $i <800 ; $i++) {
            $color = imagecolorallocate($image, rand(50,200), rand(50,200), rand(50,200));
            imagesetpixel($image, rand(0,100), rand(0,40), $color);
        }
     
        //增加干扰线
        for ($i=0; $i <5 ; $i++) {
            $color = imagecolorallocate($image, rand(50,200), rand(50,200), rand(50,200));
            imageline($image, rand(10,180), rand(10,180), rand(10,180), rand(10,180), $color);
        }
        //说明这个是一个图片
        header("content-type:image/png");
        //输出到浏览器
        imagepng($image);
        //关闭
        imagedestroy($image);       # code...
    }
    public function send()
    {
        // echo "string";
        // include_once 'SmsApi.php';
        require_once ('SmsApi.php');
        // use Aliyun\DySDKLite\Sms\SmsApi;
       /* 避免空验证码提交直接绕过验证 */
        // 判断session中是否存在code参数 --modified by 翔
        // if(empty($_SESSION['code'])) {
        //     exit(json_encode(array('code' => '5','msg'=>'请刷新验证码')));
        // }

        // 判断验证码是否为空 --modified by 翔
        if(empty($_REQUEST['yanzhengma'])) {
            exit(json_encode(array('code' => '4','msg'=>'验证码为空')));
        }
        /* 避免空验证码提交直接绕过验证 */

        if($_REQUEST['yanzhengma'] != 6666){
            exit(json_encode(array('code' => '3','msg'=>'验证码错误！')));
            // exit(json_encode(array('msg' => '验证码错误！')));
        }

        if (empty($_REQUEST['mobile'])) {
            exit(json_encode(array('msg' => '手机号码不能为空')));
        }

        $preg = '/^1[0-9]{10}$/'; //简单的方法
        if (!preg_match($preg, $_REQUEST['mobile'])) {
            exit(json_encode(array('msg' => '手机号码格式不正确')));
        }

        if ($_SESSION['sms_mobile']) {
            if (strtotime(read_file($mobile)) > (time() - 60)) {
                exit(json_encode(array('msg' => '获取验证码太过频繁，一分钟之内只能获取一次。')));
            }
        }

        // $sql = "select user_id from " . $ecs->table('users') . " where user_name='" . $mobile . "'";
        // //echo $sql;die;
        // $user_id = $db->getOne($sql);
        $user_id = M("the_store")->where('user_name = '.$_REQUEST['mobile'])->select();

        if($_REQUEST['flag'] == 'register'){
            //手机注册
            if (!empty($user_id)){
                exit(json_encode(array('msg' => '手机号码已存在，请更换手机号码')));
            }
        }elseif($_GET['flag'] == 'forget'){
            
            //找回密码

            if (empty($user_id)) {
                exit(json_encode(array('msg' => "手机号码不存在\n无法通过该号码找回密码")));
            }
        }

        $mobile_code = $this->random(4, 1);
        $sms_name = M("other_config")->where('`key` = "sms_sign"')->getField("value");
        $zhuce_sms_code = M("other_config")->where('`key` = "zhuce"')->getField("value");
        $sms_ak = M("other_config")->where('`key` = "sms_ak"')->getField("value");
        $sms_as = M("other_config")->where('`key` = "sms_as"')->getField("value");
        if($_REQUEST['yanzhengma'] != NULL){
            $sms = new SmsApi($sms_ak, $sms_as); // 请参阅 https://ak-console.aliyun.com/ 获取AK信息
            $response = $sms->sendSms(
                $sms_name, // 短信签名 环球美淘
                $zhuce_sms_code, // 短信模板编号
                $_REQUEST['mobile'], // 短信接收者
                Array (  // 短信模板中字段的值
                    "code"=> $mobile_code
                    
                ),
                "123"   // 流水号,选填
            );
        }
        // print_r($response);
        if(intval($result[1])==0){
            $_SESSION['sms_mobile'] = $mobile;
            $file_path="time3.txt";
            $_SESSION['sms_mobile_code'] = $mobile_code;//把手机获取到的验证码存在session
            $data['code']=1;
            $data['mobile_code']=$mobile_code;
            echo json_encode($data);//短信验证码发送成功！
        }else{
             $data['code']=2;
             $data['msg']='短信验证码发送失败！';
             echo json_encode($data);//短信验证码发送失败！
            //echo "未连接上服务器";
        }
    }
    // public function sss()
    // {
    //     $shop_id = M("the_store")->where('user_name = "13726215377"')->getField("id");
    //     $is_check = M("other_config")->where('`key` = "sms_sign"')->getField("value");
    //     echo $is_check;
    // }
    public function send_pwd_sms()
    {
        require_once ('SmsApi.php');
        // include_once(ROOT_PATH . 'include/lib_passport.php');
        /* 初始化会员手机 */
        $mobile = !empty($_POST['mobile']) ? trim($_POST['mobile']) : '';
            //生成新密码
        $newPwd = $this->random(6, 1);
        $sms_name = M("other_config")->where('`key` = "sms_sign"')->getField("value");
        $send_new_pass = M("other_config")->where('`key` = "send_new_pass"')->getField("value");
        $sms_ak = M("other_config")->where('`key` = "sms_ak"')->getField("value");
        $sms_as = M("other_config")->where('`key` = "sms_as"')->getField("value");
        $sms = new SmsApi($sms_ak, $sms_as); // 请参阅 https://ak-console.aliyun.com/ 获取AK信息
        $response = $sms->sendSms($sms_name,$send_new_pass, $_REQUEST['mobile'],array('password' => $newPwd));
        // print_r($response);
        if($response !=''){
            // $sql="UPDATE ".$ecs->table('users'). "SET `ec_salt`='0',password='". md5($newPwd) ."' WHERE user_name= '".$mobile."'";
            // $res = $db->query($sql);
            $User = M("the_store"); 
            $User->password = md5($newPwd);
            $bool = $User->where('user_name='.$_REQUEST['mobile'])->save();
            if($bool){
                // echo 1;
                $data['code'] = 1;
                $data['msg'] = "修改密码成功";
            }else{
                // echo 2;
               $data['code'] = 2;
                $data['msg'] = "修改密码失败";
            }         
        }else{
            // echo 2;
            $data['code'] = 2;
            $data['msg'] = "修改密码失败";
        }  

        echo json_encode($data);
    }

    //忘记密码
    public function forget()
    {
        require_once ('SmsApi.php');

        // use Aliyun\DySDKLite\Sms\SmsApi;
       /* 避免空验证码提交直接绕过验证 */
        // 判断session中是否存在code参数 --modified by 翔
        // if(empty($_SESSION['code'])) {
        //     exit(json_encode(array('code' => '5','msg'=>'请刷新验证码')));
        // }

        // 判断验证码是否为空 --modified by 翔
        if(empty($_REQUEST['yanzhengma'])) {
            exit(json_encode(array('code' => '4','msg'=>'验证码为空')));
        }
        /* 避免空验证码提交直接绕过验证 */

        if($_REQUEST['yanzhengma'] != 6666){
            exit(json_encode(array('code' => '3','msg'=>'验证码错误！')));
            // exit(json_encode(array('msg' => '验证码错误！')));
        }

        if (empty($_REQUEST['mobile'])) {
            exit(json_encode(array('msg' => '手机号码不能为空')));
        }

        $preg = '/^1[0-9]{10}$/'; //简单的方法
        if (!preg_match($preg, $_REQUEST['mobile'])) {
            exit(json_encode(array('msg' => '手机号码格式不正确')));
        }

        if ($_SESSION['sms_mobile']) {
            if (strtotime(read_file($mobile)) > (time() - 60)) {
                exit(json_encode(array('msg' => '获取验证码太过频繁，一分钟之内只能获取一次。')));
            }
        }

        // $sql = "select user_id from " . $ecs->table('users') . " where user_name='" . $mobile . "'";
        // //echo $sql;die;
        // $user_id = $db->getOne($sql);
        $user_id = M("the_store")->where('user_name = '.$_REQUEST['mobile'])->select();

        if($_REQUEST['flag'] == 'register'){
            //手机注册
            if (!empty($user_id)){
                exit(json_encode(array('msg' => '手机号码已存在，请更换手机号码')));
            }
        }elseif($_GET['flag'] == 'forget'){
            //找回密码
            if (empty($user_id)) {
                exit(json_encode(array('msg' => "手机号码不存在\n无法通过该号码找回密码")));
            }
        }
        $mobile_code = $this->random(4, 1);
        if($_REQUEST['yanzhengma'] != NULL){
            $sms_ak = M("other_config")->where('`key` = "sms_ak"')->getField("value");
            $sms_as = M("other_config")->where('`key` = "sms_as"')->getField("value");
            $sms_name = M("other_config")->where('`key` = "sms_sign"')->getField("value");
            $forget_code = M("other_config")->where('`key` = "forget_code"')->getField("value");
            $sms = new SmsApi($sms_ak, $sms_as); // 请参阅 https://ak-console.aliyun.com/ 获取AK信息
            $response = $sms->sendSms(
                $sms_name, // 短信签名 环球美淘
                $forget_code, // 短信模板编号
                $_REQUEST['mobile'], // 短信接收者
                Array (  // 短信模板中字段的值
                    "code"=> $mobile_code
                ),
                "123"   // 流水号,选填
            );
        }
        // print_r($_REQUEST['mobile']);
        if(intval($result[1])==0){
            $_SESSION['sms_mobile'] = $mobile;
            $_SESSION['sms_mobile_code'] = $mobile_code;
            // print_r($_SESSION['sms_mobile_code']);
            //把手机获取到的验证码存在session
            $data['code']=1;
            $data['mobile_code']=$mobile_code;
            echo json_encode($data);//短信验证码发送成功！
        }else{
             $data['code']=2;
             $data['msg']='短信验证码发送失败！';
             echo json_encode($data);//短信验证码发送失败！
            //echo "未连接上服务器";
        }
    }

    public function forget_check()
    {
        // $user_info = M("the_store")->where('user_name = '.$_REQUEST['mobile'])->select();
        // if ($user_info) {
        //  echo 4;
        //  exit();
        // }
        // //验证上级推荐号是否存在
        // $user_info = M("the_store")->where('user_name = '.$_REQUEST['mobile'])->select();
        // if ($user_info) {
        //  echo 3;
        //  exit();
        // }
        // print_r($_SESSION['sms_mobile_code']);
        // echo "string";
        // print_r($_REQUEST['mobile_code']);
        if ($_REQUEST['mobile_code'] == $_SESSION['sms_mobile_code']) {
            echo 1;
        }else{
            echo 2;
        }
        // $User = M("the_store");
        // $data['user_name'] = $_REQUEST['mobile'];
        // $data['sj_id'] = $_REQUEST['recommendCode'];
        // $data['password'] = md5($_REQUEST['password']);
        // $data['add_time'] = time();
        // $data['shop_type'] = '0';
        // // $data['sj_uid'] = $_REQUEST['recommendCode'];
        // $bool = $User->add($data);
        // if ($bool) {
        //  $_SESSION['user_id'] = $_REQUEST['mobile'];
        //  echo 1;
        // }else{
        //  echo 2;
        // }
    }
    public function random($length = 6, $numeric = 0) {
        PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }
    public function check()
    {
        if ($_REQUEST['mobile_code'] != $_SESSION['sms_mobile_code']) {
            echo 5;
            exit();
        }
        //验证电话号码是否存在
        $user_info = M("the_store")->where('user_name = '.$_REQUEST['mobile'])->select();
        if (!empty($user_info)) {
            echo 4;
            exit();
        }
        //验证上级推荐号是否存在
        $user_info2 = M("the_store")->where('id = '.$_REQUEST['recommendCode'])->find();
        if (empty($user_info2)) {
            echo 3;
            exit();
        }
        $rank = D('store_rank');
        $maxrank = $rank->max("rank_id");
        if ($user_info2['shop_rank'] != $maxrank) {
            $data['zd_shop_id'] = $user_info2['zd_shop_id'];
        }else{
             $data['zd_shop_id'] = $user_info2['id'];
        }
        // dump($user_info2);die;
        $User = M("the_store");
        $data['user_name'] = $_REQUEST['mobile'];
        $data['sj_id'] = $_REQUEST['recommendCode'];
        $data['password'] = md5($_REQUEST['password']);
        $data['add_time'] = time();
        $data['shop_type'] = '0';
        // $data['sj_uid'] = $_REQUEST['recommendCode'];
        $bool = $User->add($data);

        // $User2 = M('shop_earnings');
        // $data2['shop_id'] = 
        if ($bool) {
            $_SESSION['user_id'] = $_REQUEST['mobile'];
            echo 1;
        }else{
            echo 2;
        }
    }

}
