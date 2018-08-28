<?php
header('content-type:text/html;charset=utf-8');
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// echo '<pre>';
// print_r(md5(md5('hqmt360hqmt360')));
// echo '</pre>';
// exit;
//设置时间戳
date_default_timezone_set('PRC');

//调用七里牛文件
require 'token/qiniu.php';
// 应用入口文件

// 检测PHP环境
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    die('require PHP > 5.3.0 !');
}

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG', true);
// 定义应用目录
define('APP_PATH', './Application/');
$Absolute_Path=substr($_SERVER['SCRIPT_FILENAME'],0,-9).'Public';
define('PUBLIC_PATH', $Absolute_Path);
define('ROOT_PATH', substr($_SERVER['SCRIPT_FILENAME'],0,-9));
// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';
// 亲^_^ 后面不需要任何代码了 就是如此简单
