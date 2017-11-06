<?php

require_once (dirname(__FILE__) . "/include/common.inc.php");
define('DEDEADMIN', DEDEROOT.'/ja');
require_once(DEDEINC."/arc.listview.class.php");
require_once(DEDEINC."/helpers/channelunit.helper.php");
header("Content-type: text/xml; charset=utf-8");
#当前时间，判断文章发布时间
$now = time();
#配置不需要生成的栏目
$unmakecolumn = array();
#栏目核心类
$lv = new ListView();
# sitemap 路径
$catchpath = $lv->GetTruePath();

#获取参数
$key = !empty($_GET['r']) ? str_replace('/article', '', $_GET['r']) : '';
$key = !empty($key) ? str_replace('/sitemap.xml', '', $key) : '';

$typedir = $GLOBALS['cfg_cmspath'].$key;
$filepath = $catchpath.$typedir.'/sitemap.xml';
// if(file_exists($filepath) && filemtime($filepath)+86400>$now){
	// echo file_get_contents($filepath);exit;
// }

if(empty($key)){
	// 全站栏目索引sitemap
	saveColumnSitemap();exit;
}else if(!preg_match('/^[\w\/]+$/i', $key)){
	header("Content-type: text/html; charset=utf-8");
	echo '参数格式不对';
	exit;
}


if(!is_dir($catchpath.$typedir)){
	CreateDir($typedir);
}
// $typedir = $catchpath.$GLOBALS['cfg_cmspath'].$key;
# 栏目数据
$column = array();

#查找所有栏目id
$tids = getColumnIds();

article();
// 统计文章
function article(){
	global $dsql, $column, $tids, $now, $key;
	$articlearr = getArticleData();
	if(!empty($articlearr)){
		mksitemap($articlearr);
	}else{
		header("Content-type: text/html; charset=utf-8");
		echo $key.'该栏目下没有找到文章';
	}
}

// 获取文章数据
function getArticleData(){
	global $dsql, $column, $tids, $now;
	$article = array();
	$query = "SELECT id, typeid, senddate, title, ismake, money FROM dede_archives WHERE typeid in ($tids) AND pubdate < $now AND arcrank>=0 ORDER BY pubdate desc";
	$dsql->Execute('me',$query);
	while($row = $dsql->GetArray()){
		$article[] = $row;
	}
	return $article;
}

// 制作sitemap.xml
function mkSiteMap($article){
	global $column, $catchpath, $typedir, $key;
	$sitemaplevelarr = array(1=>'0.8', 2=>'0.6');
	$filepath = $catchpath.$typedir.'/sitemap.xml';
	$dom = new DomDocument('1.0', 'utf-8');
	$dom->formatOutput = false;
	//  创建根节点
	$urlset = $dom->createElement('urlset');
	$dom->appendchild($urlset);

	$url = $dom->createElement('url');
	$urlset->appendchild($url);

	$loc = $dom->createElement('loc');
	$url->appendchild($loc);
	$text = $dom->createTextNode($GLOBALS['domain'].'/article'.str_replace('{cmspath}', $GLOBALS['cfg_cmspath'], $key).'/index.html');
	$loc->appendChild($text);

	$loc = $dom->createElement('priority');
	$url->appendchild($loc);
	$level = substr_count($key, '/');
	$text = $dom->createTextNode($sitemaplevelarr[$level]);
	$loc->appendChild($text);

	foreach ($article as $v) {
		$url = $dom->createElement('url');
		$urlset->appendchild($url);

		$loc = $dom->createElement('loc');
		$url->appendchild($loc);
		$articleurl = getArticleUrl($v, $column[$v['typeid']]);
		$text = $dom->createTextNode($articleurl);
		$loc->appendChild($text);

		$loc = $dom->createElement('priority');
		$url->appendchild($loc);
		$text = $dom->createTextNode('0.5');
		$loc->appendChild($text);
	}
	$dom->save($filepath);
	echo file_get_contents($filepath);
}

// 获取文章链接
function getArticleUrl($article, $column){
	// global $catchpath;
	$url = str_replace('{Y}', date('Y', $article['senddate']), $column['namerule']);
	$url = str_replace('{M}', date('m', $article['senddate']), $url);
	$url = str_replace('{D}', date('d', $article['senddate']), $url);
	$url = str_replace('{aid}', $article['id'], $url);
	$url = str_replace('{typedir}', $column['typedir'], $url);
	$url = str_replace('{cmspath}', $GLOBALS['cfg_cmspath'], $url);
	return $GLOBALS['domain'].'/article'.$url;
}

// 获取栏目id
function getColumnIds(){
	global $dsql, $key, $column;
	$key = '{cmspath}'.$key;
	$idArr = array();
	$query = "SELECT id, reid, topid, corank, typename, namerule, namerule2, typedir, defaultname FROM dede_arctype where typedir like '{$key}%'";
	$dsql->Execute('me',$query);
	while($row = $dsql->GetArray()){
		$idArr[] = $row['id'];
		$column[$row['id']] = $row;
	}
	return implode(',', $idArr);
}

// 生成栏目索引sitemap
function saveColumnSitemap(){
	global $dsql, $catchpath, $typedir, $key;
	$column = array();
	$query = "SELECT id, reid, topid, corank, typename, namerule, namerule2, typedir, defaultname FROM dede_arctype";
	$dsql->Execute('me',$query);
	while($row = $dsql->GetArray()){
		$column[] = $row;
	}
	$date = date('Y-m-d', time());
	$filepath = $catchpath.$typedir.'/sitemap.xml';
	$dom = new DomDocument('1.0', 'utf-8');
	$dom->formatOutput = false;
	//  创建根节点
	$urlset = $dom->createElement('sitemapindex');
	$dom->appendchild($urlset);

	$url = $dom->createElement('sitemap');
	$urlset->appendchild($url);

	$loc = $dom->createElement('loc');
	$url->appendchild($loc);
	$text = $dom->createTextNode($GLOBALS['domain'].str_replace('{cmspath}', $GLOBALS['cfg_cmspath'], $key).'/sitemap.xml');
	$loc->appendChild($text);

	$loc = $dom->createElement('lastmod');
	$url->appendchild($loc);
	$text = $dom->createTextNode($date);
	$loc->appendChild($text);
	foreach ($column as $v) {
		if(countArticle($v['id'])==0){
			continue;
		}else if(strpos($v['typedir'], '../') !== false){
			continue;
		}
		$url = $dom->createElement('sitemap');
		$urlset->appendchild($url);

		$loc = $dom->createElement('loc');
		$url->appendchild($loc);
		$articleurl = getArticleUrl($v, $column[$v['typeid']]);
		$murl = $GLOBALS['domain'].str_replace("{cmspath}",$GLOBALS['cfg_cmspath'],$v['typedir']).'/sitemap.xml';
		$text = $dom->createTextNode($murl);
		$loc->appendChild($text);

		$loc = $dom->createElement('lastmod');
		$url->appendchild($loc);
		$text = $dom->createTextNode($date);
		$loc->appendChild($text);
	}
	$dom->save($filepath);
	echo file_get_contents($filepath);
}

// 统计文章数
function countArticle($aid){
	global $dsql, $catchpath, $typedir, $key;
	$query = "SELECT COUNT(*) as num FROM dede_archives WHERE typeid = $aid AND arcrank>=0";
	$dsql->Execute('me',$query);
	$row = $dsql->GetArray();
	return $row['num'];
}
?>
