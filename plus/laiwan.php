<?php
/**
 *
 * 来玩
 * $r 请求连接
 * $c 客户端类型
 */
require_once(dirname(__FILE__).'/../include/common.inc.php');

$arr = explode('.', $r);
$r = array_shift($arr);
$cats = explode('/', $r);

// 一级首页或排行榜（laiwan/index.html|laiwan/rank.html）
if(count($cats) == 1 && $cats[0] == 'index'){
	index();
	exit;
}else if( count($cats) == 1 && strpos($cats[0], 'list') !== false ){
	laiwanList();
	exit;
}else if( count($cats) == 1 && $cats[0] == 'active' ){
	lawanActive();
	exit;
}

// 二级专题首页或排行榜（laiwan/gcd/index.html|laiwan/gcd/rank.html）
if(count($cats) == 2 && $cats[1] == 'index'){
	gameIndex();
	exit;
}else if(count($cats) == 2 && $cats[1] == 'rank'){
	gameRank();
	exit;
}else if(count($cats) == 2 && strpos($cats[1], 'list') !== false){
	gamelist();
	exit;
}

// 回归cms
$path = $c=='pc' ? '/vip/laiwan/'.$r.'.html' : '/'.$c.'/vip/laiwan/'.$r.'.html';
$filepath = $GLOBALS['cfg_cachedir'].$path;
if(file_exists($filepath)){
	echo file_get_contents($filepath);exit;
}else{
	header("HTTP/1.1 404 Not Found");  
	header("Status: 404 Not Found");  
	exit;
}

// 三级专题列表页（laiwan/rmtj/list_1.html|laiwan/rmtj/list_2.html）
// if( count($cats) == 3 && strpos($cats[2], 'list') !== false ){
	// gamelist();
	// exit;
// }

function index(){
	global $c;
	$conf = toolsConf('lw');
	include(toolsTpl('lw', $c));
}

function laiwanList(){
	global $cats, $c;
	$conf = toolsConf('lw');
	$tmp = explode('_', $cats[0]);
	$tpl = $tmp[0];
	$pageno = isset($tmp[1]) ? $tmp[1] : 1;
	include(toolsTpl('list', $c));
}

function lawanActive(){
	global $c;
	include(toolsTpl('active', $c));
}

function gameIndex(){
	global $cats,$c;
	$conf = toolsConf($cats[0]);
	include(toolsTpl($cats[1], $c));
}

function gameRank(){
	global $cats,$c;
	$conf = toolsConf($cats[0]);
	include(toolsTpl($cats[1], $c));
}

function gamelist(){
	global $cats,$c;
	$conf = toolsConf($cats[0]);
	$tmp = explode('_', $cats[1]);
	$game = $cats[0];
	$tpl = $tmp[0];
	$pageno = isset($tmp[1]) ? $tmp[1] : 1;
	include(toolsTpl($tpl, $c));
}

function toolsConf($game){
	$confpath = DEDEROOT.'/templets/plus/youku_video_'.$game.'.htm';
	if(!file_exists($confpath))die('配置文件缺失');
	$conf = json_decode(file_get_contents($confpath), true);
	return $conf;
}

function toolsTpl($tpl, $c){
	if( $c == 'pc' ) $dir = 'default';
	else $dir = $c;
	$tplpath = DEDEROOT.'/templets/'.$dir.'/game_'.$tpl.'.htm';
	if(!file_exists($tplpath)) die('模板文件缺失');
	return $tplpath;
}

function toolsGetYoukuId($url){
	if($url == '' || strpos($url, 'youku') === false) return '';
	$urlinfo = parse_url($url);
	$dirpath = explode('/', $urlinfo['path']);
	$str = array_pop($dirpath);
	$idstr = str_replace('.html', '', $str);
	$id = str_replace('id_', '', $idstr);
	return $id;
}

function toolsPage($page){
	if(is_array($page) && !empty($page)){
		if($page['maxPage'] == 1){
			return '';
		}
		if(!$page['lastPage']){
			$next = '<li><a href="list_'.($page['curPage']+1).'.html">下一页</a></li>';
			$last = '<li><a href="list_'.$page['maxPage'].'.html">末页</a></li>';
		}else{
			$next = $last = '';
		}
		if(!$page['firstPage']){
			if($page['curPage']-1 == 1){
				$prev = '<li><a href="list.html">上一页</a></li>';
			}else{
				$prev = '<li><a href="list_'.($page['curPage']-1).'.html">上一页</a></li>';
			}
			$first = '<li><a href="list.html">首页</a></li>';
		}else{
			$prev = $first = '';
		}
		//数字
		$no = '';
		foreach($page['displayingPages'] as $val){
			if($page['curPage'] == $val){
				$no .= '<li class="thisclass">'.$val.'</li> ';
			}else{
				if($val == 1){
					$no .= '<li><a href="list.html">'.$val.'</a></li> ';
				}else{
					$no .= '<li><a href="list_'.$val.'.html">'.$val.'</a></li> ';
				}
			}
		}
		$pagehtml = $first.$prev.$no.$next.$last;
		return $pagehtml;
	}else{
		return '';
	}
}

// 优酷游戏视频专区用--分类
function getGameCatVideoData($appkey, $catid, $sub = ''){
	global $vsdkapiUrl;
	if(!$appkey || !$catid){
		return array();
	}
	$url = $vsdkapiUrl.'/cms/api/category?appkey='.$appkey.'&categoryid='.$catid.'&categorytype='.$sub;
	$res = json_decode(joymeCurlGetFn($url), true);
	if($sub){
		return $res['rs']==1 ? $res['result'] : array();
	}else{
		return $res['rs']==1 ? array_pop($res['result']) : array();
	}
}

// 优酷游戏视频专区用--排行
function getGameRankVideoData($appkey){
	global $vsdkapiUrl;
	if(!$appkey){
		return array();
	}
	$url = $vsdkapiUrl.'/cms/api/rank?appkey='.$appkey;
	$res = json_decode(joymeCurlGetFn($url), true);
	return $res['rs']==1 ? $res['result'] : array();
}

// 优酷游戏视频专区用--最新
function getGameNewVideoData($appkey='', $count=10, $page=1){
	global $vsdkapiUrl;
	$url = $vsdkapiUrl.'/cms/api/videolist?appkey='.$appkey.'&count='.$count.'&page='.$page;
	$res = json_decode(joymeCurlGetFn($url), true);
	return $res['rs']==1 ? $res['result'] : array();
}

// 优酷游戏视频专区用--CMS
function getGameCmsVideoData($typeid, $limit){
	global $dsql;
	$arcsql = 'SELECT id,title,shorttitle,litpic,pubdate FROM dede_archives WHERE typeid = '.$typeid.' AND arcrank>-1 AND FIND_IN_SET("j", flag) ORDER BY pubdate DESC LIMIT '.$limit;
	$dsql->Execute('me', $arcsql);
	$lunbo = $ids = array();
	while($row = $dsql->GetArray()){
		$lunbo[] = $row;
		$ids[] = $row['id'];
	}
	$idstr = implode(',', $ids);
	$addsql = 'SELECT redirecturl FROM dede_addonarticle WHERE aid IN('.$idstr.') ORDER BY field(aid,'.$idstr.')';
	$dsql->Execute('me', $addsql);
	$add = array();
	while($row = $dsql->GetArray()){
		$add[] = $row;
	}
	foreach($lunbo as $key=>$val){
		$lunbo[$key]['redirecturl'] = $add[$key]['redirecturl'];
	}
	return $lunbo;
}

// 工具
function formatTime($time){
	$h = sprintf("%02d", floor($time/3600));
	$i = sprintf("%02d", floor($time/60)%60);
	$s = sprintf("%02d", $time%60);
	return $h.':'.$i.':'.$s;
}
