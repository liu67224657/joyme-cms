<?php
/**
 *
 * 搜索页
 *
 * @version        $Id: search.php 1 15:38 2010年7月8日Z tianya $
 * @package        DedeCMS.Site
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/../include/common.inc.php");
$maintable = '#@__archives';
$typeid=771;
$cachefile = $cfg_basedir.'/wechat/searchstrategy.htm';
if(!isset($keyword)){
    if(!isset($q)) $q = '';
    $keyword=$q;
}

$oldkeyword = $keyword = FilterSearch(stripslashes($keyword));
$keyword = addslashes(cn_substr($keyword,30));
// $typeid = (isset($typeid) && is_numeric($typeid)) ? $typeid : 0;
// $typeid = intval($typeid);
$filechangetime = filemtime($cachefile);
//3600 为60分钟秒数， 缓存文件每60分钟更新一次
if(file_exists($cachefile) && ($filechangetime+3600)>time()){
	include($cachefile);exit;
}

ob_start();
$sql_hotwords = "SELECT title FROM dede_archives WHERE typeid=772 limit 8";
$dsql->Execute('me', $sql_hotwords);
$words = array();
while($words[] = $dsql->GetArray('me')){}
array_pop($words);
//攻略
$sql_strategy = "SELECT a.title, b.redirecturl, b.gameicon, b.gamecat FROM dede_archives AS a "
	."LEFT JOIN dede_addonwechatstrategy AS b ON a.id=b.aid WHERE a.`typeid` = 771 AND b.`gameicon` != '' GROUP BY a.title";
$dsql->Execute('me', $sql_strategy);
$data = array();
while($data[] = $dsql->GetArray('me')){}
array_pop($data);
$tempfile = $GLOBALS['cfg_basedir'].$GLOBALS['cfg_templets_dir']."/wechatstrategy/search.htm.php";
include($tempfile);
$res = ob_get_contents();
ob_end_clean();
file_put_contents($cachefile, $res);
echo $res;
exit;