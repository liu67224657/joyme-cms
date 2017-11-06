<?php
/**
 * @version        $Id: index.php 1 13:41 2010年7月26日Z tianya $
 * @package        DedeCMS.Install
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
@set_time_limit(0);
//error_reporting(E_ALL);
error_reporting(E_ALL || ~E_NOTICE);
header('Content-type:text/html;charset=utf-8');

$lockname = 'init_lock.txt';

if(!empty($_SERVER['HTTP_HOST'])){
	echo "You don't do this";
	exit;
}elseif(file_exists($lockname)){
	echo "please remove lock";
	exit;
}

if(empty($argv[1])){
	die('env error');
}

$env = $argv[1];
if (!in_array($env, array('alpha', 'beta', 'com'))) {
	die("env error");
}

if($env == 'alpha'){
	$link = mysql_connect('172.16.75.75','root','654321') or die('mysql connect fail');
}elseif($env == 'beta'){
	$link = mysql_connect('alyweb002.prod','wikiuser','123456') or die('mysql connect fail');
}elseif($env == 'com'){
	$link = mysql_connect('alyweb005.prod','wikiuser','123456') or die('mysql connect fail');
}
mysql_select_db('article_cms',$link);
mysql_query('SET NAMES utf8',$link);

$templets_path = '../templets/';

$faillist = array();

$sql = 'SELECT * FROM dede_tpl';
$rs = mysql_query($sql);
while($row = mysql_fetch_assoc($rs))
{
	if(substr($row['tplname'],strrpos($row['tplname'],'.')) != '.htm'){
		$faillist[] = $row['tplname'];
		continue;
	}
	//存入文件
	$path = $templets_path.$row['tpldir'].'/';
	
	if(!file_exists($path)){
		createDir($path);
	}
	
	$truefile = $path.$row['tplname'];
	$content = $row['tplcontent'];

	$fp = fopen($truefile, 'w');
	$frs = fwrite($fp, $content);
	if($frs === false){
		$faillist[] = $truefile;
	}
	fclose($fp);
}

echo "all templetsfile to db over\n";

mysql_close($link);

if($faillist){
	echo "save fail file list:\n";
	foreach ($faillist as $v){
		echo $v."\n";
	}
}


file_put_contents($lockname, 'ok');

function createDir($path){

	if (!file_exists($path)){

		createDir(dirname($path));

		mkdir($path);

	}

}

?>