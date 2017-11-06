<?php

$root_dir = str_replace('\\', '/', dirname(dirname(__FILE__)));

//数据库连接信息
$com = substr($_SERVER['HTTP_HOST'], strrpos($_SERVER['HTTP_HOST'], '.') + 1);

if ($com === 'beta') {
    $secretKey = '#4g%klwe';
    $mongo_server = 'alyweb008.prod:15021';
    $cfg_dbhost = 'alyweb002.prod';
    $cfg_dbuser = 'wikiuser';
    $cfg_dbpwd = '123456';
    include_once '/opt/www/joymephplib/beta/phplib.php';
    $cfg_cachedir = '/opt/www/cache/beta/article';
} else if ($com === 'com') {
    $secretKey = 'yh87&sw2';
    $mongo_server = 'alyweb004.prod:15021';
    $cfg_dbhost = 'rm-2zed40rbv0xc9iam0.mysql.rds.aliyuncs.com';
    $cfg_dbuser = 'td_userrw';
    $cfg_dbpwd = '2QWdf#Z9fc0o*$zE';
    require_once '/opt/www/joymephplib/prod/phplib.php';
    $cfg_cachedir = '/opt/www/cache/prod/article';
} else if ($com === 'alpha') {
    $secretKey = '8F5&JL3';
    $mongo_server = '172.16.75.65:4066';
    $cfg_dbhost = '172.16.75.75';
    $cfg_dbuser = 'root';
    $cfg_dbpwd = '654321';
    $cfg_cachedir = '/opt/www/cache/alpha/article';
    include_once '/opt/www/joymephplib/alpha/phplib.php';
} else if ($com === 'dev') {
    $secretKey = '8F5&JL3';
    $mongo_server = '172.16.75.65:4066';
    $cfg_dbhost = '172.16.75.75';
    $cfg_dbuser = 'root';
    $cfg_dbpwd = '654321';
    $cfg_cachedir = '/opt/www/cache/dev/article';
    include_once '/opt/www/joymephplib/phplib.php';
} else {
    $secretKey = '7ejw!9d#';
    $mongo_server = '172.16.75.65:4066';
    $cfg_dbhost = '172.16.75.65';
    $cfg_dbuser = 'rd';
    $cfg_dbpwd = 'rd';
    $cfg_cachedir = '/opt/www/cache/dev/article';
    include_once '/opt/www/joymephplib/alpha/phplib.php';
}

use Joyme\core\Log;

// if ($com != 'com'){
Log::config(Log::DEBUG);
// }else{
// Log::config(Log::NONE);
// }

use Joyme\core\JoymeToolsUser;

$redirect_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
JoymeToolsUser::init($com, $redirect_url);


$cfg_dbname = 'article_cms';
$cfg_dbprefix = 'dede_';
$cfg_db_language = 'utf8';

# 七牛---图片云存储
use Joyme\qiniu\Qiniu_RS_PutPolicy;
use Joyme\qiniu\Qiniu_PutExtra;
use Joyme\qiniu\Qiniu_ImageView;
use Joyme\qiniu\Qiniu_Utils;

if ($com === 'com' || $com === 'beta') {
    $conf['qiniu']['bucket'] = 'joymepic';
    $conf['qiniu']['accessKey'] = 'G8_5kjfXfaufU53Da4bnGQ3YP-dhdmqct9sR6ImI';
    $conf['qiniu']['secretKey'] = 'KXwyeZMxYnsZMqAwojI_IEDkYj69zkwvu8jZP5_a';
    $conf['qiniu']['attachurl'] = 'http://joymepic.joyme.com';
} else {
    $conf['qiniu']['bucket'] = 'joymetest';
    $conf['qiniu']['accessKey'] = 'MMuzPJz8oQrz197-KjYfPy-00s8C1qwNBtbiX7bA';
    $conf['qiniu']['secretKey'] = 'ftEHE9bTV1h_wedrLYpgZaxHaJ6Np3O1hoba0OfP';
    $conf['qiniu']['attachurl'] = 'http://joymetest.qiniudn.com';
}

//关闭掉的功能列表
$purviewCloseList = array('plus_文件管理器', 'sys_Data');

#定时更新设置每次更新3页
$upPageNum = 3;
$ApiSecretKey = '7eJw!9d#';
#域名配置
$toolsUrl = 'http://tools.joyme.' . $com;
$domain = 'http://article.joyme.' . $com;
$apiUrl = 'http://api.joyme.' . $com;
$vsdkapiUrl = 'http://vsdkapi.joyme.' . $com;
// $vsdkapiUrl = 'http://vsdkapi.joyme.com';
$staticUrl = 'http://static.joyme.' . $com;
$qiniuurl = 'http://joymepic.joyme.com';

$joymedefaultpic = 'http://static.joyme.' . $com . '/pc/cms/wikiscroll/images/default.jpg';
$wikidomain = 'http://wiki.joyme.' . $com;
$webcacheurl = 'http://webcache.joyme.' . $com;
$wwwdomain = 'http://www.joyme.' . $com;
$videourl = 'http://joymevideo.joyme.com/';

$channelApiUrl = 'http://channel.joyme.'.$com; //渠道后台接口地址
// $webcacheurl = 'http://webcache.joyme.beta';
#tools & article roid map
#http://wiki.enjoyf.com/wiki/Joyme_tools_uc
/*
  article信息发布员 105
  article离职员工   106
  article友链专员   107
  article专区编辑   108
  article真新闻编辑 109
  article频道管理员 110
  article模板管理   111
  article超级管理员 112
 */


$roidMap = array(
    //'角色id' => '角色权限排序  10最高， 1最小'
    1 => 10,  // tools 超级管理员
    105 => 1,
    106 => 1.1,
    107 => 2.5,
    108 => 3,
    109 => 3.1,
    110 => 5,
    111 => 9,
    112 => 10  // cms 管理员
);
// 定义模板渠道集合
$joymeTpls = array('wap', 'moji', 'bdhz', 'ios', 'android', 'wifi', 'shenma','wikiapp','washare');
// 本地
// $cfg_dbhost = '172.0.0.1';
// $cfg_dbname = 'article_cms';
// $cfg_dbuser = 'root';
// $cfg_dbpwd = '';
// $cfg_dbprefix = 'dede_';
// $cfg_db_language = 'utf8';
?>