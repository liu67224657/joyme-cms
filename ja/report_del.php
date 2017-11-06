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
if(empty($id)){
	ShowMsg("ID都不可以为空","-1");
	exit();
}else{
	
	$row = $dsql->GetOne("SELECT status FROM #@__reporter WHERE id = '$id'");
	if(!is_array($row))
	{
		ShowMsg("您要操作的主持人不存在！","-1");
		exit();
	}
	if($row['status'] == '0'){
		$sta = 1;
	}else{
		$sta = 0;
	}
	
	$query = "UPDATE `#@__reporter`
	SET
	status='$sta'
	WHERE id='$id'
	";
	
	$dsql->ExecuteNoneQuery($query);
	ShowMsg("操作成功！","report.php");
    exit();
}