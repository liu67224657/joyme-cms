<?php
/**
 *add wiki word
 *10:29 2016/8/22
 **/

require_once(dirname(__FILE__)."/config.php");
use Joyme\core\Request;
$wikiid	= Request::getParam('wikiid');
$sb = Request::post('sb');
if( $sb == '提交' ){
	$word	= Request::post('word') ? Request::post('word') : '';
	$wordurl	= Request::post('wordurl') ? Request::post('wordurl') : '';
	$url = $webcacheurl.'/wiki/keyword/report.do';
	$params = array('wikiid'=>$wikiid, 'keyword'=>urlencode($word), 'url'=>urlencode($wordurl));
	$data = joymeCurlPostFn($url, $params);
	$data = json_decode($data, true);
	if($data['rs'] == 1){
		header("Location:/ja/joyme_wikiword.php?wikiid=".$wikiid);
	}else{
		echo '<script>alert("'.$data['msg'].'");history.go(-1);</script>';
		// var_dump($data);exit;
		// exit('程序出错');
	}
}else{
	include(DEDEADMIN."/templets/joyme_wikiword_add.htm");
}