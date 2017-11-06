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
require_once(dirname(__FILE__) . '/config.php');
require_once(DEDEINC . '/typelink.class.php');
require_once(DEDEINC . '/datalistcp.class.php');
require_once(DEDEADMIN . '/inc/inc_list_functions.php');
require_once(DEDEADMIN . "/inc/inc_catalog_options.php");
$cid = isset($cid) ? intval($cid) : 0;
$joymearctypes = isset($joymearctypes) ? intval($joymearctypes) : 0;
if ($joymearctypes) {
    $cid = $joymearctypes;
}

//$joymearctypes = $cid;
$channelid = isset($channelid) ? intval($channelid) : 0;
$mid = isset($mid) ? intval($mid) : 0;
$tagid = isset($tagid) ? intval($tagid) : 0;
//if(!isset($keyword)) $keyword = '';
//if(!isset($game)) $game = '';
//if(!isset($author)) $author = '';
//if(!isset($flag)) $flag = '';
if (!isset($arcrank)) $arcrank = '';
if (!isset($dopost)) $dopost = '';
//if(!isset($joymearctypes)) $joymearctypes = '';
if (!isset($pageno)) $pageno = 1;
$pageno2 = $pageno;
if (!isset($keyword)) $keyword = '';
if (!isset($game)) $game = '';
if (!isset($author)) $author = '';
//检查权限许可，总权限
CheckPurview('a_List,a_AccList,a_MyList');

//栏目浏览许可
$userCatalogSql = '';
if (TestPurview('a_List')) {
    ;
} else if (TestPurview('a_AccList')) {
    if ($cid == 0 && $cfg_admin_channel == 'array') {
        $admin_catalog = join(',', $admin_catalogs);
        $userCatalogSql = " arc.typeid IN($admin_catalog) ";
    } else {
        CheckCatalog($cid, '你无权浏览非指定栏目的内容！');
    }
    if (TestPurview('a_MyList')) $mid = $cuserLogin->getUserID();

}
$wheresqlinid = '';
if ($game) {
    $url = $apiUrl . '/collection/api/gamearchive/getarchives?gamename=' . urlencode($game).'&currentpage='.$pageno;
    $articleIds = json_decode(joymeCurlGetFn($url), true);
    if ($articleIds['rs'] == 1 && $articleIds['result']) {
        $ids = implode(',', $articleIds['result']['rows']);
        $totalresult = intval($articleIds['result']['page']['totalRows']);
    }else{
        $totalresult = 0;
    }
    $ids = empty($ids)?0:$ids;
    $wheresqlinid = ' AND arc.id in (' . $ids . ') ';
}

$adminid = $cuserLogin->getUserID();
$maintable = '#@__archives';
if (empty($_GET['popen'])) {
    setcookie('ENV_GOBACK_URL', $dedeNowurl, time() + 3600, '/');
}
$tl = new TypeLink($cid);

if ($cid == 0) {
    if ($channelid == 0) {
        $positionname = '所有栏目&gt;';
    } else {
        $row = $tl->dsql->GetOne("SELECT id,typename,maintable FROM `#@__channeltype` WHERE id='$channelid'");
        $positionname = $row['typename'] . " &gt; ";
        $maintable = $row['maintable'];
        $channelid = $row['id'];
    }
} else {
    $positionname = str_replace($cfg_list_symbol, " &gt; ", $tl->GetPositionName()) . " &gt; ";
}

//当选择的是单表模型栏目时，直接跳转到单表模型管理区
if (empty($channelid)
    && isset($tl->TypeInfos['channeltype'])
) {
    $channelid = $tl->TypeInfos['channeltype'];
}
if ($channelid < -1) {
    header("location:content_sg_list.php?cid=$cid&channelid=$channelid&keyword=$keyword");
    exit();
}


// 栏目大于800则需要缓存数据
@$optHash = md5($cid . $admin_catalogs . $channelid);
$optCache = DEDEDATA . "/tplcache/inc_option_$optHash.inc";

$typeCount = 0;
if (file_exists($cache1)) require_once($cache1);
else $cfg_Cs = array();
$typeCount = count($cfg_Cs);
if ($typeCount > 800) {
    if (file_exists($optCache)) {
        $optionarr = file_get_contents($optCache);
    } else {
        $optionarr = $tl->GetOptionArray($cid, $admin_catalogs, $channelid);
        file_put_contents($optCache, $optionarr);
    }
} else {
    $optionarr = $tl->GetOptionArray($cid, $admin_catalogs, $channelid);
}

//$whereSql = empty($channelid) ? " WHERE arc.channel > 0  AND arc.arcrank > -2 " : " WHERE arc.channel = '$channelid' AND arc.arcrank > -2 ";
$whereSql = ' WHERE arc.channel > 0  AND arc.arcrank > -2 ';

$flagsArr = '';
$dsql->Execute('f', 'SELECT * FROM `#@__arcatt` ORDER BY sortid ASC');
$flag = '';
while ($frow = $dsql->GetArray('f')) {
    $flagsArr .= ($frow['att'] == $flag ? "<option value='{$frow['att']}' selected>{$frow['attname']}</option>\r\n" : "<option value='{$frow['att']}'>{$frow['attname']}</option>\r\n");
}

if (!empty($tagid)) {
    $whereSql .= " AND FIND_IN_SET('{$tagid}', arc.tagid)";
}
if (!empty($userCatalogSql)) {
    $whereSql .= " AND " . $userCatalogSql;
}
if (!empty($mid)) {
    $whereSql .= " AND arc.mid = '$mid' ";
}
if ($keyword != '') {
    $whereSql .= " AND arc.title LIKE '%$keyword%' ";
}
if ($author != '' && is_numeric($author)) {
    $whereSql .= " AND arc.mid = $author ";
} else if ($author != '') {
    $authorsql = 'SELECT id,uname FROM dede_admin WHERE uname LIKE "%' . $author . '%";';
    $dsql->Execute('author', $authorsql);
    $mids = array();
    while ($row = $dsql->GetArray("author", MYSQL_ASSOC)) {
        $mids[] = $row['id'];
    }
    $whereSql .= " AND arc.mid IN (" . implode(',', $mids) . ") ";
}
if ($flag != '') {
    $whereSql .= " AND FIND_IN_SET('$flag', arc.flag) ";
}
if ($cid != 0) {
    $whereSql .= ' AND (arc.typeid IN (' . GetSonIds($cid) . ') OR FIND_IN_SET(' . $cid . ', arc.joymearctypes))';
}
if ($arcrank != '') {
    $whereSql .= " AND arc.arcrank = '$arcrank' ";
    $CheckUserSend = "<input type='button' class='coolbg np' onClick=\"location='catalog_do.php?cid=" . $cid . "&dopost=listArchives&gurl=content_list.php';\" value='所有文档' />";
} else {
    $CheckUserSend = "<input type='button' class='coolbg np' onClick=\"location='catalog_do.php?cid=" . $cid . "&dopost=listArchives&arcrank=-1&gurl=content_list.php';\" value='稿件审核' />";
}

$orderby = empty($orderby) ? 'pubdate' : preg_replace("#[^a-z0-9]#", "", $orderby);
$orderbyField = 'arc.' . $orderby;
if ($wheresqlinid) {
    $whereSql .= $wheresqlinid;
}

//if($cid != 0){
//    $whereSql .= " OR FIND_IN_SET('$cid', arc.joymearctypes) ";
//}

$query = "SELECT arc.id,arc.joymearctypes,arc.typeid,arc.senddate,arc.flag,arc.ismake,
arc.channel,arc.arcrank,arc.click,arc.title,arc.writer,arc.color,arc.litpic,arc.pubdate,arc.mid
FROM `$maintable` arc
$whereSql
ORDER BY $orderbyField DESC";
if (empty($f) || !preg_match("#form#", $f)) $f = 'form1.arcid1';

//初始化
$dlist = new DataListCP();
$dlist->pageSize = 30;
//GET参数
//$dlist->SetParameter('dopost', 'listArchives');
//$dlist->SetParameter('keyword', $keyword);
if (!empty($mid)) $dlist->SetParameter('mid', $mid);
$dlist->SetParameter('cid', $cid);
//$dlist->SetParameter('flag', $flag);
//!empty($tagid) ? $dlist->SetParameter('tagid', $tagid) : '';
//$dlist->SetParameter('orderby', $orderby);
//$dlist->SetParameter('arcrank', $arcrank);
//$dlist->SetParameter('channelid', $channelid);
//$dlist->SetParameter('f', $f);
//if(!isset($joymearctypes)) $joymearctypes = '';
if (!isset($keyword)) $keyword = '';
if (!isset($game)) $game = '';
if (!isset($author)) $author = '';

//$dlist->SetParameter('joymearctypes', $joymearctypes);
$dlist->SetParameter('keyword', $keyword);
$dlist->SetParameter('game', $game);
$dlist->SetParameter('author', $author);
$dlist->SetParameter('tagid', $tagid);

//$newpage = $dlist->GetPageList();
// 栏目
$joymearctypesdata = GetTypeList(0, $cuserLogin->getUserChannel(), $channelid);
$joymearctypes = json_encode($joymearctypesdata);
$typeidSelectHtml = typeidSelectHtml($joymearctypesdata, $cid);

//模板
if (empty($s_tmplets)) $s_tmplets = 'templets/content_list.htm';
$dlist->SetTemplate(DEDEADMIN . '/' . $s_tmplets);

//查询
if ($game) {
    $pageno = 1;
}
$dlist->SetSource($query);
$dlist->PreLoad();
$list = $dlist->GetArcList('');
$pageids = array();
foreach ($list as $val) {
    $pageids[] = $val['id'];
}


$atts = array();
$atts['tagname'] = 'pagelist';
$atts['listsize'] = '3';
$dlist->pageNO = $pageno2;

$newpage = $dlist->GetPageList($atts,$dlist->refObj,$fields);

$url = $webcacheurl . '/json/pagestat/pvlist.do?pageids=' . implode(',', $pageids) . '&pagetype=1';
$data = json_decode(gzdecode(joymeCurlGetFn($url)), true);
$pvdata = $data['result'];
$url = $apiUrl . '/collection/api/gamearchive/getgames?archiveid=' . implode(',', $pageids);
$data = json_decode(joymeCurlGetFn($url), true);
$arcgamedata = array();
if ($data['rs'] == 1 && is_array($data['result'])) {
    foreach ($data['result'] as $val) {
        $arcgamedata[$val['archiveId']] .= '[<a href="' . $domain . '/ja/content_list.php?game=' . $val['gameName'] . '">' . $val['gameName'] . '</a>]';
        /*if(!isset($arcgamedata[$val['archiveId']])) continue;
        $arcgamedata[$val['archiveId']] .= '[<a href="'.$domain.'/ja/content_list.php?game='.$val['gameName'].'">'.$val['gameName'].'</a>]';*/
    }
}
//显示
$doPreLoad = false;
$dlist->Display($doPreLoad);
$dlist->Close();