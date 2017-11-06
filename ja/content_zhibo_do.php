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

//检查权限许可，总权限
CheckPurview('a_List,a_AccList,a_MyList');

if(empty($dopost)) $dopost = "";

$aid = isset($aid) ? preg_replace("#[^0-9]#", '', $aid) : '';

if(empty($aid))
{
	ShowMsg('对不起，你没指定运行参数！','-1');
	exit();
}

if($dopost=="add")
{
	$url = $apiUrl.'/comment/bean/json/post';
	$data = array('unikey'=>time(), 'domain'=>10, 'groupid'=>$aid,'pic'=>$pic,'description'=>$description,'expstr'=>$expstr);
	$res = json_decode(joymeCurlPostFn($url, $data), true);
	
	var_dump($res);exit;
}
elseif($dopost=="del")
{
	$url = $apiUrl.'/comment/bean/json/del';
	$data = array('cid'=>$cid);
	$res = json_decode(joymeCurlPostFn($url, $data), true);
	
	var_dump($res);exit;
}
else
{
	ShowMsg('对不起，你没指定action！','-1');
	exit();
}