<?php
//$is_check = false;
require_once (dirname(__FILE__) . "/../include/common.inc.php");
//require_once("../ja/config.php");
define('DEDEADMIN', DEDEROOT.'/ja'); //dede修改为你后台的文件夹名称
require_once(DEDEINC.'/userlogin.class.php');
require_once(DEDEADMIN.'/../include/common.inc.php');
require_once(DEDEINC.'/typelink.class.php');
require_once(DEDEINC.'/datalistcp.class.php');
require_once(DEDEADMIN.'/inc/inc_list_functions.php');
require_once(DEDEADMIN.'/inc/inc_batchup.php');
require_once(DEDEADMIN.'/inc/inc_archives_functions.php');
require_once(DEDEINC.'/typelink.class.php');
require_once(DEDEINC.'/arc.archives.class.php');
require_once(DEDEADMIN."/inc/inc_archives_functions.php");
require_once(DEDEINC."/arc.partview.class.php");
require_once(DEDEDATA."/cache/inc_catalog_base.inc");
require_once(DEDEINC."/channelunit.func.php");
require_once(DEDEINC."/arc.listview.class.php");

// 清除数据缓存
$dsql->ExecuteNoneQuery("Delete From `#@__arccache` ");
// 获取参数更新栏目
$typeid = !empty($_GET['typeid']) ? intval($_GET['typeid']) : 0;
$time = !empty($_GET['time']) ? intval($_GET['time']) : 0;
$joymes = !empty($_GET['joymes']) ? $_GET['joymes'] : '';
$checkStr = md5($typeid.$time.$ApiSecretKey);
if($typeid == 0){
    die('栏目ID不能为空');
}else if($checkStr !== $joymes){
     die('Access Dine!');
}
//SELECT * FROM `dede_arctiny` arc WHERE typeid = 368 AND arc.senddate < 1438323761 ORDER BY id DESC LIMIT 20
$typeids = GetSonIds($typeid); //366,367,368
$idArray = explode(',',$typeids);
$isremote = 0;
foreach($idArray as $typeid){
    $lv = new ListView($typeid);
    $channeltemp = $lv->Fields['channeltemp'] != '' ? explode(',', $lv->Fields['channeltemp']) : array();
    array_unshift($channeltemp, 'pc');
    foreach($channeltemp as $val){
        $lv->CountRecord($val);
        $reurl = $lv->MkHtml($val);
        mkArt($typeid);
    }
}
echo '更新完成';


/**
 * 更新文档
 */
function mkArt($typeid){
    global $dsql;
    $time = time();
    $query = "SELECT id FROM `dede_arctiny` arc WHERE typeid = $typeid AND arc.senddate < $time ORDER BY id DESC LIMIT 30";
    $dsql->Execute('me',$query);
    while($row2 = $dsql->GetArray())
    {
      $aid = $row2['id']; 
      $pageurl = MakeArt($aid,false);
      $now = time();
      $dsql->ExecuteNoneQuery("Update `dede_arctiny` set arcrank='0',pubdate='$now' where id='$aid' ");
    }
}

