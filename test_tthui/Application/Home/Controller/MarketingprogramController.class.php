<?php
namespace Home\Controller;
header('content-type:text/html;charset=utf-8');
// header("Access-Control-Allow-Origin:*");
use Think\Controller;
/*引流小程序接口  用户授权管理接口 2018-7-10 by tao*/
class MarketingprogramController extends Controller {
  /**
   * 更新指定用户信息，返回对应数据
   *
   * @access  public
   * @param   $shop_id 请求参数店铺id
   * @param   $wxuser  微信用户本地信息
   * @param   $Common 自定义类文件对象
   * @param   $uid 通过分享链接存在分享用户id
   * @param   $history 用户最近浏览店铺表对象
   * @param   $user 本地用户信息表对象
   * @return  void
   */
  public function secondIntoApp($shop_id, $wxuser, $Common, $uid, $history, $user){
    //存在店铺id，判断当前进入的店铺ID和最近浏览的店铺ID是否一样
    //设置店铺关系更新条件
    $shop_user_where = array();
    if($shop_id){
      $shop_id == $wxuser['shop_id'] ?: $shop_user_where['shop_id'] = $shop_id;
    }else{
      //获取历史店铺id
      $shop_id = $wxuser['shop_id'];
    }
    //设置用户上下级关系
    if($uid){
      if($uid != $wxuser['sj_uid'] && $uid != $wxuser['uid']){
        $user_rank=$Common->user_rank($uid);//获取分享用户的等级
        if($user_rank > 1){//如果用户为普通用户，则绑定该用户上级
          $shop_user_where['sj_uid'] = $uid;
        }else{
          $sjuid = $Common->sjuid($uid);
          $shop_user_where['sj_uid'] = $sjuid;
        }
      }
    }
    //如果存在更新条件，则进行更新操作
    if($shop_user_where){
      $user->startTrans();
      $shop_user_where['uid'] = $wxuser['uid'];
      $result = $user->save($shop_user_where);
      if(isset($shop_user_where['shop_id'])){
        //插入店铺历史浏览表
        $array = array();
        $array['uid']=$wxuser['uid'];
        $array['shop_id']= $shop_id;
        $array['time']=time();
        $res = $history->add($array);//插入进入小程序拓客宝历史记录表
      }else{
        $res = true;
      } 
    }else{
      $result = true;
      $res = true;
    }
    //判断结果返回数据
    if($result && $res){
      $user->commit();
      $data = array(
                'code'=>1,
                'uid'=> $wxuser['uid'],
                // 'sj_uid'=> $uid,
                'user_rank'=> $wxuser['user_rank'],
                'nickname'=> $wxuser['nickname'],
                'shop_id'=>$shop_id,
                'msg'=>'获取信息成功',
              );
    }else{
      $user->rollback();
      $data = array( 
                'code'=>0,
                'shop_id'=>"",
                'msg'=>'更新用户信息失败，直接跳广告页',
              );
    }
    echo json_encode($data);
    exit;
  }
  /**
   * 第一次进入小程序
   *
   * @access  public
   * @param   $shop_id 请求参数店铺id
   * @param   $wxuser  微信用户本地信息
   * @param   $userInfo 微信用户远程信息
   * @param   $Common 自定义类文件对象
   * @param   $uid 通过分享链接存在分享用户id
   * @param   $history 用户最近浏览店铺表对象
   * @param   $user 本地用户信息表对象
   * @param   $openid 微信用户唯一标识ID
   * @param   $session_key 微信用户会话密钥
   * @param   $function 营销方案组合信息
   * @return  void
   */
  public function firstIntoApp($shop_id, $wxuser, $userInfo, $Common, $uid, $history, $user, $openid, $session_key,$function){
    if($uid){//判断是否是通过别人分享的产品进入小程序
        $user_rank=$Common->user_rank($uid);//获取分享用户的等级
        if($user_rank>1){
            $arr['sj_uid']=$uid;//绑定上下级关系
        }else{
           $sjuid=$Common->sjuid($uid);//获取分享用户的上级UID
           if($sjuid != 0){
              $arr['sj_uid']= $sjuid;//绑定上下级关系
           }
           
        }
      }
      $arr['openid']= $openid;//微信用户的openid
      $arr['nickname']= $userInfo->nickName;//微信用户昵称
      $arr['shop_id']= $shop_id;//拓客宝id
      $arr['headimgurl']= $userInfo->avatarUrl;//微信用户头像
      $arr['sex']= $userInfo->gender;//用户性别
      $arr['city']= $userInfo->city;//城市
      $arr['province']= $userInfo->province;//省份
      $arr['country']= $userInfo->country;//国家
      $arr['language']= $userInfo->language;//语言
      $arr['reg_time']= time(); 
      $arr['session_key']=$session_key;
      $arr = array_merge($arr, $function);
      $user->startTrans();
      $res = $user->data($arr)->add();//插入用户表 
      if($res){
        $array['uid']=$res;
        $array['shop_id']= $shop_id;
        $array['time']=time();
        $result = $history->data($array)->add();//插入历史浏览表
      }
      if($res && $result){
        $user->commit();
        //$_SESSION['wid']=$id;
        $data = array(
            'code'=>1,
            'uid'=>$res,
            'user_rank'=>1,
            'shop_id'=>$shop_id,
            'msg'=>'授权成功',
            );
        echo json_encode($data);
        exit;  
      }else{
        $user->rollback();
        $data = array( 
          'code'=>0,
          'shop_id'=>"",
          'msg'=>'插入数据库失败，授权失败，直接跳广告页',
        );   
        echo json_encode($data);
        exit();
      }
  }
}
