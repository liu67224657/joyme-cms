<?php
require_once (dirname(__FILE__) . "/../include/common.inc.php");
define('DEDEADMIN', DEDEROOT.'/ja');

use Joyme\qiniu\Qiniu_Utils;
use Joyme\core\Request;
use Joyme\net\Curl;
use Joyme\core\Log;

$fn = Request::get('a', '');
if($fn != '' && function_exists($a)){
	echo $fn();
}else{
	echo 'function not found!';
}

// 文章点赞
function licklike(){
	global $dsql;
	$callback = Request::get('callback', '');
	$aid = Request::get('aid', 0);
	$r = array();
	if(!$aid){
		$r = array('rs'=>2, 'msg'=>'参数错误');
		callback($r);exit;
	}
	
	$type = Request::get('type', 0);// 1 赞，2 踩，0 获取数据
	$query = "SELECT `like_count`,unlike_count FROM dede_joyme_arcaddtable WHERE aid = $aid";
	$res = $dsql->getOne($query);
	if($type == 0){
		if(!$res){
			$ins = "INSERT INTO dede_joyme_arcaddtable(aid) VALUES($aid);";
			$dsql->ExecuteNoneQuery($ins);
			$r = array('rs'=>1, 'msg'=>'成功', 'result'=>array('like_count'=>0,'unlike_count'=>0));
		}else{
			$r = array('rs'=>1, 'msg'=>'成功', 'result'=>$res);
		}
		callback($r);exit;
	}
	
	$uid = $_COOKIE['jmuc_u'];
	$ip = getIp();
	$user = $uid ? $uid : $ip;
	$sel = "SELECT id FROM joyme_clicklike WHERE aid = {$aid} AND `user`='{$user}'";
	$selres = $dsql->getOne($sel);
	if(!$selres){
		$ins = "INSERT INTO joyme_clicklike(aid,`user`,`type`) VALUES({$aid}, '{$user}', {$type});";
		$dsql->ExecuteNoneQuery($ins);
	}else{
		$r = array('rs'=>3, 'msg'=>'请勿重复点赞');
		callback($r);exit;
	}
	$field = $type==1 ? 'like_count' : 'unlike_count';
	$up = 'UPDATE dede_joyme_arcaddtable SET `'.$field.'` = `'.$field.'`+1 WHERE aid = '.$aid;
	$dsql->ExecuteNoneQuery($up);
	$type==1 ? ($res['like_count']+=1) : ($res['unlike_count']+=1);
	$r = array('rs'=>1, 'msg'=>'成功', 'result'=>$res);
	callback($r);
}


// 图片吐槽
function shortcomment(){
	global $dsql;
	$aid = Request::get('aid', 0);
	$imgkey = Request::get('imgkey', '');
	$body = Request::get('body', '');
	$r = array();
	if(!$aid || !$imgkey || !$body){
		$r = array('rs'=>2, 'msg'=>'参数错误');
		callback($r);exit;
	}
	$ch = checkWord($body);
	if($ch['rs'] != 1){
		Log::error(__FILE__, json_encode($ch).' body '.$body, 'cms tuji 敏感词');
		$r = array('rs'=>2, 'msg'=>'内容不能包含敏感词');
		callback($r);exit;
	}
	$imgkey = $imgkey;
	$ins = "INSERT INTO joyme_short_comment(aid,imgkey,`like_count`,body) VALUES({$aid}, '{$imgkey}', 0, '{$body}');";
	$dsql->ExecuteNoneQuery($ins);
	$id = $dsql->GetLastID();
	$res = array('id'=>$id, 'like_count'=>0, 'body'=>$body);
	$r = array('rs'=>1, 'msg'=>'吐槽成功', 'result'=>$res);
	callback($r);
	
}

// 吐槽点赞
function shortcommentlike(){
	global $dsql;
	$sclid = Request::get('sclid', 0);
	if(!$sclid){
		$r = array('rs'=>2, 'msg'=>'参数错误');
		callback($r);exit;
	}
	$up = 'UPDATE joyme_short_comment SET `like_count` = `like_count`+1 WHERE id = '.$sclid;
	$dsql->ExecuteNoneQuery($up);
	$r = array('rs'=>1, 'msg'=>'点赞成功');
	callback($r);
}

// 吐槽列表
function shortcommentlist(){
	global $dsql;
	$aid = Request::get('aid', 0);
	$imgkey = Request::get('imgkey', '');
	if(!$aid){
		$r = array('rs'=>2, 'msg'=>'参数错误');
		callback($r);exit;
	}
	$con = $imgkey!='' ? "AND imgkey= '{$imgkey}'" : '';
	$sel = "SELECT * FROM joyme_short_comment WHERE aid = {$aid} ".$con.' ORDER BY id DESC';
	$dsql->Execute('me', $sel);
	$data = array();
	while($row = $dsql->GetArray()){
		$data[] = $row;
	}
	$res = array();
	if($data){
		foreach($data as $row){
			$res[$row['imgkey']][] = array('id'=>$row['id'], 
				'like_count'=>$row['like_count'],
				'body'=>$row['body']
			);
		}
		$data = $res;
	}
	$r = array('rs'=>1, 'msg'=>'成功', 'result'=>$data);
	callback($r);
}

function uptoken(){
	global $conf;
	$bucket = $conf['qiniu']['bucket'];
    $uptoken = Qiniu_Utils::Qiniu_UploadToken($bucket);
	$r = array('rs'=>1, 'msg'=>'成功', 'result'=>array('uptoken'=>$uptoken));
	callback($r);
}

function uptoken2(){
	global $conf;
	$bucket = $conf['qiniu']['bucket'];
    $uptoken = Qiniu_Utils::Qiniu_UploadToken($bucket);
	echo json_encode(array('uptoken'=>$uptoken));
}

function checkWord($con){
	global $com;
	$url = 'http://servapi.joyme.' . $com . '/servapi/verify/word';
	$curl = new Curl();
	$res = $curl->Post( $url, array('word'=>$con) );
	$res = json_decode($res, true);
	return $res;
}

function callback($r){
	$callback = Request::get('callback', '');
	if($callback){
		echo $callback.'('. json_encode($r) .')';
	}else{
		echo json_encode($r);
	}
}