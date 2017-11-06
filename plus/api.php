<?php
require_once (dirname(__FILE__) . "/../include/common.inc.php");
//require_once("../member/config.php");
define('DEDEADMIN', DEDEROOT.'/ja'); //dede修改为你后台的文件夹名称
// require_once(DEDEINC.'/userlogin.class.php');
// require_once(DEDEADMIN.'/../include/common.inc.php');
// require_once(DEDEINC.'/typelink.class.php');
// require_once(DEDEINC.'/datalistcp.class.php');
// require_once(DEDEADMIN.'/inc/inc_list_functions.php');
// require_once(DEDEADMIN.'/inc/inc_batchup.php');
// require_once(DEDEADMIN.'/inc/inc_archives_functions.php');
// require_once(DEDEINC.'/typelink.class.php');
// require_once(DEDEINC.'/arc.archives.class.php');
// require_once(DEDEADMIN."/inc/inc_archives_functions.php");
// require_once(DEDEINC."/arc.partview.class.php");
// require_once(DEDEDATA."/cache/inc_catalog_base.inc");
// require_once(DEDEINC."/channelunit.func.php");
// require_once(DEDEINC."/arc.listview.class.php");
use Joyme\qiniu\Qiniu_Utils;
$a = isset($_GET['a']) ? $_GET['a'] : '';
if($a != '' && function_exists($a)){
	echo $a();
}else{
	echo 'function not found!';
}

/**
 * java 调用获取栏目名称
 */
function getTypeName(){
	global $dsql;
	$ids = isset($_GET['ids']) ? $_GET['ids'] : '';
	if(preg_match('/^[0-9,]+$/i', $ids) == 0){
		die('数据格式错误');
	}
	$query = "SELECT id, typename FROM `#@__arctype` WHERE id IN($ids)";
	$dsql->Execute('me',$query);
	$data = array();
	while($row = $dsql->GetArray()){
		$data[] = $row;
	}
	return json_encode($data);
}

function getAllTags(){
	global $dsql;
	$searchkey = !empty($_GET['searchkey']) ? addslashes($_GET['searchkey']) : '';
	$wheres = ' where status=1 ';
	if($searchkey){
		$wheres .= ' and tag like "%'.$searchkey.'%"';
	}
	$query = "SELECT id,tag FROM `dede_tagindex` {$wheres} ORDER BY total DESC LIMIT 30;";
	$dsql->Execute('tag',$query);
	$tags = array();
	while($row = $dsql->GetArray('tag'))
	{
		$tags[] = $row;
	}
	return $_GET['callback'].'('.json_encode($tags).')';
}

// 获取直播状态
function getLiveStatus(){
    global $dsql;
    $aid = isset($_GET['aid']) ? intval($_GET['aid']) : 0;
    $data = array();
    $statusarr = array('准备中','直播中','已结束','已删除');
	$query = "SELECT `status` FROM dede_addonzhibo WHERE aid = $aid";
	$res = $dsql->getOne($query);
	// $arcquery = "SELECT `arcrank` FROM dede_archives WHERE aid = $aid";
	// $arcdata = $dsql->getOne($arcquery);
	$status = $data[$aid]['status'] = $res['status'];
	// $data[$aid]['arcrank'] = $arcdata['arcrank'];
     echo $_GET['callback'].'('.  json_encode(array('status'=>$statusarr[$status])).')';
}

// 获取直播状态
function getNewLiveStatus(){
    global $dsql;
    $aid = isset($_GET['aid']) ? intval($_GET['aid']) : 0;
    $data = array();
    $statusarr = array('准备中','直播中','已结束','已删除');
	$query = "SELECT `mtype` FROM dede_archives WHERE id = $aid";
	$res = $dsql->getOne($query);
	$status = $res['mtype'];
    echo $_GET['callback'].'('.  json_encode(array('status'=>$statusarr[$status])).')';
}

// 设置直播状态
function setNewLiveStatus(){
    global $dsql;
    $aid = isset($_GET['aid']) ? intval($_GET['aid']) : 0;
	$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
	if($aid == 0 || $type<0 || $type>3){
		$res = array('rs'=>2, 'msg'=>'参数错误');
	}else{
		$upquery = 'UPDATE dede_archives SET mtype = '.$type.' WHERE id = '.$aid;
		$rs = $dsql->ExecuteNoneQuery($upquery);
		$res = array('rs'=>1, 'msg'=>'更新成功');
	}
	echo $_GET['callback'].'('.  json_encode($res).')';
    // $data = array();
    // $statusarr = array('准备中','直播中','已结束','已删除');
	// $query = "SELECT `mtype` FROM dede_archives WHERE id = $aid";
	// $res = $dsql->getOne($query);
	// $status = $res['mtype'];
    // echo $_GET['callback'].'('.  json_encode(array('status'=>$statusarr[$status])).')';
}

// 获取图片上传tooken
function getImageUptoken(){
	global $conf;
	$bucket = $conf['qiniu']['bucket'];
    $uptoken = Qiniu_Utils::Qiniu_UploadToken($bucket);
    echo json_encode(array("uptoken"=>$uptoken));
}

// 七牛uri编码
function base64_urlSafeEncode($data){
	$find = array('+', '/');
	$replace = array('-', '_');
	return str_replace($find, $replace, base64_encode($data));
}
// 获取视频信息
function getVideoInfo($id, $i=0){
	$id = isset($id) ? $id : addslashes($_GET['id']);
	$res = joymeCurlGetFn('http://api.qiniu.com/status/get/prefop?id='.$id);
	$data = json_decode($res, true);
	if($data['code'] != 0 && $i<=30){
		sleep(1);
		$i++;
		getVideoInfo($id, $i);
	}else{
		echo "getvideoinfo($res)";
	}
	//var_dump($data);
}
// 获取视频上传tooken
function getVideoUptoken(){
	global $conf;
	$bucket = 'joymevideo';
	$sets = array();
	$time = time();
	$uri = $bucket.':article/'.date('Ym', $time).'/'.date('d', $time).$time.'.mp4';
	//file_put_contents('D:/a.txt', $uri);
	$sets = array(
		'PersistentOps'=>'avthumb/mp4/vframe/jpg/offset/1/rotate/auto|saveas/'.base64_urlSafeEncode($uri),
		'PersistentPipeline'=>'joymetest');
		
    $uptoken = Qiniu_Utils::Qiniu_UploadToken($bucket, $sets);
    echo json_encode(array("uptoken"=>$uptoken));
}

// 获取主持人信息
function getReportMsg(){
    global $dsql;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if($id == 0){
        echo 'id不能为0';exit;
    }
    $query = "SELECT * FROM dede_reporter WHERE id = $id";
    $res = $dsql->getOne($query);
    if($res){
        echo $_GET['callback'].'('.  json_encode(array('msg'=>$res)).')';
    }else{
        echo $_GET['callback'].'({})';
    }
}

// 获取文章缩略图
function getArticleImg(){
    global $dsql, $conf;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$callback = isset($_GET['callback']) ? $_GET['callback'] : '';
	$res = array();
    if($id == 0){
        $res['code'] = 1;
		$res['errmsg'] = '参数错误';
		rData($res);
		exit;
    }
    $query = "SELECT litpic FROM dede_archives WHERE id = $id";
    $data = $dsql->getOne($query);
    if($data){
        $res['code'] = 0;
		$res['result'] = array('litpic'=>$conf['qiniu']['attachurl'].$data['litpic']);
		rData($res);
    }else{
        $res['code'] = 2;
		$res['errmsg'] = '查询结果为空';
		rData($res);
    }
}

function rData($data){
	$callback = isset($_GET['callback']) ? $_GET['callback'] : '';
	if($callback){
		echo $callback.'('.json_encode($data).')';
	}else{
		echo json_encode($data);
	}
}

// 从wiki导入到cms
function addCmsForWikipage(){
	global $dsql;
	$sid = isset($_POST['sid']) ? intval($_POST['sid']) : 0;
	$edit_time = isset($_POST['edit_time']) ? intval($_POST['edit_time']) : 0;
	$title = isset($_POST['title']) ? $_POST['title'] : '';
	
	$res = array();
	if(empty($sid) || empty($edit_time) || empty($title)){
		$res['code'] = 1;
		$res['errmsg'] = '参数错误';
		rData($res);
		exit;
	}
	$query = "SELECT id FROM joyme_archives_wiki WHERE sid = $sid and title = '$title'";
	$data = $dsql->getOne($query);
	if($data){
		$sql = "update `joyme_archives_wiki` set edit_time='$edit_time' where id=".$data['id'];
		$dsql->ExecuteNoneQuery($sql);
		$res['code'] = 0;
		rData($res);
	}else{
		$iquery = "INSERT INTO `joyme_archives_wiki` (`title`,`sid`,`edit_time`) VALUES ('$title','$sid', '$edit_time') ";
		$dsql->ExecuteNoneQuery($iquery);
		$res['code'] = 0;
		rData($res);
	}
}
// 从wiki导入到cms站点
function addCmsSite(){
	global $dsql;

	$sid = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
	$site_type = isset($_POST['site_type']) ? intval($_POST['site_type']) : 0;
	$site_name = isset($_POST['site_name']) ? $_POST['site_name'] : '';
	$site_key = isset($_POST['site_key']) ? $_POST['site_key'] : '';
	$create_time = time();

	$res = array();
	if(empty($sid) || empty($site_name) || empty($site_key)){
		$res['code'] = 1;
		$res['errmsg'] = '参数错误';
		rData($res);
		exit;
	}
	$iquery = "INSERT INTO `joyme_sites` (`site_id`,`site_type`,`site_name`,`site_key`,`create_time`) VALUES ('$sid','$site_type', '$site_name', '$site_key','$create_time') ";
	$dsql->ExecuteNoneQuery($iquery);
	$res['code'] = 0;
	rData($res);
}