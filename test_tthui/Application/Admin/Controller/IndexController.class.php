<?php
namespace Admin\Controller;
header('content-type:text/html;charset=utf-8');
use Admin\Controller\BaseController;
use Admin\Controller\AdminuserController;
class IndexController extends BaseController {
  public function setCookie(){
    //设置分页页数
        $data = I('get.');
        if($data){
          foreach ($data as $k => $v) {
            cookie($k,$v); 
          }
        }elseif(!cookie('pageshop')){
           cookie('pageshop',15); 
        }
        echo cookie('pageshop');
  }
  public function index()
    {
      $obj = new AdminuserController();
      $data = $obj->actionName();
      // dump($data);
      // exit;
      $this->assign('arr',$data);
      $this->display();  
    }
    public function body()
    {
      // dump(explode('-','4-3'));
      //查询所有订单
      $list = M('OrderInfo')->field('order_status')->select();
      //创建空数组，存储统计数据
      $data = array();
      //将所有统计数据初始化为0
      if(!isset($data['uncomfirmed'])){
        $data['uncomfirmed'] = 0;
      }
      if(!isset($data['payed'])){
        $data['payed'] = 0;
      }
      if(!isset($data['cancel'])){
        $data['cancel'] = 0;
      }
      if(!isset($data['return'])){
        $data['return'] = 0;
      }
      if(!isset($data['count'])){
        $data['count'] = 0;
      }
      foreach ($list as $k => $v) {
        
        //订单总数
        $data['count'] += 1;
        //判断订单分类统计
        // if($v['jisuan_status'] == 0 && $v['jiesuan_status'] == 0 && $v['tijiao_status'] == 0 && $v['huoqu_status'] == 0 && $v['wancheng_status'] == 0){
        //   $data['jisuan_status'] += 1;
        // }elseif ($v['jisuan_status'] == 1 && $v['jiesuan_status'] == 0 && $v['tijiao_status'] == 0 && $v['huoqu_status'] == 0 && $v['wancheng_status'] == 0) {
        //   $data['jiesuan_status'] += 1;
        // }elseif ($v['jisuan_status'] == 1 && $v['jiesuan_status'] == 1 && $v['tijiao_status'] == 0 && $v['huoqu_status'] == 0 && $v['wancheng_status'] == 0) {
        //   $data['tijiao_status'] += 1;
        // }elseif ($v['jisuan_status'] == 1 && $v['jiesuan_status'] == 1 && $v['tijiao_status'] == 1 && $v['huoqu_status'] == 0 && $v['wancheng_status'] == 0) {
        //   $data['huoqu_status'] += 1;
        // }elseif ($v['jisuan_status'] == 1 && $v['jiesuan_status'] == 1 && $v['tijiao_status'] == 1 && $v['huoqu_status'] == 1 && $v['wancheng_status'] == 0) {
        //   $data['weiwancheng_status'] += 1;
        // }elseif ($v['jisuan_status'] == 1 && $v['jiesuan_status'] == 1 && $v['tijiao_status'] == 1 && $v['huoqu_status'] == 1 && $v['wancheng_status'] == 1) {
        //   $data['wancheng_status'] += 1;
        // }
        switch ($v['order_status']) {
          case '0':
            $data['uncomfirmed'] += 1;
            break;
          case '1':
            $data['payed'] += 1;
          break;
          case '2':
            $data['cancel'] += 1;
          break;
          case '3':
            $data['return'] += 1;
          break;
        }
        
      }
      //将user_id转换成真实名称
      // $Model = new \Think\Model();
      // foreach ($data as $k => $v) {
      //   $row = $Model->query("select realname from ecs_users where user_id = {$v['user_id']}");
      //   $data[$k]['user_id'] = $row[0]['realname'];
      //   $row = $Model->query("select app_name from ecs_user_app where user_id = {$v['user_id']}");
      //   $data[$k]['app_name'] = $row[0]['app_name'];
      // }
        $store = D("the_store");
      $user = D("user");
      $order = D("order_info");
      $now = time();
        /*今日订单*/
      $beginTime =strtotime(date('Y-m-d', $now));
      $endTime = $beginTime+24*60*60;
      /*统计店主数量*/
      $storerow = $store->field('count(id) as total_number')->find();
      $arr['store_count'] = $storerow ['total_number'];//店主总数
      $row =  $store->field("count(id) as count")->where("add_time>='$beginTime' and add_time<='$endTime'")->find();//每日新增店主数量
      $arr['store_today'] =$row[count];

      /*统计会员数量*/
      $viprow = $user->field('count(uid) as total_number')->where('user_rank = 2')->find();
      $arr['vip_count'] = $viprow ['total_number'];//会员总数
      $row1 =  $user->field("count(uid) as count")->where("reg_time>='$beginTime' and reg_time<='$endTime' and user_rank =2")->find();//每日新增会员数量
      $arr['vip_today'] =$row1[count];
        
        /*统计普通用户数量*/
      $ptrow = $user->field('count(uid) as total_number')->where('user_rank = 1')->find();
      $arr['pt_count'] = $ptrow ['total_number'];//店主总数
      $row2 =  $user->field("count(uid) as count")->where("reg_time>='$beginTime' and reg_time<='$endTime' and user_rank =1")->find();//每日新增店主数量
      $arr['pt_today'] =$row2[count];
      $orderrow =    $order->field("count(order_id) as count")->where("pay_time>='$beginTime' and pay_time<='$endTime' and pay_status=1")->find();//每日新增订单数量
      $arr['order_today'] =$orderrow[count];
      // dump($_SERVER);
      $this->assign('data',$data);
      $this->assign('arr',$arr);
      $this->assign('time',time());
      $this->display();
    }

    public function top()
    {
     
       $this->display();
    }

}
