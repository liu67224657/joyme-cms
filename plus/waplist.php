<?php
/**
 * 列表页点击加载更多
 */
require_once(dirname(__FILE__).'/../include/common.inc.php');
$typeid = isset($_GET['typeid']) ? str_replace('，', ',', addslashes($_GET['typeid'])) : 1;
$typeid2 = isset($_GET['typeid2']) ? intval($_GET['typeid2']) : null;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
//$tag = isset($_GET['tag']) ? addslashes($_GET['tag']) : 'a';
$tpl = isset($_GET['tpl']) ? addslashes($_GET['tpl']) : 'a';
$callback=$_GET['callback'];  

$tplfile = $GLOBALS['cfg_basedir'].$GLOBALS['cfg_templets_dir'].'/wap/'.$tpl.'.htm';
if(!file_exists($tplfile)){
	exit;
}

$offset = 10;
$limit = (($page-1)*$offset).",".$offset;
if($typeid2 == null){
	$sqlwhere = "typeid in ($typeid) AND flag NOT LIKE '%s%'";
}else{
	$sqlwhere = "(typeid in ($typeid) or FIND_IN_SET($typeid2, typeid2)) AND flag NOT LIKE '%s%'";
}
$selquery = "SELECT * FROM `#@__archives` where ".$sqlwhere." ORDER BY pubdate DESC limit ".$limit;
$dsql->SetQuery($selquery);
$dsql->Execute();
ob_start();
include($tplfile);
$listHtml = ob_get_clean();
$listHtml = json_encode(array('listHtml'=>$listHtml));
echo $callback."($listHtml)";