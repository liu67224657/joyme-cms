#!/usr/bin/env php
<?php


if(empty($argv[1])){
    echo 'no argv[1] env';exit;
}

$_SERVER['HTTP_HOST'] = 'article.joyme.'.$argv[1];
//检查权限
$is_check = false;
require_once(dirname(__FILE__) . '/config.php');
require_once(DEDEINC . '/datalistcp.class.php');
require_once(DEDEINC . '/common.func.php');



if (!isset($typeid)) $typeid = 0;
if (!isset($typeid2)) $typeid2 = 0;

if (!isset($wid)) $wid = 0;
if (!isset($arcrank)) $arcrank = 0;

$ENV_GOBACK_URL = empty($_COOKIE["ENV_GOBACK_URL2"]) ? "archives_wiki_list.php" : $_COOKIE["ENV_GOBACK_URL2"];

$oknum = 0;

$where = array(' 1=1 ');
$where[] = ' cid=4 '; //beta改为4
$sql = 'select gid,gamename from channel.channelgame where ' . join(' AND ', $where);
$dsql->SetQuery($sql);//将SQL查询语句格式化
$dsql->Execute();//执行SQL操作

$games = '"xx"';
$gamelist = array();
while ($row = $dsql->GetArray()) {
    $games .= ',"' . $row['gamename'] . '"';
    $gamelist[$row['gamename']] = $row['gid'];
}
//var_dump($games);exit;

$where = array(' 1=1 ');
$where[] = ' maincat=1 ';
$where[] = " gamename in(" . $games . ") ";
$sql = 'select * from wikiurl.baidu_hezuo where ' . join(' AND ', $where);
$dsql->SetQuery($sql);//将SQL查询语句格式化
$dsql->Execute();//执行SQL操作

while ($row = $dsql->GetArray()) {
    $sortrank = $pubdate = $senddate = $row['pubdate'];


    if ($domain == "http://article.joyme.dev") {
        $typeid = "1938";
    } elseif ($domain == "http://article.joyme.alpha") {
        $typeid = "1938";
    } elseif ($domain == "http://article.joyme.beta") {
        $typeid = "1934";
    } elseif ($domain == "http://article.joyme.com") {
        $typeid = "2079";
    } else {
        $typeid = "2079";
    }
    $channelid = 17;
    $adminid = 1;
    //生成文档ID
    $arcID = GetIndexKey(0, $typeid, $sortrank, $channelid, $senddate, $adminid);
    if (empty($arcID)) {
        ShowMsg("无法获得主键，因此无法进行后续操作！", "-1");
        exit();
    }

    //分析处理附加表数据
    $inadd_f = $inadd_v = '';

    //保存到主表

    $title = preg_replace("#\"#", '＂', $row['arctitle']);
    $title = htmlspecialchars(cn_substrR($title, $cfg_title_maxlen));
    $shorttitle = '';
    $writer = '着迷小编';
    $source = '着迷WIKI';
    $litpic = $row['litpic'];
    $description = '';
    $redirecturl = $row['arcurl'];

    $gid = $gamelist[$row['gamename']];

    $flag = ($litpic == '' ? 'j' : 'p,j');

    $query = "INSERT INTO `#@__archives`(id,typeid,typeid2,sortrank,flag,ismake,channel,arcrank,click,money,title,shorttitle,color,writer,source,litpic,pubdate,senddate,mid,voteid,notpost,description,keywords,filename,dutyadmin,weight,showpc,showios,showandroid,categoryid,clientpic,clientnote,tagid,joymearctypes)
    VALUES ('$arcID','$typeid','$typeid2','$sortrank','$flag','-1','$channelid','$arcrank','0','0',
    '$title','$shorttitle','','$writer','$source','$litpic','$pubdate','$senddate',
    '$adminid','0','1','$description','','','$adminid','$arcID','0','0','0','0','','','','$typeid2');";

    if (!$dsql->ExecuteNoneQuery($query)) {
        $gerr = $dsql->GetError();
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
        ShowMsg("把数据保存到数据库主表 `#@__archives` 时出错，请把相关信息提交给DedeCms官方。" . str_replace('"', '', $gerr), "javascript:;");
        exit();
    }

    //保存到附加表
    $cts = $dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id='$channelid' ");
    $addtable = trim($cts['addtable']);
    if (empty($addtable)) {
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__archives` WHERE id='$arcID'");
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
        ShowMsg("没找到当前模型[{$channelid}]的主表信息，无法完成操作！。", "javascript:;");
        exit();
    }
    $useip = GetIP();
    $templet = empty($templet) ? '' : $templet;
    $addfieldkey = '';
    $addfieldval = '';
    if (in_array($addtable, array('dede_addonarticle', 'dede_addon17_lanmu'))) {
        $addfieldkey .= ', htlistimg';
        $addfieldval .= ', \'\'';
        $addfieldkey .= ', wenzhangid';
        $addfieldval .= ', \'\'';
    }
    $addfieldkey .= ', isvideo';
    $addfieldval .= ', \'0\'';
    $body = '';
    $query = "INSERT INTO `{$addtable}`(aid,typeid,redirecturl,templet,userip,body{$inadd_f} " . $addfieldkey . ") Values('$arcID','$typeid','$redirecturl','$templet','$useip','$body'{$inadd_v} " . $addfieldval . ")";
    if (!$dsql->ExecuteNoneQuery($query)) {
        $gerr = $dsql->GetError();
        $dsql->ExecuteNoneQuery("Delete From `#@__archives` where id='$arcID'");
        $dsql->ExecuteNoneQuery("Delete From `#@__arctiny` where id='$arcID'");
        ShowMsg("把数据保存到数据库附加表 `{$addtable}` 时出错，请把相关信息提交给DedeCms官方。" . str_replace('"', '', $gerr), "javascript:;");
        exit();
    }
    // 将 pubdate（发布时间） 更新到arctiny表
    $dsql->ExecuteNoneQuery("UPDATE `#@__arctiny` SET senddate='$pubdate' WHERE id='$arcID'");

    /*
     * $query = "INSERT INTO `#@__archives`(id,typeid,typeid2,sortrank,flag,ismake,channel,arcrank,
     * click,money,title,shorttitle,color,writer,source,litpic,pubdate,senddate,mid,voteid,notpost,
     * description,keywords,filename,dutyadmin,weight,showpc,showios,showandroid,categoryid,clientpic,
     * clientnote,tagid,joymearctypes)
    VALUES ('$arcID','$typeid','$typeid2','$sortrank','$flag','-1','$channelid','$arcrank','0','0',
    '$title','$shorttitle','','$writer','$source','$litpic','$pubdate','$senddate',
    '$adminid','0','1','$description','','','$adminid','$arcID','0','0','0','0','','','','$typeid2');";
    */
    $extra = array(
        'typeid' => $typeid,
        'typeid2' => $typeid2,
        'sortrank' => $sortrank,
        'flag' => $flag,
        'click' => '0',
        'ismake' => '0',
        'arcrank' => $arcrank,
        'money' => '0',
        'title' => $title,
        'color' => '',
        'writer' => $writer,
        'source' => $source,
        'litpic' => $litpic,
        'pubdate' => $pubdate,
        'notpost' => '',
        'description' => $description,
        'keywords' => $keywords,
        'shorttitle' => $shorttitle,
        'filename' => $filename,
        'dutyadmin' => $adminid,
        'weight' => $arcID,
        'showpc' => '0',
        'showios' => '0',
        'showandroid' => '0',
        'categoryid' => '0',
        'clientpic' => '',
        'clientnote' => '',
        'tagid' => '',
        'joymearctypes ' => $typeid2
    );
    $channeldata = array(
        'aid' => $arcID,
        'cid' => 4,
        'gid' => intval($gid),
        'atype' => 5,
        'source' => 1,
        'pubdate' => $pubdate,
        'url' => $redirecturl,
        'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE)
    );

    arcToChannel($channeldata);
    $oknum++;
}

echo $oknum;
exit;
ShowMsg('成功导入了' . $oknum . '条跳转链接！', $ENV_GOBACK_URL);


?>