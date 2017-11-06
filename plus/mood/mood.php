<?php
/**
 * @version        $Id: mood.php 1 15:42 2013年09月30日 织梦技术研究中心-土匪 $
 * @package        DedeCMS.Plus
 * @copyright      Copyright (c) 2013, Dedejs, Inc.
 * @license        http://bbs.dedejs.com
 * @link           http://www.dedejs.com
 */
@header("Pragma:no-cache\r\n");
@header("Cache-Control:no-cache\r\n");
@header("Expires:0\r\n");
@header("Content-Type: text/html; charset=utf-8");
$time = time();
$time24 = $time-86400;

require_once(dirname(__FILE__)."../../../include/common.inc.php");

if(empty($aid)) $aid = "1";

if(!isset($action))$action = 'not';
$aid = ereg_replace("[^0-9]","",$aid);
$uip = GetIP();
if($action == 'mood'){
	$rankmood = $mood;
	$mood = 'mood'.$mood;

	//提交投票数据
	$sql = "UPDATE `#@__mood` SET $mood=$mood+1 WHERE `aid` ='$aid'";
	$ranksql = "INSERT INTO `#@__mood_ranking` ( `aid` , `mood` , `time` ,`ip` )VALUES ('$aid', '$rankmood', '$time', '$uip')";
	$dsql->ExecuteNoneQuery2($sql);
	$dsql->ExecuteNoneQuery2($ranksql);
	//exit();
	$action = 'not';
}
if($action == 'not'){
	$sql="Select count(id) as cc From `#@__mood` where `aid`='$aid'";
	$rs=$dsql->GetOne($sql);


	//检查是否有存在投票数据
	if($rs['cc'] == 0){
		$sql = "INSERT INTO `#@__mood` ( `id` , `aid` ) VALUES (NULL , '$aid' )";
		$dsql->ExecuteNoneQuery($sql);
	}

	//查询投票数据
	$row = $dsql->GetOne("SELECT mood1,mood2,mood3,mood4,mood5,mood6,mood7,mood8 FROM `#@__mood` where `aid`='$aid'",MYSQL_NUM);
	$mood1 	= $row[0];	//欠扁
	$mood2 	= $row[1];	//支持
	$mood3 	= $row[2];	//很棒
	$mood4 	= $row[3];  //找骂
	$mood5 	= $row[4];  //搞笑
	$mood6 	= $row[5];	//恶心
	$mood7 	= $row[6];	//不解
	$mood8	= $row[7];	//吃惊
	$moods	= array_sum($row);
	$moodl	= @(80/$moods);


	//检查是否已经投票
	// $tp = $dsql->GetOne("SELECT count( mood ) AS cc FROM `#@__mood_ranking` WHERE aid = '$aid' AND ip = '$uip' AND time > $time24");
	// if($tp['cc'] > 0)$js = 'alert(\'您已经投过票了，请不要重复投票！\');//';
	// else $js = '';
	

	//统计票数比例
	function moodl($mood){
		global $row;
		global $moodl;
		global $cfg_phpurl;
		if($mood == max($row)){
			$mood = ceil($moodl*$mood);
			echo "<img src=\"$cfg_phpurl/mood/img/100.gif\" width=\"20\" height=\"$mood\" />";
		}else if($mood == 0){
			echo "<img src=\"$cfg_phpurl/mood/img/101.gif\" width=\"20\" height=\"1\" />";
		}else{
			$mood = ceil($moodl * $mood);
			echo "<img src=\"$cfg_phpurl/mood/img/101.gif\" width=\"20\" height=\"$mood\" />";
		}
	}
}
ob_start();
include_once('mood.htm');
$str = ob_get_contents();
ob_clean();
$rs = array('rs'=>0, 'html'=>$str);
echo $_GET['callback'].'('.json_encode($rs).')';
$dsql->Close();
exit();
?>