<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
/*每周结算店铺业绩 自动执行文件*/
class ShopAchievementController extends BaseController
{
	private $shop_earnings = NULL;
	private $shop_achievement = NULL;
	private $achievement_sn = NULL;
	private $earnings = NULL;
	private $store = NULL;
	public function _initializes(){
		!is_null($this->shop_earnings)?:$this->shop_earnings = D('shop_earnings_record');
		!is_null($this->earnings)?:$this->earnings = D('shop_earnings');
		!is_null($this->shop_achievement)?:$this->shop_achievement = D('shop_achievement');
		!is_null($this->achievement_sn)?:$this->achievement_sn = D('shop_achievement_sn');
		!is_null($this->store)?:$this->store = D('the_store');
	}
	
    public function lists(){//店收益列表
    	//$this ->clear();
        $earninge = $this->earnings_list();
        //dump($earninge);
        $this -> assign('limit', $earninge['limit']);
        $this -> assign('shop_earnings', $earninge['shop_earnings']);
        $this -> display('shop_achievement');

}
//所有店铺收益ajax返回
    public function earnings_query(){
        $earninge = $this->earnings_list();
        $this->ajaxReturn($earninge);exit;
    }

//店铺收益周期列表
public function shop_achievement_list(){
    // dump($_REQUEST);die;
    $achievement_detail = $this->achievement_detail();
    //dp($earnings_record);die;
    $this -> assign('type', $achievement_detail['type']);
    $this -> assign('tixian', $achievement_detail['tixian']);
    $this -> assign('achievement_detail', $achievement_detail['achievement_detail']);
    $this -> assign('order_type', $this->order_type);
    $this->assign('is_tixian', $this->is_tixian);
    $this -> assign('limit', $achievement_detail['limit']);
    $this -> display('shop_achievement_list');
}
//店铺收益订单详情ajax返回
public function shop_achievement_list_query(){
    //echo '123';
    $earnings_record = $this->achievement_detail();
    $this->ajaxReturn($earnings_record);exit;
}

//店铺收益周期业绩报表详情
public function achievement_sn(){
    $achievement_sn_detail = $this->achievement_sn_detail();
    //dp($achievement_sn_detail);die;
    $this -> assign('type', $achievement_sn_detail['type']);
    $this -> assign('tixian', $achievement_sn_detail['tixian']);
    $this -> assign('achievement_sn_detail', $achievement_sn_detail['achievement_sn_detail']);
    $this -> assign('order_type', $this->order_type);
    $this->assign('is_tixian', $this->is_tixian);
    $this -> assign('limit', $achievement_sn_detail['limit']);
    $this -> display('shop_achievement_detail');
}
    /**
    *-------返回店铺收益的数据处理
    */
    public function earnings_list(){
        $limit['page_num'] = $_POST['page_num'] ? trim($_POST['page_num']) : '15';
        $limit['page'] = $_POST['page'] ? trim($_POST['page']) : '1';
        $where['status'] = $_POST['status'];
        $where['shop_id'] = !empty($_POST['shop_id']) ? $_POST['shop_id'] : '';
        $where['shop_name'] = !empty($_POST['shop_name']) ? $_POST['shop_name'] : '';
        $where['user_name'] = !empty($_POST['user_name']) ? $_POST['user_name'] : '';
        $where['end_time'] = !empty($_POST['end_time']) ? $_POST['end_time'] : '';
        $where['sta_time'] = !empty($_POST['sta_time']) ? $_POST['sta_time'] : '';
        //dump($_POST);
        //dump($where);
        $wheres = ' shop_rank > 1 ';
        if($where['status'] != ''){
            $wheres .= " AND a.`shop_type` = '{$where['status']}'";
        }
        if(!empty($where['shop_id'])){
            $wheres .= " AND a.`id` = '{$where['shop_id']}'";
        }
        if(!empty($where['shop_name'])){
            $wheres .= " AND a.`shop_name` LIKE '%".$where['shop_name']."%'";
        }
        if(!empty($where['user_name'])){
            $wheres .= " AND a.`user_name` LIKE '%".$where['user_name']."'";
        }
        if(!empty($where['end_time'])){
            $wheres .= " AND b.`add_time` <= '".strtotime($where['end_time'])."'";
        }
        if(!empty($where['sta_time'])){
            $wheres .= " AND b.`add_time` >= '".strtotime($where['sta_time'])."'";
        }
        //echo $wheres;

        $count = D()->query("SELECT COUNT(*) AS `count` FROM `app_the_store` AS a INNER JOIN `app_shop_earnings` AS b ON a.`id` = b.`shop_id` WHERE {$wheres}");// 查询满足要求的总记录数
        $limit['count'] = $count['0']['count'];
        $limit['page_mun'] = ceil($limit['count'] / $limit['page_num']);
        if($limit['page'] < '1'){
          $limit['page'] = 1;
        }
        if($limit['page'] > $limit['page_mun']){
          $limit['page'] = $limit['page_mun'];
        }
        $start = (($limit['page'] - 1) * $limit['page_num']);
        if($start < 0){
            $start = 0;
        }
        $shop_earnings = D()->query("SELECT a.`id` AS `shop_id`, a.`user_name`, a.`shop_name`, a.`shop_type`, b.`money`, b.`the_time` FROM `app_the_store` AS a INNER JOIN `app_shop_earnings` AS b ON a.`id` = b.`shop_id` WHERE {$wheres} LIMIT {$start}, {$limit['page_num']}");
        foreach ($shop_earnings as $k => $v) {
            $shop_achievement = D('shop_achievement');
            $wh['shop_id'] = $v[shop_id];
            $arr = $shop_achievement->where($wh)->select();
            $total_money =0;
            $tx_money = 0;
            $notx_money = 0;
            foreach ($arr as $key => $va) {
                if($arr){
                    $total_money +=$va['income_money'];
                    $txwh['shop_id'] = $va[shop_id];
                    $txwh['is_tixian'] = 1;
                    $txarr = $shop_achievement->field("sum(income_money) as tx_money")->where($txwh)->find();
                    $tx_money=$txarr['tx_money'];//已提现
                    $no_txwh['shop_id'] = $va[shop_id];
                    $no_txwh['is_tixian'] = 0;
                    $notxarr = $shop_achievement->field("sum(income_money) as notx_money")->where($no_txwh)->find();
                    $notx_money=$notxarr['notx_money'];//未提现 
                }
              
            }
            $shop_earnings[$k][total_money]= $total_money? $total_money:0;
            $shop_earnings[$k][tx_money]=  $tx_money?$tx_money:0;
            $shop_earnings[$k][notx_money]=  $notx_money?$notx_money:0;
        }
        //dp($shop_earnings);die;
         return array('shop_earnings' => $shop_earnings, 'limit' => $limit);
    }  
    /**
    *-------返回店铺收益的数据处理
    */
    public function achievement_detail(){
        $limit['page_num'] = $_POST['page_num'] ? trim($_POST['page_num']) : '15';
        $limit['page'] = $_POST['page'] ? trim($_POST['page']) : '1';
        $where['is_tixian'] = $_POST['is_tixian'] != '' ? $_POST['is_tixian'] : '';
        $where['end_time'] = !empty($_POST['end_time']) ? $_POST['end_time'] : '';
        $where['sta_time'] = !empty($_POST['sta_time']) ? $_POST['sta_time'] : '';
        $where['achievement_sn'] = !empty($_REQUEST['achievement_sn']) ? $_REQUEST['achievement_sn'] : '';
        //($_POST);
        // print_r($_REQUEST);die;
        $wheres = ' 1 ';
        if($where['is_tixian'] != ''){
            $wheres .= " AND `is_tixian` = '".$where['is_tixian']."'";
        }
        if(!empty($where['end_time'])){
            $wheres .= " AND `add_time` <= '".strtotime($where['end_time'])."'";
        }
        if(!empty($where['sta_time'])){
            $wheres .= " AND `add_time` >= '".strtotime($where['sta_time'])."'";
        }
        if (!empty($where['achievement_sn'])) {
            $wheres .= " AND `id` in ( ".$where['achievement_sn'].")";
            // echo $wheres;die;
        }
        if(!empty($_GET['shop_id'])){
            $shop_id = $_GET['shop_id'] ? $_GET['shop_id'] : $_POST['id'];
            // $arr = $this->shop_achievement ->field("shop_list")->where()->find();

            $wheres .= " AND  `shop_id` = ".$shop_id;
          
        }
        // dump($wheres);die;
        //echo "SELECT COUNT(*) AS `count` FROM `app_shop_earnings_record` AS a WHERE {$wheres}";
        $count = D()->query("SELECT COUNT(*) AS `count` FROM `app_shop_achievement`  WHERE {$wheres}");// 查询满足要求的总记录数
        //$sql = "SELECT COUNT(*) AS `count` FROM `app_shop_achievement_sn` AS a WHERE {$wheres}";
        //dp($sql);die;
        $limit['count'] = $count['0']['count'];
        //dp($limit);die;
        $limit['page_mun'] = ceil($limit['count'] / $limit['page_num']);
        if($limit['page'] < '1'){
          $limit['page'] = 1;
        }
        if($limit['page'] > $limit['page_mun']){
          $limit['page'] = $limit['page_mun'];
        }
        $start = (($limit['page'] - 1) * $limit['page_num']);
        if($start < 0){
            $start = 0;
        }
       // $earnings_record = D()->query("SELECT *, (SELECT `shop_name` FROM `app_the_store` WHERE `id` = a.`shop_id`) AS buy_name FROM `app_shop_achievement_sn` AS a WHERE {$wheres} LIMIT {$start}, {$limit['page_num']}");

        //$the_store = D("shop_achievement_sn");
            //dp($wheres);
        $achievement_detail = $this->shop_achievement->where($wheres)->order("number desc")->limit($start,$limit[page_num])->select();//业绩详情
            foreach ($achievement_detail as $k => $v) {
                $wh['id']=$v['shop_id'];
                $row=$this->store->field("shop_name,user_name")->where($wh)->find();
                $achievement_detail[$k]['shop_name']=$row['shop_name'];
                $achievement_detail[$k]['shop_phone']=$row['user_name']?$row['user_name']:0;
                $achievement_detail[$k]['start_time']=date("Y-m-d H:i:s",$v['start_time']);
                $achievement_detail[$k]['end_time']=date("Y-m-d H:i:s",$v['end_time']);
                $achievement_detail[$k]['add_time']=date("Y-m-d H:i:s",$v['add_time']);
                $achievement_detail[$k]['num']=$k+1;
                if($v['is_tixian']==0){
                    $achievement_detail[$k]['is_tixian']="未提现";   
                }else if($v['is_tixian']==1){
                    $achievement_detail[$k]['is_tixian']="已提现";
                }else{
                     $achievement_detail[$k]['is_tixian']="已注销"; 
                }


            }
       // dp($achievement_detail);die;
        return array('achievement_detail' => $achievement_detail, 'limit' => $limit, 'tixian' => $tixian, 'type' => $type);
    }
    /*
    *相关店铺业绩相关
    *
    */
  public function achievement_sn_detail(){
        // dump($_REQUEST);die;
        $limit['page_num'] = $_POST['page_num'] ? trim($_POST['page_num']) : '15';
        $limit['page'] = $_POST['page'] ? trim($_POST['page']) : '1';
        $where['order_type'] = $_POST['order_type'];
        $where['buyid'] = !empty($_POST['buyid']) ? $_POST['buyid'] : '';
        $where['is_tixian'] = $_POST['is_tixian'] != '' ? $_POST['is_tixian'] : '';
        $where['end_time'] = !empty($_POST['end_time']) ? $_POST['end_time'] : '';
        $where['sta_time'] = !empty($_POST['sta_time']) ? $_POST['sta_time'] : '';
        //dump($_POST);
        // dump($where);die;
        $wheres = ' 1 ';
        if($where['order_type'] != ''){
            $wheres .= " AND a.`order_type` = '{$where['order_type']}'";
        }
        if(!empty($where['buyid'])){
            $wheres .= " AND a.`buyid` = '{$where['buyid']}'";
        }
        if($where['is_tixian'] != ''){
            $wheres .= " AND a.`is_tixian` = '".$where['is_tixian']."'";
        }
        if(!empty($where['end_time'])){
            $wheres .= " AND a.`add_time` <= '".strtotime($where['end_time'])."'";
        }
        if(!empty($where['sta_time'])){
            $wheres .= " AND a.`add_time` >= '".strtotime($where['sta_time'])."'";
        }
        if(!empty($_GET['is_tixian'])){
            // $tixian_id = $_GET['tixian_id'] ? $_GET['tixian_id'] : $_POST['id'];
            // //dump(123);
            // $tixian = D('shop_tixian')->where("id='{$tixian_id}'")->find();
            // $tixian['order_count'] = 0;
            // $order_list = explode(',', $tixian['all_order_sn']);
            // $all_order_sn = '';
            // for($i = 0; $i < count($order_list); $i++){
            //     $all_order_sn .= "'".$order_list[$i]."',";
            // }
            // $all_order_sn = trim($all_order_sn, ',');
            // $wheres .= " AND a.`order_sn` in({$all_order_sn})";
            // $type['type'] = 'tixian';
            // $type['id'] = $tixian_id;
             $wheres .= " AND  `is_tixian` = ".$number;
            
        }

        if(!empty($_GET['shop_list'])){
            $shop_list = $_GET['shop_list'] ? $_GET['shop_list'] : $_POST['shop_list'];
            // $arr = $this->shop_achievement ->field("shop_list")->where()->find();
            $wheres .= " AND  `shop_id` in "."(".$shop_list.")";
        }
         if(!empty($_GET['number'])){
            $number = $_GET['number'] ? $_GET['number'] : $_POST['number'];
            // $arr = $this->shop_achievement ->field("shop_list")->where()->find();
            //dp($number);die;
            $wheres .= " AND  `a_number` = ".$number;
        }
        //dp($wheres);die;
        //echo "SELECT COUNT(*) AS `count` FROM `app_shop_earnings_record` AS a WHERE {$wheres}";
        $count = D()->query("SELECT COUNT(*) AS `count` FROM `app_shop_achievement_sn` AS a WHERE {$wheres}");// 查询满足要求的总记录数
        $sql = "SELECT COUNT(*) AS `count` FROM `app_shop_achievement_sn` AS a WHERE {$wheres}";
    
        $limit['count'] = $count['0']['count'];
       // dp($limit );die;
        $limit['page_mun'] = ceil($limit['count'] / $limit['page_num']);
        if($limit['page'] < '1'){
          $limit['page'] = 1;
        }
        if($limit['page'] > $limit['page_mun']){
          $limit['page'] = $limit['page_mun'];
        }
        $start = (($limit['page'] - 1) * $limit['page_num']);
        if($start < 0){
            $start = 0;
        }

        // dump($limit);die;
       // $earnings_record = D()->query("SELECT *, (SELECT `shop_name` FROM `app_the_store` WHERE `id` = a.`shop_id`) AS buy_name FROM `app_shop_achievement_sn` AS a WHERE {$wheres} LIMIT {$start}, {$limit['page_num']}");

        //$the_store = D("shop_achievement_sn");fetchSql()->w
            //dp($wheres);

        $achievement_sn_detail = $this->achievement_sn->where($wheres)->order("sn_id desc")->limit($start,$limit[page_num])->select();//业绩详情
            // echo $achievement_sn_detail;
            foreach ($achievement_sn_detail as $k => $v) {
                $wh['id']=$v['shop_id'];
                $row=$this->store->field("shop_name,shop_phone")->where($wh)->find();
                $achievement_sn_detail[$k]['shop_name']=$row['shop_name'];
                $achievement_sn_detail[$k]['shop_phone']=$row['shop_phone'];
                $achievement_sn_detail[$k]['start_time']=date("Y-m-d H:i:s",$v['start_time']);
                $achievement_sn_detail[$k]['end_time']=date("Y-m-d H:i:s",$v['end_time']);
                $achievement_sn_detail[$k]['add_time']=date("Y-m-d H:i:s",$v['add_time']);
                $achievement_sn_detail[$k]['num']=$k+1;
            }

       // dp($achievement_detail);die;



        return array('achievement_sn_detail' => $achievement_sn_detail, 'limit' => $limit, 'tixian' => $tixian, 'type' => $type);
    }
/*店铺周期业绩结算函数 */  
public function clear(){
        $earnings = $this->earnings->alias('e')->field('e.*,s.*')->join(" left join app_the_store as s on s.id=e.shop_id")->where("shop_rank >1")->select();
        ///dp($earnings);die;
        $rank = D('store_rank');
        $maxrank = $rank->max("rank_id");
        foreach ($earnings as $k => $v) {
            if($v[shop_rank]==$maxrank){
                $arr  = $this->store->field('id')->where("zd_shop_id = $v[id]")->select();
                $earnings[$k]['shop_arr'] =$arr;

            }else{
                $arr  = $this->store->field('id')->where("sj_id = $v[id]")->select();
                $earnings[$k]['shop_arr'] =$arr;
            }
        }
        //dp($earnings);die;
        foreach ($earnings as $k => $v){
            $str ="";
            foreach ($v['shop_arr'] as $ke => $va){
                $str.= $va["id"] . ",";
                $b = rtrim($str,",");
                $wh['shop_id']=$va['id'];
                $arr = $this->shop_earnings->where($wh)->select();
                 $order_money = 0;
                 $list = '';
                foreach ($arr as $key => $value) {
                    $order_money +=$value['order_money'];
                    $list.= $value["order_sn"] . ",";
                    $order_list = rtrim($list,",");
                }
                $earnings[$k]['shop_arr'][$ke]['order_money'] = $order_money;//下级每个店铺的周业绩
                $earnings[$k]['shop_arr'][$ke]['order_list'] = $order_list;//每个店铺周业绩相关 的订单号
            }
            $earnings[$k]['shop_list'] = $b;//周期内所有下级店铺的id
            $where['shop_id']=array('in',$b);
            $orderarr = $this->shop_earnings->where($where)->select();
                $total_money = "";
             foreach ($orderarr as $key => $order) {
                $total_money +=$order['order_money'];//周期内所有下级店铺的总业绩
             }
           // dp($row['total_money']);
            $earnings[$k]['total_money'] = $total_money;
            //$earnings[$k]['orderarr'] = $orderarr;
    }
//dp($earnings);die;
    foreach ($earnings as $k => $v) {
            if($v['shop_rank']==$maxrank){
                  //
                $rankrow =$rank->field("discount")->where("rank_id = $v[shop_rank]")->find();
                $discount=$rankrow['discount']/100;
                $arr['shop_id'] =$v['shop_id'];
                $arr['total_money'] =$v['total_money'];
                $arr['income_money'] = sprintf("%.2f", $v['total_money']*$discount);
                $arr['shop_list'] =$v['shop_list'];
                $arr['start_time'] =time();
                $arr['end_time'] =time();
                $arr['add_time'] =time();
                $res = $this->shop_achievement->add($arr);
            }else{
                $rankrow =$rank->field("discount")->where("rank_id = $v[shop_rank]")->find();
                $discount=$rankrow['discount']/100;
                $arr['shop_id'] =$v['shop_id'];
                $arr['total_money'] =$v['total_money'];
                $arr['income_money'] = sprintf("%.2f", $v['total_money']*$discount);
                $arr['shop_list'] =$v['shop_list'];
                $arr['start_time'] =time();
                $arr['end_time'] =time();
                $arr['add_time'] =time();
                $res = $this->shop_achievement->add($arr);
            }
         // dp($v['shop_arr']);
           foreach ($v['shop_arr'] as $key => $va) {
               
                //dp($va['order_money']);
               if($va['order_money']!='0'){
                    $data['shop_id'] =$va['id'];
                    $data['total_money'] =$va['order_money'];
                    $data['order_list'] =$va['order_list'];
                    $data['start_time'] =time();
                    $data['end_time'] =time();
                    $data['add_time'] =time();
                   // dp($data);
                    //$result = $this->achievement_sn->fetchsql()->add($data);
                    $result = $this->achievement_sn->add($data);
                    dp($result);
               }
              
           }
               
        }
    }
}
