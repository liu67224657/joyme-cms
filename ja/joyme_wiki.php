<?php
/**
 * wiki 列表
 **/

require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC.'/JoymeWikiWords.class.php');

use Joyme\core\Request;
$name	= Request::getParam('name') ? Request::getParam('name') : '';
$pnum	= Request::get('pnum') ? Request::get('pnum') : 1;
$psize	= Request::get('psize') ? Request::get('psize') : 20;

$url = $webcacheurl.'/wiki/title/query.do';
$params = array('pnum'=>$pnum, 'psize'=>$psize);
if($name){
	$params['name'] = urlencode($name);
}
$data = joymeCurlPostFn($url, $params);
$data = json_decode($data, true);

if($data['rs'] == 1){
	$JoymeWikiWords = new JoymeWikiWords();
	$purl = '/ja/joyme_wiki.php?';
	if($name){
		$purl .= '&name='.urlencode($name).'&';
	}
	$pagelisthtml = $JoymeWikiWords->getPageListHtml($data['result']['page'], $purl);
	$list = $data['result']['rows'];
	include(DEDEADMIN."/templets/joyme_wiki.htm");
}else{
	exit('接口出错');
}


