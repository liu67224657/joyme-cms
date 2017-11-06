<?php
/**
 *
 * 微信攻略端每天随机一个攻略数字
 * 
 */

$callback= !empty($_GET['callback']) ? $_GET['callback'] : '' ;
$filepath = 'wechat_search_strategy_views.txt';

if(!file_exists($filepath)){
	$views = 303148 + rand(500,600);
	file_put_contents($filepath, $views);
	$result = json_encode(array('views'=>$views));
	echo $callback."($result)";  exit;
}

$ctime = date('Y-m-d', filemtime($filepath));
$today = date('Y-m-d', time());
if($ctime===$today){
	$views = file_get_contents($filepath);
	$result = json_encode(array('views'=>$views));
}else{
	$views = file_get_contents($filepath) + rand(500,600);
	file_put_contents($filepath, $views);
	$result = json_encode(array('views'=>$views));
}
echo $callback."($result)";  exit;