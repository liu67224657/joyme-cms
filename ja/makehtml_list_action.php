<?php
set_time_limit(0);
/**
 * 生成列表栏目操作
 *
 * @version        $Id: makehtml_list_action.php 1 11:09 2010年7月19日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
if(empty($is_check)) $is_check = true;
require_once(dirname(__FILE__)."/config.php");
if(!isset($is_check) || $is_check != 'false'){
	CheckPurview('sys_MakeHtml');
}
require_once(DEDEDATA."/cache/inc_catalog_base.inc");
require_once(DEDEINC."/channelunit.func.php");

if(!isset($upnext)) $upnext = 1;
if(empty($gotype)) $gotype = '';
if(empty($pageno)) $pageno = 0;
if(empty($mkpage)) $mkpage = 1;
if(empty($typeid)) $typeid = 0;
if(!isset($uppage)) $uppage = 0;
if(empty($maxpagesize)) $maxpagesize = 50;
if(empty($tpl)) $tpl = 'pc';
if(empty($channeltpls)) $channeltpls = '';
$adminID = $cuserLogin->getUserID();

$isremote = (empty($isremote)  ? 0 : $isremote);
$serviterm = empty($serviterm)? "" : $serviterm;
$typeid = intval($typeid);
//检测获取所有栏目ID
//普通生成或一键更新时更新所有栏目
if($gotype=='' || $gotype=='mkallct')
{
    if($upnext==1 || $typeid==0)
    {
        if($typeid>0) 
        {
            $tidss = GetSonIds($typeid,0);
            $idArray = explode(',',$tidss);
        } else {
            foreach($cfg_Cs as $k=>$v) $idArray[] = $k;
        }
    } else {
        $idArray = array();
        $idArray[] = $typeid;
    }
}
//一键更新带缓存的情况
else if($gotype=='mkall')
{
    $uppage = 1;
    $mkcachefile = DEDEDATA."/mkall_cache_{$adminID}.php";
    $idArray = array();
    if(file_exists($mkcachefile)) require_once($mkcachefile);
}

//当前更新栏目的ID
$totalpage=count($idArray);
if(isset($idArray[$pageno]))
{
    $tid = $idArray[$pageno];
}
else
{
    if($gotype=='')
    {
        ShowMsg("完成所有列表更新！","javascript:;");
        exit();
    }
    else if($gotype=='mkall' || $gotype=='mkallct')
    {
        ShowMsg("完成所有栏目列表更新，现在作最后数据优化！","makehtml_all.php?action=make&step=10");
        exit();
    }
}

if($pageno==0 && $mkpage==1) //清空缓存
{
    $dsql->ExecuteNoneQuery("Delete From `#@__arccache` ");
}

$reurl = '';

//更新数组所记录的栏目
if(!empty($tid))
{
    if(!isset($cfg_Cs[$tid]))
    {
        showmsg('没有该栏目数据, 可能缓存文件(/data/cache/inc_catalog_base.inc)没有更新, 请检查是否有写入权限');
        exit();
    }
    if($cfg_Cs[$tid][1]>0)
    {
        require_once(DEDEINC."/arc.listview.class.php");
        $lv = new ListView($tid);
        $position= MfTypedir($lv->Fields['typedir']);
    }
    else
    {
        require_once(DEDEINC."/arc.sglistview.class.php");
        $lv = new SgListView($tid);        
    }
    if($lv->Fields['channeltemp'] != '' && $tpl == 'pc' && $pageno==0){
        $channeltpls = $lv->Fields['channeltemp'];
    }
    if($channeltpls != ''){
        $channeltemp = explode(',', $channeltpls);
    }

    $lv->CountRecord($tpl);// 统计总数加载模板
    if($lv->TypeLink->TypeInfos['ispart']==0 && $lv->TypeLink->TypeInfos['isdefault']!=-1) $ntotalpage = $lv->TotalPage;
    else $ntotalpage = 1;

    if($cfg_remote_site=='Y' && $isremote=="1")
    {
        if($serviterm!="")
        {
            list($servurl, $servuser, $servpwd) = explode(',',$serviterm);
            $config = array( 'hostname' => $servurl, 'username' => $servuser, 
                             'password' => $servpwd,'debug' => 'TRUE');
        } else {
            $config=array();
        }
        if(!$ftp->connect($config)) exit('Error:None FTP Connection!');
    }
    //如果栏目的文档太多，分多批次更新

    if($ntotalpage <= $maxpagesize || $lv->TypeLink->TypeInfos['ispart']!=0 || $lv->TypeLink->TypeInfos['isdefault']==-1)
    {
        $reurl = $lv->MakeHtml('', '', $isremote, $tpl);
        $finishType = TRUE;
    }
    else
    {
        $reurl = $lv->MakeHtml($mkpage, $maxpagesize, $isremote, $tpl);
        $finishType = FALSE;
        $mkpage = $mkpage + $maxpagesize;
        if( $mkpage >= ($ntotalpage+1) ) $finishType = TRUE;
    }
}

if(!isset($is_check) || $is_check != 'false'){
	$diy_host = '';
	$diy_param = '';
}else{
	$diy_host = 'http://'.$_SERVER['HTTP_HOST'].'/ja/';
	$diy_param = '&is_check=false';
}
$nextpage = $pageno+1;
if($nextpage >= $totalpage && $finishType && !empty($channeltemp)){
    $tpl = array_pop($channeltemp);
    $channeltpls = implode(',', $channeltemp);
    $pageno = 0;
    $mkpage = 1;
    $finishType = false;
}
if($nextpage >= $totalpage && $finishType)
{
    if($gotype=='')
    {
        if(empty($reurl)) { 
            $reurl = '../plus/list.php?tid='.$tid; 
        }else{
            $reurl = '/article/pc'.$reurl;
        }
        ShowMsg("完成所有栏目列表更新！<a href='$reurl' target='_blank'>浏览栏目</a>","javascript:;");
        exit();
    }
    else if($gotype=='mkall' || $gotype=='mkallct')
    {
        ShowMsg("完成所有栏目列表更新，现在作最后数据优化！","makehtml_all.php?action=make&step=10");
        exit();
    }
} else {
    if($finishType)
    {
        $gourl = $diy_host."makehtml_list_action.php?gotype={$gotype}&upnext={$upnext}&tpl={$tpl}&channeltpls={$channeltpls}&uppage=$uppage&maxpagesize=$maxpagesize&typeid=$typeid&pageno=$nextpage&isremote={$isremote}&serviterm={$serviterm}".$diy_param;
        ShowMsg("成功创建栏目：".$tid."，继续进行操作！",$gourl,0,100);
        exit();
    } else {
        $gourl = $diy_host."makehtml_list_action.php?gotype={$gotype}&upnext={$upnext}&tpl={$tpl}&channeltpls={$channeltpls}&uppage=$uppage&mkpage=$mkpage&maxpagesize=$maxpagesize&typeid=$typeid&pageno=$pageno&isremote={$isremote}&serviterm={$serviterm}".$diy_param;
        ShowMsg("栏目：".$tid."，继续进行操作...",$gourl,0,100);
        exit();
    }
}