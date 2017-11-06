<?php
/**
 * wiki 词条列表
 **/

require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC.'/JoymeWikiWords.class.php');

use Joyme\core\Request;
$wikiid	= Request::getParam('wikiid');
$pnum	= Request::get('pnum') ? Request::get('pnum') : 1;
$psize	= Request::get('psize') ? Request::get('psize') : 20;
$name	= Request::getParam('name') ? Request::getParam('name') : '';
$wikiname = Request::getParam('wikiname', '');
$url = $webcacheurl.'/wiki/keyword/page/query.do';
$params = array('wikiid'=>$wikiid, 'pnum'=>$pnum, 'psize'=>$psize);
if($name){
	$params['name'] = urlencode($name);
}//var_dump($params);exit;
$data = joymeCurlPostFn($url, $params);
$data = json_decode($data, true);

if($data['rs'] == 1){
	$JoymeWikiWords = new JoymeWikiWords();
	$purl = '/ja/joyme_wikiword.php?wikiid='.$wikiid.'&wikiname='.urlencode($wikiname).'&';
	if($name){
		$purl .= 'name='.urlencode($name).'&';
	}
	$pagelisthtml = $JoymeWikiWords->getPageListHtml($data['result']['page'], $purl);
	$list = $data['result']['rows'];
	include(DEDEADMIN."/templets/joyme_wikiword.htm");
}else{
	exit('接口出错');
}


