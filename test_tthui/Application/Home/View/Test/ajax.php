<?php 
define('IN_ECTOUCH', true); 
// require('D:\phpStudy\PHPTutorial\WWW\ecshop\mobile\include\init.php');
require_once('D:\phpStudy\PHPTutorial\WWW\ecshop\TrustSQL\idm\test2.php');
// $name = $_REQUE['name'];
// $pwd = $_POST['pwd'];
// $pwd2 = $_POST['pwd'].'22222';

$name = "22222";
$pwd = "22222";
$pwd2 = "22222".'22222';
// $result = "12312";
$res = new TrustSql();
// $result = $res->account_query();
// $result = $res->asset_issue_apply();
// $result = $res->asset_transfer_apply("13726215322","13726215355","1");
$result = $res->trans_query("13726215322","1","0");
$array = array("$name","$pwd","$result");
// echo "<pre>";
// print_r($result);
// echo "</pre>";
/* 检查权限 */
$result = '123123';
$smarty->assign('ttc_info',$result);
$smarty->assign('ur_here',      $_LANG['01_shop_config']);
$smarty->display('test_ajax.htm'); 
// echo json_encode($array);//json_encode方式是必须的
?>
