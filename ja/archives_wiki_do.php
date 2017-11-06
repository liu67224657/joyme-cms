<?php
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/datalistcp.class.php');
require_once(DEDEINC.'/common.func.php');

require_once(DEDEINC."/dedetag.class.php");
require_once(DEDEADMIN."/inc/inc_catalog_options.php");
ClearMyAddon();

//检查权限
CheckPurview('a_List,a_AccList,a_MyList');

if(!isset($dopost)) $dopost = '';

if(empty($dopost))
{
	ShowMsg('对不起，你没指定运行参数！','-1');
	exit();
}
if( $dopost == 'getsite' ){
	if(!isset($keyword)) $keyword = '';
	
	$sql = 'select * from joyme_sites where site_name like "%'.$keyword.'%" or site_key like "%'.$keyword.'%" limit 10';
	$dsql->SetQuery($sql);//将SQL查询语句格式化
	$dsql->Execute();//执行SQL操作
	//通过循环输出执行查询中的结果
	$sitelist = array();
	while($row = $dsql->GetArray()){
		$sitelist[] = $row;
	}
	echo json_encode($sitelist);
}elseif( $dopost == 'uptime' ){
	if(!isset($qstr)) $qstr = '';
	$qstrs = explode('`',$qstr);
	$qstrs = implode(',',$qstrs);
	
	$time = time();
	
	$where  = array('1=1');
	
	$where[] = " id in({$qstrs}) ";
	
	$sql = 'select aid from joyme_archives_wiki where '.join(' AND ',$where);
	$dsql->SetQuery($sql);//将SQL查询语句格式化
	$dsql->Execute();//执行SQL操作
	//通过循环输出执行查询中的结果
	$aids = '0';
	while($row = $dsql->GetArray()){
		$aids .= ','.$row['aid'];
	}
	
	if($aids == '0'){
		echo 0;exit;
	}
	
	$sql = "UPDATE `#@__archives` AS a SET a.pubdate=".$time." WHERE a.id in($aids)";
	$dsql->ExecuteNoneQuery($sql);
	$sql = "UPDATE `#@__arctiny` AS a AS b SET a.senddate=".$time." WHERE a.id in($aids)";
	$dsql->ExecuteNoneQuery($sql);
	
	$sql = "update `joyme_archives_wiki` set rec_time=".$time." where ".join(' AND ',$where);
	$dsql->ExecuteNoneQuery($sql);
	echo 1;exit;
}elseif( $dopost == 'render' ){
	
	if(!isset($url)) $url = '';
	$url = urldecode($url);
	if(empty($url)){
		echo -1;
	}else{
		if(strpos($url,'?') != false){
			$url .= '&action=render';
		}else{
			$url .= '?action=render';
		}
		//echo $url;exit;
		$str = file_get_contents($url);
		$str = str_replace('<pre>','<p>',$str);
		$str = str_replace('</pre>','</p>',$str);
		echo $str;
	}
}



?>