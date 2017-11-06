<?php
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/datalistcp.class.php');
require_once(DEDEINC.'/common.func.php');

require_once(DEDEINC."/dedetag.class.php");
require_once(DEDEADMIN."/inc/inc_catalog_options.php");
ClearMyAddon();
setcookie("ENV_GOBACK_URL2", $dedeNowurl, time()+3600, "/");
//检查权限
CheckPurview('a_List,a_AccList,a_MyList');

if(!isset($title)) $title = '';
if(!isset($wikikey)) $wikikey = '';
if(!isset($aid)) $aid = '';
if(!isset($sid)) $sid = 0;
if(!isset($starttime)) $starttime = date('Y-m-d',time()-24*3600);
if(!isset($starttime2)) $starttime2 = date('H',time()-24*3600);
if(!isset($endtime)) $endtime = date('Y-m-d',time());
if(!isset($endtime2)) $endtime2 = date('H',time())+1;
if(!isset($type)) $type = 1;
if(!isset($sort)) $sort = 0; //排序 0为推荐时间 1为编辑时间

$where  = array('1=1');


if($type == 1){
	$sort = 'edit_time';
}elseif($sort == 'rec_time'){
	$sort = 'rec_time';
}else{
	$sort = 'edit_time';
}

if(!empty($aid)){
	$where[] = " aid=$aid ";
}else{
	if(!empty($sid))
	{
		$where[] = " sid=$sid ";
	}
	
	if(!empty($title))
	{
		$where[] = " title like '%{$title}%' ";
	}
	
	if(!empty($starttime))
	{
		$where[] = " $sort>=".strtotime($starttime.' '.$starttime2.':00:00')." ";
	}
	
	if(!empty($endtime))
	{
		$where[] = " $sort<=".strtotime($endtime.' '.$endtime2.':00:00')." ";
	}
	
	if($type == 1){
		$where[] = " rec_time=0 ";
	}else{
		$where[] = " rec_time>0 ";
	}
}



$dlist = new DataListCP('', true);
$dlist->pageSize = 20;
$dlist->SetTemplet(DEDEADMIN."/templets/archives_wiki_list.htm");

//查询
$sql = "Select * From `joyme_archives_wiki` where ".join(' AND ',$where)." order by ".$sort." DESC";
//echo $sql;exit;
$dlist->SetSource($sql);

$dlist->SetParameter('type', $type);
$dlist->SetParameter('wikikey', $wikikey);
$dlist->SetParameter('title', $title);
$dlist->SetParameter('sid', $sid);
$dlist->SetParameter('starttime', $starttime);
$dlist->SetParameter('starttime2', $starttime2);
$dlist->SetParameter('endtime', $endtime);
$dlist->SetParameter('endtime2', $endtime2);
$dlist->SetParameter('sort', $sort);

$list = $dlist->GetArcList('');
//var_dump('<pre>',$list);exit;
$sidstr = '0';
foreach($list as $val){
	$sidstr .= ','.$val['sid'];
}
$sql = 'select * from joyme_sites';
$dsql->SetQuery($sql);//将SQL查询语句格式化
$dsql->Execute();//执行SQL操作
//通过循环输出执行查询中的结果
$sitelist = array();
while($row = $dsql->GetArray()){
	$sitelist[$row['site_id']] = $row;
}

//显示
$dlist->display();
$dlist->Close();
?>