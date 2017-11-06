<?php
/**
 * 获取TAGS管理
 *
 * @version        $Id: tag_test_action.php 1 23:07 2010年7月20日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__).'/config.php');
CheckPurview('sys_Keyword');
require_once(DEDEINC.'/datalistcp.class.php');
$timestamp = time();
$adminid = $cuserLogin->getUserID();
if(empty($tag)) $tag = '';

if(empty($action))
{
    $orderby = empty($orderby) ? 'id' : preg_replace("#[^a-z]#i", '', $orderby);
    $orderway = isset($orderway) && $orderway == 'asc' ? 'asc' : 'desc';
    if(!empty($tag)) $where = " where tag like '%$tag%'";
    else $where = '';

    $neworderway = ($orderway == 'desc' ? 'asc' : 'desc');
    $query = "SELECT * FROM `#@__tagindex` $where ORDER BY $orderby $orderway";
    $dlist = new DataListCP();
    $tag = stripslashes($tag);
    $dlist->SetParameter("tag", $tag);
    $dlist->SetParameter("orderway", $orderway);
    $dlist->SetParameter("orderby", $orderby);
    $dlist->pageSize = 30;
    $dlist->SetTemplet(DEDEADMIN."/templets/tags_main.htm");
    $dlist->SetSource($query);
    $uidarr = array();
    $dlist->PreLoad();
    $arcarr = $dlist->GetArcList('');
    foreach($arcarr as $val){
        if(!in_array($val['uid'], $uidarr) && $val['uid']!=0){
            $uidarr[] = $val['uid'];
        }
    }
    $ids = implode(',', $uidarr);
    $userquery = "SELECT id,uname FROM `#@__admin` where id in ({$ids})";
    $dsql->Execute('user',$userquery);
    $user = array();
    while($row = $dsql->GetArray('user'))
    {
        $user[] = $row;
    }
    $users = array();
    foreach($user as $val){
        $users[$val['id']] = $val['uname'];
    }
    $users[0] = '无';
    $doPreLoad = false;
    $dlist->Display($doPreLoad);
    exit();
}
/*
function update()
*/
else if($action == 'update')
{
    $tid = (empty($tid) ? 0 : intval($tid) );
    if(empty($tid))
    {
        ShowMsg('没有选择要删除的tag!','-1');
        exit();
    }
    if($count){
        $count = intval($count);
        $query = "UPDATE `#@__tagindex` SET `count`='$count' WHERE id='$tid' ";
        $dsql->ExecuteNoneQuery($query);
        ShowMsg("成功保存标签的点击信息!", 'tags_main.php');
    }else if($tagname){
        $tagname = addslashes($tagname);
        $selsql = "SELECT * FROM dede_tagindex WHERE tag = '$tagname'";
        $rows = $dsql->GetOne($selsql);
        if(!empty($rows) && $rows['id'] != $tid){
            ShowMsg("tag 不能重复添加", 'tags_main.php');
            exit();
        }
        $query = "UPDATE `#@__tagindex` SET `tag`='$tagname', `uid`='$adminid' WHERE id='$tid' ";
        $dsql->ExecuteNoneQuery($query);
        ShowMsg("成功保存编辑信息!", 'tags_main.php');
    }
   
    exit();
}
/*
function delete()
*/
else if($action == 'delete')
{
    if(@is_array($ids))
    {
        $stringids = implode(',', $ids);
    }
    else if(!empty($ids))
    {
        $stringids = $ids;
    }
    else
    {
        ShowMsg('没有选择要删除的tag','-1');
        exit();
    }
//    $query = "DELETE FROM `#@__tagindex` WHERE id IN ($stringids)";
    $status = intval($status) == 0 ? 1 : 0;
    $query = "UPDATE dede_tagindex SET `status`={$status}, uid='{$adminid}' WHERE id IN ($stringids)";
    if($dsql->ExecuteNoneQuery($query))
    {
//        $query = "DELETE FROM `#@__taglist` WHERE tid IN ($stringids)";
//        $dsql->ExecuteNoneQuery($query);
        if($status==0){
            $msg = '删除';
        }else{
            $msg = '恢复';
        }
        ShowMsg("{$msg}tags[ $stringids ]成功", 'tags_main.php');
    }
    else
    {
        ShowMsg("删除tags[ $stringids ]失败", 'tags_main.php');
    }
    exit();
}
/*
function addtag()
*/
else if($action == 'addtag')
{
    $goto = "tags_main.php";
    $tid = 0;
    if(empty($tag)){
        $msg = '添加失败';
    }else{
        $tag = addslashes($tag);
        $selsql = "SELECT * FROM dede_tagindex WHERE tag = '$tag'";
        $rows = $dsql->GetOne($selsql);
        if($rows['tag']){
            $msg = 'tag 不能重复添加';
        }else{
            $now = time();
            $insertsql = "INSERT INTO dede_tagindex(tag, uid, addtime) VALUES('{$tag}', $adminid, {$now})";
            $dsql->ExecuteNoneQuery($insertsql);
            $tid = $dsql->GetLastID();
        }
    }
    if($tid){
        $msg = '添加成功';
    }
    ShowMsg($msg.'，跳转标签列表页 ...', $goto, 0, 500);
    exit();
}
/*
function fetch()
*/
else if($action == 'fetch')
{
    $wheresql = '';
    $start = isset($start) && is_numeric($start) ? $start : 0;
    $where = array();
    if(isset($startaid) && is_numeric($startaid) && $startaid > 0)
    {
        $where[] = " id>$startaid ";
    }
    else
    {
        $startaid = 0;
    }
    if(isset($endaid) && is_numeric($endaid) && $endaid > 0)
    {
        $where[] = " id<$endaid ";
    }
    else
    {
        $endaid = 0;
    }
    if(!empty($where))
    {
        $wheresql = " WHERE arcrank>-1 AND ".implode(' AND ', $where);
    }
    $query = "SELECT id as aid,arcrank,typeid,keywords,showpc,showios,showandroid FROM `#@__archives` $wheresql LIMIT $start, 100";
    $dsql->SetQuery($query);
    $dsql->Execute();
    $complete = true;
    while($row = $dsql->GetArray())
    {
        $aid = $row['aid'];
        $typeid = $row['typeid'];
        $arcrank = $row['arcrank'];
		$showpc = $row['showpc'];
		$showios = $row['showios'];
		$showandroid = $row['showandroid'];
        $row['keywords'] = trim($row['keywords']);
        if($row['keywords']!='' && !preg_match("#,#", $row['keywords']))
        {
            $keyarr = explode(' ', $row['keywords']);
        }
        else
        {
            $keyarr = explode(',', $row['keywords']);
        }
        foreach($keyarr as $keyword)
        {
            $keyword = trim($keyword);
            if($keyword != '' && strlen($keyword)<21 )
            {
                $keyword = addslashes($keyword);
                $row = $dsql->GetOne("SELECT id FROM `#@__tagindex` WHERE tag LIKE '$keyword'");
                if(is_array($row))
                {
                    $tid = $row['id'];
                    $query = "UPDATE `#@__tagindex` SET `total`=`total`+1 WHERE id='$tid' ";
                    $dsql->ExecuteNoneQuery($query);
                }
                else
                {
                    $query = " INSERT INTO `#@__tagindex`(`tag`,`count`,`total`,`weekcc`,`monthcc`,`weekup`,`monthup`,`addtime`) VALUES('$keyword','0','1','0','0','$timestamp','$timestamp','$timestamp');";
                    $dsql->ExecuteNoneQuery($query);
                    $tid = $dsql->GetLastID();
                }
                $query = "REPLACE INTO `#@__taglist`(`tid`,`aid`,`typeid`,`arcrank`,`tag`,`showpc`,`showios`,`showandroid`) VALUES ('$tid', '$aid', '$typeid','$arcrank','$keyword','$showpc','$showios','$showandroid'); ";
                $dsql->ExecuteNoneQuery($query);
            }
        }
        $complete = FALSE;
    }
    if($complete)
    {
        ShowMsg("tags获取完成", 'tags_main.php');
        exit();
    }
    $start = $start + 100;
    $goto = "tags_main.php?action=fetch&startaid=$startaid&endaid=$endaid&start=$start";
    ShowMsg('继续获取tags ...', $goto, 0, 500);
    exit();
}