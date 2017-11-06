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
ShowMsg('该功能已被弃用！','-1');
exit();
require_once(DEDEINC.'/typelink.class.php');
require_once(DEDEINC.'/datalistcp.class.php');
require_once(DEDEADMIN.'/inc/inc_list_functions.php');
require_once(DEDEADMIN."/inc/inc_catalog_options.php");

//检查权限许可，总权限
CheckPurview('a_List,a_AccList,a_MyList');

$aid = isset($aid) ? preg_replace("#[^0-9]#", '', $aid) : '';
if(empty($aid))
{
	ShowMsg('对不起，你没指定运行参数！','-1');
	exit();
}

$url = $apiUrl.'/comment/bean/json/querybygroup';

$data = array('domain'=>10, 'groupid'=>$aid, 'pnum'=>1,'psize'=>200);
$res = json_decode(joymeCurlPostFn($url, $data), true);

if($res['rs'] != '1'){
	ShowMsg('对不起，运行错误！'.$res['msg'].' '.$res['rs'],'-1');
	exit();
}

$data = $res['result']['rows'];

use Joyme\qiniu\Qiniu_Utils;

$uptoken = Qiniu_Utils::Qiniu_UploadToken('joymepic');

$reportersql = "SELECT id,name FROM dede_reporter WHERE `status` = 1";
$dsql->SetQuery($reportersql);
$dsql->Execute();
$reporters = array();
while($row = $dsql->GetArray()){
	$reporters[] = $row;
}

$query = "SELECT * FROM dede_archives arc LEFT JOIN dede_addonzhibo zb ON arc.id=zb.aid WHERE id = $aid";
$zhibodata = $dsql->getOne($query);

$reporterquery = "SELECT * FROM dede_reporter WHERE `status` = 1 AND id = ".$zhibodata['compere'];
$reporterdata = $dsql->getOne($reporterquery);

//初始化
$dlist = new DataListCP();
$dlist->pageSize = 200;
    
//模板
$s_tmplets = 'templets/content_zhibo_info.htm';
$dlist->SetTemplate(DEDEADMIN.'/'.$s_tmplets);

//显示
$doPreLoad = false;
$dlist->Display($doPreLoad);
$dlist->Close();