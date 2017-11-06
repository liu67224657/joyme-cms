<?php
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/datalistcp.class.php');
require_once(DEDEINC.'/common.func.php');
require_once(DEDEADMIN.'/inc/inc_archives_functions.php');

//检查权限
CheckPurview('a_List,a_AccList,a_MyList');

if(!isset($qstr)) $qstr = '';
if(!isset($typeid)) $typeid = '';
if(!isset($typeid2)) $typeid2 = '';

if($qstr=='')
{
	echo '参数无效！';
	exit();
}
$qstrs = explode('`',$qstr);
$qstrs = implode(',',$qstrs);


$where  = array('1=1');

$where[] = " id in({$qstrs}) ";

$dlist = new DataListCP('', true);
$dlist->pageSize = 20;
$dlist->SetTemplet(DEDEADMIN."/templets/archives_wiki_addcontent.htm");

//查询
$sql = "Select * From `joyme_archives_wiki` where ".join(' AND ',$where)." order by edit_time DESC";

$dlist->SetSource($sql);

$list = $dlist->GetArcList('');
$sidstr = '0';
foreach($list as $val){
	$sidstr .= ','.$val['sid'];
}
//查询站点信息
$sql = 'select * from joyme_sites where site_id in('.$sidstr.')';
$dsql->SetQuery($sql);//将SQL查询语句格式化
$dsql->Execute();//执行SQL操作
//通过循环输出执行查询中的结果
$sitelist = array();
while($row = $dsql->GetArray()){
	$sitelist[$row['site_id']] = $row;
}
//获取body的描述
$deslist = array();
foreach($list as $k=>$val){
	$deslist[$val['id']] = getwikides($sitelist[$val['sid']]['site_key'], $val['title']);
}
$dlist->SetParameter('typeid', $typeid);
$dlist->SetParameter('typeid2', $typeid2);
//显示
$dlist->display();
$dlist->Close();

function getwikides($wikikey,$title){
	global $com;
	$description = $litpic = $keywords = '';
	$url = 'http://wiki.joyme.'.$com.'/'.$wikikey.'/'.$title;
	$body = file_get_contents($url.'?action=render');
	$userurl = 'http://wiki.joyme.'.$com.'/'.$wikikey.'/api.php?action=query&prop=revisions&titles='.$title.'&rvdir=newer&rvlimit=1&format=json';
	$pageinfo = json_decode(file_get_contents($userurl),true);
	$rs = array_values($pageinfo['query']['pages']);
	$user = empty($rs[0]['revisions'][0]['user'])?'WIKI编辑者':$rs[0]['revisions'][0]['user'];
	AnalyseHtmlBody($body,$description,$litpic,$keywords,'htmltext');
	return array('url'=>$url,'des'=>$description,'pic'=>$litpic,'keywords'=>$keywords,'user'=>$user);
}
?>