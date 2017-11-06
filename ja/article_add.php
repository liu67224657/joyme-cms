<?php
require_once(dirname(__FILE__) . '/config.php');
CheckPurview('a_New,a_AccNew');
require_once(DEDEINC . '/customfields.func.php');
require_once(DEDEADMIN . '/inc/FileUtil.php');
require_once(DEDEADMIN . '/inc/inc_archives_functions.php');
require_once(DEDEINC . '/mgdb.class.php');
if (file_exists(DEDEDATA . '/template.rand.php')) {
    require_once(DEDEDATA . '/template.rand.php');
}
use Joyme\core\Log;

if (empty($dopost)) $dopost = '';
if ($dopost != 'save') {
    require_once(DEDEINC . "/dedetag.class.php");
    require_once(DEDEADMIN . "/inc/inc_catalog_options.php");
    ClearMyAddon();
    $channelid = empty($channelid) ? 0 : intval($channelid);
    $cid = empty($cid) ? 0 : intval($cid);

    if (empty($geturl)) $geturl = '';

    $keywords = $writer = $source = $body = $description = $title = '';

    //采集单个网页
    if (preg_match("#^http:\/\/#", $geturl)) {
        require_once(DEDEADMIN . "/inc/inc_coonepage.php");
        $redatas = CoOnePage($geturl);
        extract($redatas);
    }

    //获得频道模型ID
    if ($cid > 0 && $channelid == 0) {
        $row = $dsql->GetOne("Select channeltype From `#@__arctype` where id='$cid'; ");
        $channelid = $row['channeltype'];
    } else {
        if ($channelid == 0) {
            $channelid = 1;
        }
    }

    //获得频道模型信息
    $cInfos = $dsql->GetOne(" Select * From  `#@__channeltype` where id='$channelid' ");

    //获取文章最大id以确定当前权重
    $maxWright = $dsql->GetOne("SELECT COUNT(*) AS cc FROM #@__archives");

    //获取着迷画报分类
    $dsql->Execute('category', 'Select * From `#@__category` where typeStatus=1');
    $category = '';
    while ($frow = $dsql->GetArray('category')) {
        $category .= "<option value='" . $frow['id'] . "'>" . $frow['typeName'] . "</option>\r\n";
        //$flagsArr .= ($frow['id']==$flag ? "<option value='{$frow['id']}' selected>{$frow['attname']}</option>\r\n" : "<option value='{$frow['att']}'>{$frow['attname']}</option>\r\n");
    }
    $joymearctypesdata = GetTypeList($cid, $cuserLogin->getUserChannel(), $channelid);//var_dump($joymearctypesdata);exit;
    $joymearctypes = json_encode($joymearctypesdata);
    $typeidSelectHtml = typeidSelectHtml($joymearctypesdata, '');
    include DedeInclude("templets/article_add.htm");
    exit();
} /*--------------------------------
function __save(){  }
-------------------------------*/
else if ($dopost == 'save') {
    require_once(DEDEINC . '/image.func.php');
    require_once(DEDEINC . '/oxwindow.class.php');
    $flag = isset($flags) ? join(',', $flags) : '';
    $notpost = isset($notpost) && $notpost == 1 ? 1 : 0;
    if (empty($typeid2)) $typeid2 = '';
    if (!isset($autokey)) $autokey = 0;
    if (!isset($remote)) $remote = 0;
    if (!isset($dellink)) $dellink = 0;
    if (!isset($autolitpic)) $autolitpic = 0;
    if (!isset($showpc)) $showpc = 0; else $showpc = 1;
    if (!isset($showios)) $showios = 0; else $showios = 1;
    if (!isset($showandroid)) $showandroid = 0; else $showandroid = 1;
    $categoryid = intval($categoryid);
    if (empty($click)) $click = ($cfg_arc_click == '-1' ? mt_rand(50, 200) : $cfg_arc_click);

    if (empty($typeid)) {
        ShowMsg("请指定文档的栏目！", "-1");
        exit();
    }
    if (empty($channelid)) {
        ShowMsg("文档为非指定的类型，请检查你发布内容的表单是否合法！", "-1");
        exit();
    }
    if (!CheckChannel($typeid, $channelid)) {
        ShowMsg("你所选择的栏目与当前模型不相符，请选择白色的选项！", "-1");
        exit();
    }
    if (!TestPurview('a_New')) {
        CheckCatalog($typeid, "对不起，你没有操作栏目 {$typeid} 的权限！");
    }
    if (empty($pubdate)) {
        $pubdate = date('Y-m-d H:i:s', time());
    }

    $articlepubdate = $pubdate;
    //对保存的内容进行处理
    if (empty($writer)) $writer = $cuserLogin->getUserName();
    if (empty($source)) $source = '未知';
    $pubdate = GetMkTime($pubdate);
    $senddate = time();
    $sortrank = AddDay($pubdate, $sortup);
//	$pubdate = time();
    $ismake = $ishtml == 0 ? -1 : 0;
    $title = preg_replace("#\"#", '＂', $title);
    $title = htmlspecialchars(cn_substrR($title, $cfg_title_maxlen));
    $shorttitle = cn_substrR($shorttitle, 36);
    $color = cn_substrR($color, 7);
    $writer = cn_substrR($writer, 20);
    $source = cn_substrR($source, 30);
    $description = cn_substrR($description, $cfg_auot_description);
    $keywords = str_replace('，', ',', trim(cn_substrR($keywords, 60)));
    $keywords = str_replace('《', '', $keywords);
    $keywords = str_replace('》', '', $keywords);
    $filename = trim(cn_substrR($filename, 40));
    $userip = GetIP();
    $isremote = (empty($isremote) ? 0 : $isremote);
    $serviterm = empty($serviterm) ? "" : $serviterm;
//    $displaytag = $displaytag ? $displaytag : 0;
    $joymearctypes = $typeid2;//empty($joymearctypes) ? '' : $joymearctypes;
    if (!TestPurview('a_Check,a_AccCheck,a_MyCheck')) {
        $arcrank = -1;
    }
    $adminid = $cuserLogin->getUserID();

    //处理上传的缩略图
    if (empty($ddisremote)) {
        $ddisremote = 0;
    }
    $litpic = GetDDImage('none', $picname, $ddisremote);

    //生成文档ID
    $arcID = GetIndexKey($arcrank, $typeid, $sortrank, $channelid, $senddate, $adminid);
    if (empty($arcID)) {
        ShowMsg("无法获得主键，因此无法进行后续操作！", "-1");
        exit();
    }
    if (trim($title) == '') {
        ShowMsg('标题不能为空', '-1');
        exit();
    }

    //处理body字段自动摘要、自动提取缩略图等
    $body = AnalyseHtmlBody($body, $description, $litpic, $keywords, 'htmltext');

    //自动分页
    if ($sptype == 'auto') {
        $body = SpLongBody($body, $spsize * 1024, "#p#分页标题#e#");
    }
    // 添加wiki词条链接
    if ($wikiid) {
        $body = cmsWikiWords($body, explode(',', $wikiid));
    }
    //分析处理附加表数据
    $inadd_f = $inadd_v = '';
    if (!empty($dede_addonfields)) {
        $addonfields = explode(';', $dede_addonfields);
        if (is_array($addonfields)) {
            foreach ($addonfields as $v) {
                if ($v == '') continue;
                $vs = explode(',', $v);
                if ($vs[1] == 'htmltext' || $vs[1] == 'textdata') {
                    ${$vs[0]} = AnalyseHtmlBody(${$vs[0]}, $description, $litpic, $keywords, $vs[1]);
                } else {
                    if (!isset(${$vs[0]})) ${$vs[0]} = '';
                    ${$vs[0]} = GetFieldValueA(${$vs[0]}, $vs[1], $arcID);
                }
                $inadd_f .= ',' . $vs[0];
                $inadd_v .= " ,'" . ${$vs[0]} . "' ";
            }
        }
    }

    //处理图片文档的自定义属性
    if ($litpic != '' && !preg_match("#p#", $flag)) {
        $flag = ($flag == '' ? 'p' : $flag . ',p');
    }
    if ($redirecturl != '' && !preg_match("#j#", $flag)) {
        $flag = ($flag == '' ? 'j' : $flag . ',j');
    }

    //跳转网址的文档强制为动态
    if (preg_match("#j#", $flag)) $ishtml= $ismake = -1;
    $guanzhutag = isset($guanzhutag) ? join(',', $guanzhutag) : '';
    //保存到主表
    $query = "INSERT INTO `#@__archives`(id,typeid,typeid2,sortrank,flag,ismake,channel,arcrank,click,money,title,shorttitle,color,writer,source,litpic,pubdate,senddate,mid,voteid,notpost,description,keywords,filename,dutyadmin,weight,showpc,showios,showandroid,categoryid,clientpic,clientnote,tagid,joymearctypes)
    VALUES ('$arcID','$typeid','$typeid2','$sortrank','$flag','$ismake','$channelid','0','$click','$money',
    '$title','$shorttitle','$color','$writer','$source','$litpic','$pubdate','$senddate',
    '$adminid','$voteid','$notpost','$description','$keywords','$filename','$adminid','$weight','$showpc','$showios','$showandroid','$categoryid','$clientpicname','$clientnote','$guanzhutag','$joymearctypes');";

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
        $addfieldval .= ', \'' . $htlistimg . '\'';
        $addfieldkey .= ', wenzhangid';
        $addfieldval .= ', \'' . $wenzhangid . '\'';
    }
    $addfieldkey .= ', isvideo';
    $addfieldval .= ', \'' . $isvideo . '\'';
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
    //-----------存储地理位置开始-------------
    if ($address) {
        $mongo = new HMongodb();
        $mongo->selectDb("cms");
        $mongo->insert("cms_address", array("aid" => intval($arcID), "address" => $address, "location" => array(floatval($lng), floatval($lat))));
    }
    //-----------存储地理位置结束-------------

    //-----------存相关文章开始-------------

    //var_dump($relevancetitle,$relevanceurl,$relevancetype);exit;
    foreach ($relevancetitle as $k => $v) {
        if (empty($v) || empty($relevanceurl[$k]) || empty($relevancetype[$k])) continue;
        $relevancetype[$k] = intval($relevancetype[$k]);
        $query = "INSERT INTO `#@__addonrelevance`(aid,`title`,`url`,`type`,`status`) Values('$arcID','$v','$relevanceurl[$k]','$relevancetype[$k]',1)";
        if (!$dsql->ExecuteNoneQuery($query)) continue;
    }
    //保存文章路径
    if ($redirecturl) {
        $data = date('Y-m-d H:i:s', time()) . ' ' . $arcID . ' ' . $typeid . ' ' . codeurl($redirecturl) . ' ' . date('Y-m-d H:i:s', $pubdate) . "\n";
        saveArcUrl($arcID, $data);
    } else {
        saveArcUrl($arcID);
    }

    //百度推送
    $query = "SELECT typedir,typename,corank,namerule,namerule2,ispart,moresite,sitepath,siteurl,channeltemp FROM `#@__arctype` WHERE id='$typeid' ";
    $trow = $dsql->GetOne($query);
    $wikiservicearray = $baidupushurlarray = array(
        'aid' => $arcID,
        'typeid' => $typeid,
        'title' => $title,
        'ismake' => $ismake,
        'arcrank' => $arcrank,
        'namerule' => $trow['namerule'],
        'typedir' => $trow['typedir'],
        'money' => $money ? $money : 0,
        'filename' => $filename ? $filename : '',
        'moresite' => $trow['moresite'],
        'siteurl' => $trow['siteurl'],
        'sitepath' => $trow['sitepath'],
        'channeltemp' => $trow['channeltemp'],
    );
    $baidupushurlarray['senddate'] = $senddate;
    baiduPushUrl($baidupushurlarray);


    // 画报关注标签功能

    // $displaytag = $displaytag;
    if (!empty($guanzhutag) && ($typeid == 367 || $typeid == 368)) {
        $cmsurl = $apiUrl . "/joymeapp/gameclient/api/tagphp/updatearticle?archivesid=$arcID&tags=$guanzhutag&displaytag=";
        $res = joymeCurlGetFn($cmsurl);
        $rs = json_decode($res, true);
        if ($rs['rs'] == 0) {
            Log::info(__FILE__, $res, '画报标签');
        } else {
            Log::error(__FILE__, $res, '画报标签');
        }
    }

    // 文章游戏关联，是否为视频文章
    $data = array('archiveid' => $arcID, 'gameids' => $gameids, 'contenttype' => $isvideo);
    global $com;
    if ($gameids && $arcrank > -1) {
        if (($com == "dev" && ($typeid == "1938" || in_array("1938", $typeid2Arr))) ||
            ($com == "alpha" && ($typeid == "1938" || in_array("1938", $typeid2Arr))) ||
            ($com == "beta" && ($typeid == "1934" || in_array("1934", $typeid2Arr))) ||
            ($com == "com" && ($typeid == "2079" || in_array("2079", $typeid2Arr)))
        ) {
            $isvideo = 5;
        }
        //if ($isvideo < 5) {
            arcGames($data);
        //}
    } else if ($gameids && $arcrank == -1) {
        $arcgamesfilepath = dirname(__FILE__) . '/..' . $cfg_dataurl . '/admin/arcgamesfilepath.txt';
        if (file_exists($arcgamesfilepath)) {
            $arcgamesarr = file_get_contents($arcgamesfilepath);
            $arcgamesarr = unserialize($arcgamesarr);
        } else {
            $arcgamesarr = array();
        }
        $arcgamesarr[$arcID] = $data;
        file_put_contents($arcgamesfilepath, serialize($arcgamesarr));
    }

    //-----------存相关文章结束-------------
    // 处理着迷附加表信息开始
    $emshow = !empty($emshow) ? 1 : 0;
    $insql = "INSERT INTO `#@__joyme_arcaddtable` (`aid`,`emshow`) VALUES ('$arcID','$emshow'); ";
    $dsql->ExecuteNoneQuery($insql);
    // 处理着迷附加表信息结束
    //生成HTML
    InsertTags($tags, $arcID);
    if ($cfg_remote_site == 'Y' && $isremote == "1") {
        if ($serviterm != "") {
            list($servurl, $servuser, $servpwd) = explode(',', $serviterm);
            $config = array('hostname' => $servurl, 'username' => $servuser, 'password' => $servpwd, 'debug' => 'TRUE');
        } else {
            $config = array();
        }
        if (!$ftp->connect($config)) exit('Error:None FTP Connection!');
    }
    $picTitle = false;
    if (count($_SESSION['bigfile_info']) > 0) {
        foreach ($_SESSION['bigfile_info'] as $k => $v) {
            if (!empty($v)) {
                $pictitle = ${'picinfook' . $k};
                $titleSet = '';
                if (!empty($pictitle)) {
                    $picTitle = TRUE;
                    $titleSet = ",title='{$pictitle}'";
                }
                $dsql->ExecuteNoneQuery("UPDATE `#@__uploads` SET arcid='{$arcID}'{$titleSet} WHERE url LIKE '{$v}'; ");
            }
        }
    }
    $artUrl = MakeArt($arcID, true, true, $isremote);
    if ($artUrl == '') {
        $artUrl = $cfg_phpurl . "/view.php?aid=$arcID";
    } else {
        $artUrl = $artUrl;
    }
    ClearMyAddon($arcID, $title);

    $typeid2Arr = explode(',', $typeid2);
    // 文章游戏关联，是否为视频文章
    if ( $arcrank > -1) {
        if (($com == "dev" && ($typeid == "1938" || in_array("1938", $typeid2Arr))) ||
            ($com == "alpha" && ($typeid == "1938" || in_array("1938", $typeid2Arr))) ||
            ($com == "beta" && ($typeid == "1934" || in_array("1934", $typeid2Arr))) ||
            ($com == "com" && ($typeid == "2079" || in_array("2079", $typeid2Arr)))
        ) {
            $isvideo = 5;
        }

        if ($ishtml == 1 || $isvideo == 5) {
            //查询栏目信息
            $cids = $dsql->GetOne("select channelcids from `#@__arctype` where id='$typeid'");
            if (!empty($cids)) {
                $extra = array(
                    'typeid' => $typeid,
                    'typeid2' => $typeid2,
                    'sortrank' => $sortrank,
                    'flag' => $flag,
                    'click' => $click,
                    'ismake' => $ismake,
                    'arcrank' => $arcrank,
                    'money' => $money,
                    'title' => $title,
                    'color' => $color,
                    'writer' => $writer,
                    'source' => $source,
                    'litpic' => $litpic,
                    'pubdate' => $pubdate,
                    'notpost' => $notpost,
                    'description' => $description,
                    'keywords' => $keywords,
                    'shorttitle' => $shorttitle,
                    'filename' => $filename,
                    'dutyadmin' => $adminid,
                    'weight' => $weight,
                    'showpc' => $showpc,
                    'showios' => $showios,
                    'showandroid' => $showandroid,
                    'categoryid' => $categoryid,
                    'clientpic' => $clientpicname,
                    'clientnote' => $clientnote,
                    'tagid' => $guanzhutag,
                    'joymearctypes ' => $typeid2
                );
                $channeldata = array(
                    'aid' => $arcID,
                    'cid' => $cids['channelcids'],
                    'gid' => intval($gameids),
                    'atype' => $isvideo,
                    'source' => 1,
                    'pubdate' => $pubdate,
                    'url' => $artUrl,
                    'extra' => json_encode($extra)
                );

                arcToChannel($channeldata);
            }
        }
    }

    $wikiservicetype = 0;
    if ($com == "alpha") {
        if ($typeid == "1936") {
            $wikiservicetype = 1;
        } elseif ($typeid2) {
            if (in_array("1936", $typeid2Arr)) {
                $wikiservicetype = 2;
            }
        }
    } elseif ($com == "beta") {
        if ($typeid == "1933") {
            $wikiservicetype = 1;
        } elseif ($typeid2) {
            if (in_array("1933", $typeid2Arr)) {
                $wikiservicetype = 2;
            }
        }
    } elseif ($com == "com") {
        if ($typeid == "2072") {
            $wikiservicetype = 1;
        } elseif ($typeid2) {
            if (in_array("2072", $typeid2Arr)) {
                $wikiservicetype = 2;
            }
        }
    }
    if (!empty($wikiservicetype)) {
        if ($gameids) {
            if ($pubdate) {
                $publishtime = $pubdate * 1000;
            } else {
                $publishtime = time() * 1000;
            }
            if ($wikiservicetype == 1) {
                $artUrl = str_replace("pc", "wap", $artUrl);
            } elseif ($wikiservicetype == 2) {
                $artUrl = str_replace("pc", "wikiapp", $artUrl);
            }
            arcJavaContentPost(array(
                'archiveid' => $arcID,
                'title' => $title,
                'describe' => $description,
                'pic' => $litpic,
                'author' => $writer,
                'gameid' => $gameids,
                'weburl' => $artUrl,
                'publishtime' => $publishtime
            ));
        }
    }
    
    //如果tags有字符原创，提交到百度原创
    if($com == "com"&&strpos($tags,'原创')!==false){
        arcBaiduOriginalPost($artUrl,$title);
    }

    //返回成功信息
    $msg = "    　　请选择你的后续操作：
    <a href='article_add.php?cid=$typeid'><u>继续发布文章</u></a>
    &nbsp;&nbsp;
    <a href='$artUrl' target='_blank'><u>查看文章</u></a>
    &nbsp;&nbsp;
    <a href='archives_do.php?aid=" . $arcID . "&dopost=editArchives'><u>更改文章</u></a>
    &nbsp;&nbsp;
    <a href='catalog_do.php?cid=$typeid&dopost=listArchives'><u>已发布文章管理</u></a>
    &nbsp;&nbsp;
    $backurl
  ";
    $msg = "<div style=\"line-height:36px;height:36px\">{$msg}</div>" . GetUpdateTest('article_add', $joymearctypes);
    $wintitle = "成功发布文章！";
    $wecome_info = "文章管理::发布文章";
    $win = new OxWindow();
    $win->AddTitle("成功发布文章：");
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand", "&nbsp;", false);
    $win->Display();
}

?>