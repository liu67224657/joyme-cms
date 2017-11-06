<?php
/**
 * 文章阅读表情管理
 *
 * @version        $Id: joyme_readimg.php 1 10:49 2010年7月20日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC.'/datalistcp.class.php');
// 判断是否已经有其他心情启用
if($dopost=='checkuse'){
	$row = $dsql->GetOne("SELECT * FROM `#@__joyme_readimg` WHERE status=1");
	if((isset($id) && $row['id'] == $id) || (!isset($id) && !$row['id'])){
		echo 'yes';
	}else{
		echo 'no';
	}
	exit;
}

// 心情启用
if($dopost=='beuse'){
	$query = "UPDATE `#@__joyme_readimg` SET status=0 WHERE status = 1";
	$dsql->ExecuteNoneQuery($query);
	$query = "UPDATE `#@__joyme_readimg` SET status=1 WHERE id='$id'";
	$dsql->ExecuteNoneQuery($query);
	echo 'yes';
	exit;
}

if(!isset($status)) $status = -1;
if(!isset($title)) $title = '';
else $title = trim(FilterSearch($title));

$wheres = array();

if($title != '')
{
    $wheres[] = " title LIKE '%$title%' ";
}

if($status != -1)
{
    $wheres[] = " status = '$status' ";
}
$whereSql = join(' AND ',$wheres);
if($whereSql!='')
{
    $whereSql = ' WHERE '.$whereSql;
}

$sql  = "SELECT * FROM `#@__joyme_readimg` $whereSql ORDER BY id DESC ";
$dlist = new DataListCP();
$dlist->SetParameter('title',$title);
$dlist->SetParameter('status',$status);
$dlist->SetTemplet(DEDEADMIN."/templets/joyme_readimg.htm");
$dlist->SetSource($sql);
$dlist->display();
