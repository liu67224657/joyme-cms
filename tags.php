<?php
/**
 * @version        $Id: tags.php 1 2010-06-30 11:43:09Z tianya $
 * @package        DedeCMS.Site
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once (dirname(__FILE__) . "/include/common.inc.php");
require_once (DEDEINC . "/arc.taglist.class.php");
$PageNo = 1;
$path = $_GET['path'].'/';
$param = $_GET['param'];
if(isset($_SERVER['QUERY_STRING']) && $param)
{
    $tag = trim($param);
    $tags = explode('_', $tag);
    if(isset($tags[1])) $tag = $tags[1];
    if(isset($tags[2])) $PageNo = intval($tags[2]);
}
else
{
    $tag = '';
}
$typestr = $GLOBALS['cfg_type_list'];
$tujitypeids = $typestr != '' ? explode('|', $typestr) : array();
$tag = FilterSearch(urldecode($tag));
if($tag != addslashes($tag)) $tag = '';
if($tag == '') $dlist = new TagList($tag, 'tag.htm');
else{
	$dlist = new TagList($tag, 'taglist.htm');
	$taginfo = $dsql->GetOne("Select * From `#@__tagindex` where id = '{$tag}' ");
	if(in_array($taginfo['typeid'], $tujitypeids)){
		$iswap = strpos($path, '/pc/') !== false ? 0 : 1;
		$dlist = new TagList($tag, 'tuji_tag_list.20161102.htm', $iswap);
	}else{
		$dlist = new TagList($tag, 'taglist.htm');
	}
}
$dlist->Display();
exit();