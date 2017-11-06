<?php
/**
 *
 * 列表下页内容
 * 
 */
require_once(dirname(__FILE__)."/../include/common.inc.php");
use Joyme\net\Simple_html_dom;
if(empty($url)){
	die('缺少参数');
}

$url = urldecode($url);
$url = substr($url, strrpos($url, '/'));
$url = str_replace('/pc/', '/wap/', str_replace('#', '/', $url));
$path = str_replace('/article/pc/', '/', $url);
$path = str_replace('/article/', '/', $path);
$filepath = $GLOBALS['cfg_cachedir'].$path;

if(file_exists($filepath)){
	$html = new Simple_html_dom();
	$html->load(file_get_contents($filepath));
	$list = $html->find('ul[id="tj-list"]', 0)->innertext;
	$li = $html->find('div[id=page]', 0)->find('ul',0)->find('li',0);
	if($li){
		$nexturl = $li->find('a', 0)->href;
	}else{
		$nexturl = '';
	}
	$res = array('rs'=>0, 'data'=>array('list'=>$list, 'url'=>$nexturl));
}else{
	$res = array('rs'=>-1, 'data'=>array('list'=>'', 'url'=>''));
}
echo addslashes($_GET['callback']).'('.json_encode($res).')';