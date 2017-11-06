<?php
/**
 * 内容列表
 * content_s_list.php、content_i_list.php、content_select_list.php
 * 均使用本文件作为实际处理代码，只是使用的模板不同，如有相关变动，只需改本文件及相关模板即可
 *
 * @version        $Id: content_list.php 1 14:31 2010年7月12日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/typelink.class.php');
require_once(DEDEINC.'/datalistcp.class.php');
require_once(DEDEADMIN.'/inc/inc_list_functions.php');
require_once(DEDEADMIN."/inc/inc_catalog_options.php");
$cid = isset($cid) ? $cid : '';
$action = isset($action) ? $action : '';
if($action == 'upstatus'){
    $aid = (empty($aid) ? 0 : intval($aid) );
    $mtype = (empty($mtype) ? 0 : intval($mtype) );
    $query = "UPDATE `#@__archives` SET `mtype`='$mtype' WHERE id='$aid' ";
    $dsql->ExecuteNoneQuery($query);
    $cachefile = DEDEROOT.'/data/cache/LiveStatus.php';
    if(file_exists($cachefile)){
        @unlink($cachefile);
    }
    ShowMsg("操作成功!", 'content_zhibo_list.php?channelid='.$channelid.'&cid='.$cid);
    exit;
}

if($action == 'recoveryarc'){
    $aid = (empty($aid) ? 0 : intval($aid) );
    $dsql->ExecuteNoneQuery("UPDATE `#@__archives` SET arcrank='0',ismake='0' ,`mtype`=2 WHERE id='$aid'");
    $dsql->ExecuteNoneQuery("UPDATE `#@__arctiny` SET `arcrank` = '0' WHERE id = '$aid'; ");
    // $dsql->ExecuteNoneQuery("UPDATE `#@__addonzhibo` SET `mtype`=2 WHERE aid='$aid' ");
    ShowMsg("操作成功!", 'content_zhibo_list.php?channelid='.$channelid.'&cid='.$cid);
    exit;
}

$cid = isset($cid) ? intval($cid) : 0;
$channelid = isset($channelid) ? intval($channelid) : 0;
$mid = isset($mid) ? intval($mid) : 0;
// $tagid = isset($tagid) ? intval($tagid) : 0;
if(!isset($arcrank)) $arcrank = '';
if(!isset($dopost)) $dopost = '';
if(!isset($pageno)) $pageno = 1;
// if(!isset($keyword)) $keyword = '';
// if(!isset($game)) $game = '';
// if(!isset($author)) $author = '';
// if(!isset($mtype)) $mtype = '';
//检查权限许可，总权限
CheckPurview('a_List,a_AccList,a_MyList');

//栏目浏览许可
$userCatalogSql = '';
if(TestPurview('a_List'))
{
    ;
}
else if(TestPurview('a_AccList'))
{
    if($cid==0 && $cfg_admin_channel == 'array')
    {
        $admin_catalog = join(',', $admin_catalogs);
        $userCatalogSql = " arc.typeid IN($admin_catalog) ";
    }
    else
    {
        CheckCatalog($cid, '你无权浏览非指定栏目的内容！');
    }
    if(TestPurview('a_MyList')) $mid =  $cuserLogin->getUserID();

}

$adminid = $cuserLogin->getUserID();
$maintable = '#@__archives';
if(empty($_GET['popen'])){
	setcookie('ENV_GOBACK_URL', $dedeNowurl, time()+3600, '/');
}
$tl = new TypeLink($cid);

//----------------------------------------
//在不指定排序条件和关键字的情况下直接统计微表
//----------------------------------------

if($cid==0)
{
    if($channelid==0)
    {
        $positionname = '所有栏目&gt;';
    }
    else
    {
        $row = $tl->dsql->GetOne("SELECT id,typename,maintable FROM `#@__channeltype` WHERE id='$channelid'");
        $positionname = $row['typename']." &gt; ";
        $maintable = $row['maintable'];
        $channelid = $row['id'];
    }
}
else
{
    $positionname = str_replace($cfg_list_symbol," &gt; ",$tl->GetPositionName())." &gt; ";
}

//当选择的是单表模型栏目时，直接跳转到单表模型管理区
if(empty($channelid) 
  && isset($tl->TypeInfos['channeltype']))
{
    $channelid = $tl->TypeInfos['channeltype'];
}
if($channelid < -1 )
{
    header("location:content_sg_list.php?cid=$cid&channelid=$channelid&keyword=$keyword");
    exit();
}


// 栏目大于800则需要缓存数据
@$optHash = md5($cid.$admin_catalogs.$channelid);
$optCache = DEDEDATA."/tplcache/inc_option_$optHash.inc";

$typeCount = 0;
if (file_exists($cache1)) require_once($cache1);
else $cfg_Cs = array();
$typeCount = count($cfg_Cs);
if ( $typeCount > 800)
{
    if (file_exists($optCache))
    {
        $optionarr = file_get_contents($optCache);
    } else { 
        $optionarr = $tl->GetOptionArray($cid, $admin_catalogs, $channelid);
        file_put_contents($optCache, $optionarr);
    }
} else { 
    $optionarr = $tl->GetOptionArray($cid, $admin_catalogs, $channelid);
}

$whereSql = empty($channelid) ? " WHERE arc.channel > 0 " : " WHERE arc.channel = '$channelid' ";

if($mtype != '')
{
    if($mtype == 3){
        $whereSql .= "AND arc.arcrank = -2 ";
    }else{
        $whereSql .= "AND arc.arcrank > -2 AND mtype = '$mtype' ";
    }
}
if($keyword != '')
{
    $whereSql .= " AND arc.title LIKE '%$keyword%' ";
}
if($cid != 0)
{
    $whereSql .= ' AND arc.typeid IN ('.GetSonIds($cid).') ';
}

$orderby = empty($orderby) ? 'pubdate' : preg_replace("#[^a-z0-9]#", "", $orderby);
$orderbyField = 'arc.'.$orderby;

// $whereSql = 'LEFT JOIN dede_addonzhibo zb on arc.id = zb.aid '.$whereSql;

$query = "SELECT arc.id,arc.shorttitle,arc.typeid,arc.ismake,
arc.channel,arc.arcrank,arc.title,arc.litpic,arc.pubdate,arc.mtype
FROM `$maintable` arc 
$whereSql
ORDER BY $orderbyField DESC";

if(empty($f) || !preg_match("#form#", $f)) $f = 'form1.arcid1';
//初始化
$dlist = new DataListCP();
$dlist->pageSize = 20;

//GET参数
if(!isset($keyword)) $keyword = '';
if(!isset($mtype)) $mtype = '';
$dlist->SetParameter('dopost', 'listArchives');
$dlist->SetParameter('cid', $cid);
$dlist->SetParameter('channelid', $channelid);
$dlist->SetParameter('keyword', $keyword);
$dlist->SetParameter('mtype', $mtype);
//模板
if(empty($s_tmplets)) $s_tmplets = 'templets/content_zhibo_list.htm';
$is_mobile = is_mobile();
if($is_mobile){
    $s_tmplets = 'templets/content_wapzhibo_list.htm';
}
$dlist->SetTemplate(DEDEADMIN.'/'.$s_tmplets);

//查询
$dlist->SetSource($query);
//显示
$dlist->PreLoad();
$list = $dlist->GetArcList('');
$pageids = array();
foreach($list as $val){
    $pageids[] = $val['id'];
}

$url = $webcacheurl.'/json/pagestat/pvlist.do?pageids='.implode(',', $pageids).'&pagetype=1';
$data = json_decode(gzdecode(joymeCurlGetFn($url)), true);
$pvdata = $data['result'];
//显示
$doPreLoad = false;
$dlist->Display($doPreLoad);
$dlist->Close();