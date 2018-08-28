<?php
namespace Home\Controller;
// header('content-type:text/html;charset=utf-8');
header('content-type:application/x-www-form-urlencoded');
// header("Access-Control-Allow-Origin:*");
use Think\Controller;
class SharegoodsController extends Controller {
    private $goods = NULL;
    private $image = NULL;
    private $wx = NULL;
    private $wxConfig = NULL;
    private $the_store = NULL;
    private $user = NULL;
    private $appid = NULL;
    private $dir = 'Public/Home/share_img';//分享图的存放位置
    public function _initialize() {     
        !is_null($this -> goods) ? : $this -> goods = M('goods');
        !is_null($this -> wxConfig) ? : $this -> wxConfig = M('wxConfig');
        !is_null($this -> the_store) ? : $this -> the_store = M('the_store');
        !is_null($this -> user) ? : $this -> user = M('user');
        //获取支付参数配置，实例化支付类
        $this->getWxconfig();
    }
    //获取分享小程序二维码
    public function getSmallAppQrcode(){
        //获取目录存在时间。定期删除目录
        $ctime = filectime($this->dir);
        $mtime = filemtime($this->dir);
        //如果目录存在一个月，则删除重建
        if($mtime - $ctime > 86400){
            $this->d_rmdir($this->dir);
        }
        // echo json_encode($_REQUEST['path']);
        // exit;
        //根据goods_id获取分享商品图片
        $goods_id = I('param.goods_id');
        $shop_id = I('param.shop_id');
        $uid = I('param.uid');
        if(empty($shop_id)){
            echo json_encode(array('code'=>0,'msg'=>'没有店铺ID'));
            exit;
        }else{
            $the_store_id = $this->the_store->where(array('id'=>$shop_id))->getField('id');
            if(empty($the_store_id)){
                echo json_encode(array('code'=>0,'msg'=>'没有店铺ID'));
                exit;
            }
        }
        if(empty($uid)){
            echo json_encode(array('code'=>0,'msg'=>'没有用户ID'));
            exit;
        }else{
           $user_id = $this->user->where(array('uid'=>$uid))->getField('uid');
            if(empty($user_id)){
                echo json_encode(array('code'=>0,'msg'=>'没有用户ID'));
                exit;
            } 
        }
        //分享小程序页面
        $path = $_REQUEST['path'];
        $share_img = $this->goods->where(array('goods_id'=>$goods_id))->getField('share_img');
        if(empty($share_img)){
            unlink($filepath);
            echo json_encode(array('code'=>0,'msg'=>'没有分享模板图'));
            exit;
        }
        //将商品分享模板图下载到本地
        $share_img_path = md5($share_img.$path).'.jpg';
        if(file_exists($this->dir.'/'.$share_img_path)){
            
            echo json_encode(array('code'=>1,'msg'=>'分享图获取成功','url'=>$_SERVER['REQUEST_SCHEME'].'://'.dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/'.$this->dir.'/'.$share_img_path));
            exit;
        }
        // echo json_encode(I('param.'));
        // exit;
        $qiniu = false;
        
        if(empty($path)){
            echo json_encode(array('code'=>0,'msg'=>'分享页面不存在'));
            exit;
        }
        //判断七牛云是否存在二维码图,拼接七牛云链接
        $filepath = md5($this->appid.$path).'.jpg';
        $qiniu_img = 'http://pc5nqmb3d.bkt.clouddn.com/'.$filepath;
        $codecontent = file_get_contents($qiniu_img);
        if($codecontent){
            file_put_contents($filepath, $codecontent);
        }else{
            //获取小程序分享二维码图片
            $filepath = $this->wx->getSmallAppQrcode($path);
            $qiniu = true;
        }
        if(!file_exists($filepath)){
            echo json_encode(array('code'=>0,'msg'=>'二维码获取失败'));
            exit;
        }
        

        $result = file_get_contents($share_img);
        file_put_contents($share_img_path, $result);

        if(!file_exists($share_img_path)){
            unlink($filepath);
            echo json_encode(array('code'=>0,'msg'=>'没有分享模板图！'));
            exit;
        }
        //调用图片处理类，处理两张图片
        $new_share_img = $this->dealImage($filepath, $share_img_path, $qiniu);
        if(!file_exists($new_share_img)){
            unlink($filepath);
            echo json_encode(array('code'=>0,'msg'=>'商品无法分享'));
            exit;
        }
        echo json_encode(array('code'=>1,'msg'=>'分享图获取成功','url'=>$_SERVER['REQUEST_SCHEME'].'://'.dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/'.$new_share_img));
        exit;
        //将图片输出到浏览器
        // $image = imagecreatefromjpeg($new_share_img);
        // header("Content-type: image/jpeg");
        // imagejpeg($image); 
        // imagedestroy($image);
        // unlink($new_share_img);
    }
    //获取分享小程序二维码
    public function getSmallAppQrcodes(){
        //获取目录存在时间。定期删除目录
        $ctime = filectime($this->dir);
        $mtime = filemtime($this->dir);
        //如果目录存在一个月，则删除重建
        if($mtime - $ctime > 86400){
            $this->d_rmdir($this->dir);
        }
        // echo json_encode($_REQUEST['path']);
        // exit;
        //根据goods_id获取分享商品图片
        $goods_id = I('param.goods_id');
        //分享小程序页面
        $path = $_REQUEST['path'];
        $share_img = $this->goods->where(array('goods_id'=>$goods_id))->getField('share_img');
        if(empty($share_img)){
            unlink($filepath);
            echo json_encode(array('code'=>0,'msg'=>'没有分享模板图'));
            exit;
        }
        //将商品分享模板图下载到本地
        $share_img_path = md5($share_img.$path).'.jpg';
        if(file_exists($this->dir.'/'.$share_img_path)){
            
            echo json_encode(array('code'=>1,'msg'=>'分享图获取成功','url'=>$_SERVER['REQUEST_SCHEME'].'://'.dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/'.$this->dir.'/'.$share_img_path));
            exit;
        }
        // echo json_encode(I('param.'));
        // exit;
        $qiniu = false;
        
        if(empty($path)){
            echo json_encode(array('code'=>0,'msg'=>'分享页面不存在'));
            exit;
        }
        //判断七牛云是否存在二维码图,拼接七牛云链接
        $filepath = md5($this->appid.$path).'.jpg';
        $qiniu_img = 'http://pc5nqmb3d.bkt.clouddn.com/'.$filepath;
        $codecontent = file_get_contents($qiniu_img);
        if($codecontent){
            file_put_contents($filepath, $codecontent);
        }else{
            //获取小程序分享二维码图片
            $filepath = $this->wx->getSmallAppQrcode($path);
            $qiniu = true;
        }
        if(!file_exists($filepath)){
            echo json_encode(array('code'=>0,'msg'=>'二维码获取失败'));
            exit;
        }
        

        $result = file_get_contents($share_img);
        file_put_contents($share_img_path, $result);

        if(!file_exists($share_img_path)){
            unlink($filepath);
            echo json_encode(array('code'=>0,'msg'=>'没有分享模板图！'));
            exit;
        }
        //调用图片处理类，处理两张图片
        $new_share_img = $this->dealImage($filepath, $share_img_path, $qiniu);
        if(!file_exists($new_share_img)){
            unlink($filepath);
            echo json_encode(array('code'=>0,'msg'=>'商品无法分享'));
            exit;
        }
        echo json_encode(array('code'=>1,'msg'=>'分享图获取成功','url'=>$_SERVER['REQUEST_SCHEME'].'://'.dirname($_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']).'/'.$new_share_img));
        exit;
        //将图片输出到浏览器
        // $image = imagecreatefromjpeg($new_share_img);
        // header("Content-type: image/jpeg");
        // imagejpeg($image); 
        // imagedestroy($image);
        // unlink($new_share_img);
    }
     /**
     * 通过image类处理两张图片，生成分享图
     * @param string $minimage  //小图
     * @param string $maximage  //大图
     * @return string //返回图片链接
     */
    private function dealImage($minimage, $maximage, $qiniu = false){
        !is_null($this -> image) ? : $this -> image = new \Think\Image();
        $image = $this -> image;
        //设置缩略图路径
        // $thumb_path = 'thumb'.md5($minimage).'.jpg';
        //打开小图片,进行等比例缩放
        $image->open($minimage)->thumb(200,200,\Think\Image::IMAGE_THUMB_CENTER)->save($minimage);
        if($qiniu){
            //将处理后的小程序缩略图上传到七牛云
            $qiniu_path = getcwd().'/'.$minimage;
            //将二维码上传到七牛云
            portal_qiniu($qiniu_path, 'http://pc5nqmb3d.bkt.clouddn.com', 'share-image', '.jpg',$minimage);
        }
        
        //设置添加水印图片后的保存位置
        $dir = $this->dir;
        if(!file_exists($dir)){
            mkdir($dir);
        }
        $share_img_path = $dir.'/'.$maximage;
        //给商品图片添加水印图片
        $image->open($maximage)->water($minimage,\Think\Image::IMAGE_WATER_SOUTHEAST)->save($share_img_path);
        //删除独立图片，只保存水印图片
        unlink($minimage);
        unlink($maximage);
        // unlink($thumb_path);
        return $share_img_path;
    }
    //获取授权信息
    private function getWxconfig($type=0){
        $list = $this->wxConfig->where(array('type'=>$type))->find();
        if(empty($list)){
            $data = array('code'=>0,'msg'=>'没有相关的微信支付配置！');
            echo json_encode($data);
            exit;
        }
        !is_null($this -> appid) ?: $this -> appid = $list['appid'];
        // dump($list);
        !is_null($this -> wx) ?: $this -> wx = new \Home\Controller\WechatAppPay($list['appid'], $list['appsecret'], $list['wxmchid'],$list['wxkey']);
    }
    private function d_rmdir($dirname) {   //删除非空目录 
        if(!is_dir($dirname)) { 
            return false; 
        } 
        $handle = opendir($dirname); 
        while(($file = readdir($handle)) !== false){ 
            if($file != '.' && $file != '..'){ 
                $dir = $dirname . '/' . $file; 
                is_dir($dir) ? d_rmdir($dir) : unlink($dir); 
            } 
        } 
        closedir($handle); 
        return rmdir($dirname) ; 
    } 
    public function share_money() {   //购买成功分享赚多少钱
         $goods_id = I('param.goods_id');
         if($goods_id){
            $goods=D('goods');
             $where['goods_id']=$goods_id;
             $row = $goods->field('market_price,first_comm')->where($where)->find();
             $money = $row['market_price']*($row['first_comm']/100);
               $money = sprintf("%.2f", $money);
             $data['money']= $money;
             $data['code']= 1;
             $data['msg']="返回数据成功";
         }else{
            $data['code']= 0;
             $data['msg']="返回数据失败";
         }
         echo json_encode($data);

    } 
}
