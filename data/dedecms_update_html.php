<?php
//该脚本需要绕过dedecms登录验证运行，设置全局变量是否验证登录和权限，false不验证
$is_check = false;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'article.joyme.com';
require_once (dirname(__FILE__) . "/../include/common.inc.php");
define('DEDEADMIN', DEDEROOT.'/ja');
// $maxpagesize=10; //每次更新多少文件
$typeid=348;	//需要更新的栏目ID rss 栏目id
$upnext=1;	//1更新子级栏目，0仅更新所选栏目

if(empty($gotype)) $gotype = '';
if(empty($pageno)) $pageno = 0;
if(empty($mkpage)) $mkpage = 1;
if(!isset($uppage)) $uppage = 0;

 include "/opt/www/article/prod/ja/diy_makehtml_list_action.php";