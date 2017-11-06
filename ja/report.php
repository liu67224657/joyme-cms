<?php
/**
 * 图文直播 主持人管理
 *
 * @version        $Id: report.php 1 14:31 2010年7月12日Z pengzhang $
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

$name = empty($name)?'':$name;

//检查权限许可，总权限
CheckPurview('a_List,a_AccList,a_MyList');

$maintable = '#@__reporter';

$whereSql = ' where 1=1 ';

if($name !== ''){
	$whereSql.=' and name like "%'.$name.'%"';
}

$query = "SELECT *
FROM `$maintable` arc
$whereSql
ORDER BY id DESC";


//初始化
$dlist = new DataListCP();
$dlist->pageSize = 100;
//GET参数
$dlist->SetParameter('name', $name);

//模板
$s_tmplets = 'templets/report.htm';
$dlist->SetTemplate(DEDEADMIN.'/'.$s_tmplets);

//查询
$dlist->SetSource($query);
$dlist->Display();
$dlist->Close();