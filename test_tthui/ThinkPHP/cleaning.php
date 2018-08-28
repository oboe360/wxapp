<?php
header('content-type:text/html;charset=utf-8');
require '/ThinkPHP.php';
//echo '12312';exit;
clear();

/*店铺周期业绩结算函数 */  
function clear(){
        $the_store = D('the_store');
        $shop_achievement = D('shop_achievement');
        $shop_achievement_sn = D('shop_achievement_sn');
        //$earnings = D('shop_earnings');
        $shop_earnings_record = D('shop_earnings_record');

        $shop_list = $the_store->query("SELECT `shop_rank`, `zd_shop_id`, `shop_type`, `id`, `sj_id`, (SELECT `number` FROM `app_shop_achievement` WHERE `shop_id` = `a`.`id` ORDER BY `id` DESC LIMIT 1) AS `number`, (SELECT `shop_rank` FROM `app_the_store` WHERE `id` = `a`.`sj_id`) AS `sj_rank` FROM `app_the_store` AS a WHERE a.`is_check` = '1' ORDER BY a.`shop_rank` DESC");

        //dump($shop_list);exit;
        //每个星期日的00:00
        $t_time = date('Y-m-d', strtotime('last day this week'));
        $with['ment_time'] = strtotime($t_time); //604800
        $with['ment_str_time'] = $with['ment_time'] - 604800;
        $with['time'] = time();
        //dump($with);exit;
        global $earnings;

        //默认所有的订单号
        //dump(count($shop_list));exit;
        for($i = 0; $i < count($shop_list); $i++){
          $rechor = $shop_earnings_record->query("SELECT `shop_id`, `buyid`, `money`, `order_sn`, `order_money` FROM `app_shop_earnings_record` WHERE `is_tixian` != '2' AND add_time <= '{$with[ment_time]}' AND `cleaning` = '0' AND `shop_id` = '{$shop_list[$i][id]}' AND add_time >= '{$with[ment_str_time]}'");
          
          //dump($rechor);
          //echo "SELECT `shop_id`, `buyid`, `money`, `order_sn`, `order_money` FROM `app_shop_earnings_record` WHERE `is_tixian` != '2' AND add_time <= '{$with[ment_time]}' AND `cleaning` = '0' AND `shop_id` = '{$shop_list[$i][id]}' AND add_time >= '{$with[ment_str_time]}'";exit;
          //dump($rechor);exit;

          $str = '';
          $return = array('order_sn' => '', 'sum_money' => 0);

          if(!empty($rechor)){
            //dump($shop_list[$i]);
            //$arr = $this->clear_with($rechor, $shop_list[$i]);
            for($k = 0; $k < count($rechor); $k++){
              $return['order_sn'] .= "'".$rechor[$k]['order_sn']."',";
              $return['sum_money'] += $rechor[$k]['order_money'];
            }
            //dump($return);
            $str = trim($return['order_sn'], ',');
            //dump($shop_list[$i]['shop_rank']);
            //判断是什么等级的用户
            if($shop_list[$i]['shop_rank'] == '1'){

              if($shop_list[$i]['zd_shop_id'] > 0){
                $earnings[$shop_list[$i]['zd_shop_id']]['money'] += $return['sum_money'];
                $earnings[$shop_list[$i]['zd_shop_id']]['shop_list'] .= $shop_list[$i]['id'].',';
              }

              if($shop_list[$i]['sj_rank'] == '2'){
                $earnings[$shop_list[$i]['sj_id']]['money'] += $return['sum_money'];
                $earnings[$shop_list[$i]['sj_id']]['shop_list'] .= $shop_list[$i]['id'].',';
              }

            }else if($shop_list[$i]['shop_rank'] == '2'){

              $earnings[$shop_list[$i]['id']]['shop_id'] = $shop_list[$i]['id'];
              $earnings[$shop_list[$i]['id']]['shop_rank'] = $shop_list[$i]['shop_rank'];
              $earnings[$shop_list[$i]['id']]['add_time'] = $with['time'];
              $earnings[$shop_list[$i]['id']]['number'] = !empty($shop_list[$i]['number']) ? $shop_list[$i]['number'] + 1 : 1;
              $earnings[$shop_list[$i]['id']]['start_time'] = $with['ment_str_time'];
              $earnings[$shop_list[$i]['id']]['end_time'] = $with['ment_time'];

              $earnings[$shop_list[$i]['id']]['money'] += $return['sum_money'];
              $earnings[$shop_list[$i]['id']]['shop_list'] .= $shop_list[$i]['id'].',';
              //dump($earnings);exit;
              if($shop_list[$i]['zd_shop_id'] > 0){
                $earnings[$shop_list[$i]['zd_shop_id']]['money'] += $return['sum_money'];
                $earnings[$shop_list[$i]['zd_shop_id']]['shop_list'] .= $shop_list[$i]['id'].',';
              }

              if($shop_list[$i]['sj_rank'] == '2'){
                $earnings[$shop_list[$i]['sj_id']]['money'] += $return['sum_money'];
                $earnings[$shop_list[$i]['sj_id']]['shop_list'] .= $shop_list[$i]['id'].',';
              }

            }else if($shop_list[$i]['shop_rank'] == '3'){

              $earnings[$shop_list[$i]['id']]['shop_id'] = $shop_list[$i]['id'];
              $earnings[$shop_list[$i]['id']]['shop_rank'] = $shop_list[$i]['shop_rank'];
              $earnings[$shop_list[$i]['id']]['add_time'] = $with['time'];
              $earnings[$shop_list[$i]['id']]['number'] = !empty($shop_list[$i]['number']) ? $shop_list[$i]['number'] + 1 : 1;
              $earnings[$shop_list[$i]['id']]['start_time'] = $with['ment_str_time'];
              $earnings[$shop_list[$i]['id']]['end_time'] = $with['ment_time'];
              //dump($return['sum_money']);exit;
              $earnings[$shop_list[$i]['id']]['money'] += $return['sum_money'];
              $earnings[$shop_list[$i]['id']]['shop_list'] .= $shop_list[$i]['id'].',';
            }
            //dump($earnings);
            $datap['a_number'] = !empty($shop_list[$i]['number']) ? $shop_list[$i]['number'] + 1 : 1;
            $datap['shop_id'] = $shop_list[$i]['id'];
            $datap['start_time'] = $with['ment_str_time'];
            $datap['end_time'] = $with['ment_time'];
            $datap['total_money'] = $return['sum_money'];
            $datap['order_list'] = $str;
            $datap['add_time'] = $with['time'];
            //dump($datap);
            $res = $shop_achievement_sn->add($datap);
           	$res1 = $shop_earnings_record->execute("UPDATE `app_shop_earnings_record` SET `cleaning` = '1' WHERE `shop_id` = '{$shop_list[$i]['id']}' AND `order_sn` IN({$str})");
            //dump($res);
          }
        }
        $rank = D('store_rank');
        $maxrank = $rank->select();
        $max_rank = array();
        for($a = 0; $a < count($maxrank); $a++){
          $max_rank[$maxrank[$a]['rank_id']] = $maxrank[$a]['discount'] / 100;
        }
        //dump($max_rank);

        //dump($earnings);

        if(!empty($earnings)){
          foreach($earnings as $key => $val){
            if($val['shop_id'] != 0){
              $val['shop_list'] = trim($val['shop_list'], ',');
              $val['income_money'] = sprintf("%.2f", $val['money'] * $max_rank[$val['shop_rank']]);
              $val['total_money'] = sprintf("%.2f", $val['money']);
              //dump($val);
              $res2 = $shop_achievement->add($val);
            }
            //dump($res2);
          }
        }
        //dump($res);dump($res1);dump($res2);
        if($res && $res1 && $res2){
          echo '修改成功！';
        }
        
    }

?>