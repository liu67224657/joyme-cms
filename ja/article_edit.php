<?php
/**
 * 文档编辑
 *
 * @version        $Id: article_edit.php 1 14:12 2010年7月12日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__) . "/config.php");
CheckPurview('a_Edit,a_AccEdit,a_MyEdit');
require_once(DEDEINC . "/customfields.func.php");
require_once(DEDEADMIN . '/inc/FileUtil.php');
require_once(DEDEADMIN . "/inc/inc_archives_functions.php");
require_once(DEDEINC . '/mgdb.class.php');
if (file_exists(DEDEDATA . '/template.rand.php')) {
    require_once(DEDEDATA . '/template.rand.php');
}
use Joyme\core\Log;

if (empty($dopost)) $dopost = '';

$aid = isset($aid) && is_numeric($aid) ? $aid : 0;
//MakeArt($aid);exit;
if ($dopost != 'save') {
    require_once(DEDEADMIN . "/inc/inc_catalog_options.php");
    require_once(DEDEINC . "/dedetag.class.php");
    ClearMyAddon();

    //读取归档信息
    $query = "SELECT ch.typename AS channelname,ar.membername AS rankname,arc.*
    FROM `#@__archives` arc
    LEFT JOIN `#@__channeltype` ch ON ch.id=arc.channel
    LEFT JOIN `#@__arcrank` ar ON ar.rank=arc.arcrank WHERE arc.id='$aid' ";
    $arcRow = $dsql->GetOne($query);
    if (!is_array($arcRow)) {
        ShowMsg("读取档案基本信息出错!", "-1");
        exit();
    }
    $query = "SELECT * FROM `#@__channeltype` WHERE id='" . $arcRow['channel'] . "'";
    $cInfos = $dsql->GetOne($query);
    if (!is_array($cInfos)) {
        ShowMsg("读取频道配置信息出错!", "javascript:;");
        exit();
    }
    $addtable = $cInfos['addtable'];
    $addRow = $dsql->GetOne("SELECT * FROM `$addtable` WHERE aid='$aid'");
    if (!is_array($addRow)) {
        ShowMsg("读取附加信息出错!", "javascript:;");
        exit();
    }
    $channelid = $arcRow['channel'];
    $tags = GetTags($aid);

    //获取地理位置
    // $mongo = new HMongodb();
    // $mongo->selectDb("cms");
    // $address = $mongo->findOne('cms_address', array("aid"=>intval($aid)));

    //获取文章分类
    $dsql->Execute('category', 'Select * From `#@__category` where typeStatus=1');
    $category = '';
    while ($frow = $dsql->GetArray('category')) {
        $category .= ($frow['id'] == $arcRow['categoryid'] ? "<option value='{$frow['id']}' selected>{$frow['typeName']}</option>\r\n" : "<option value='{$frow['id']}'>{$frow['typeName']}</option>\r\n");
    }

    //获取相关文章
    $dsql->Execute('relevance', "Select * From `#@__addonrelevance` where aid='$aid' and `status`='1'");
    $relevance = array();
    while ($frow = $dsql->GetArray('relevance')) {
        $relevance[] = $frow;
    }

    $joymearctypesdata = GetTypeList($arcRow['typeid'], $cuserLogin->getUserChannel(), $channelid);
    $joymearctypes = json_encode($joymearctypesdata);
    $typeidSelectHtml = typeidSelectHtml($joymearctypesdata, $arcRow['joymearctypes']);
    $gamedata = array();
    if ($arcRow['arcrank'] < 0) {
        $arcgamesfilepath = dirname(__FILE__) . '/..' . $cfg_dataurl . '/admin/arcgamesfilepath.txt';
        if (file_exists($arcgamesfilepath)) {
            $arcgamesarr = file_get_contents($arcgamesfilepath);
            $arcgamesarr = unserialize($arcgamesarr);
            $gamedata = $arcgamesarr[$aid];
        }
    }
    // 处理着迷附加表信息开始
    $row = $dsql->GetOne("SELECT * FROM `#@__joyme_arcaddtable` WHERE aid='$aid'");
    // 处理着迷附加表信息结束
    include DedeInclude("templets/article_edit.htm");
    exit();
} /*--------------------------------
function __save(){  }
-------------------------------*/
else if ($dopost == 'save') {
    require_once(DEDEINC . '/image.func.php');
    require_once(DEDEINC . '/oxwindow.class.php');
    $flag = isset($flags) ? join(',', $flags) : '';
    $notpost = isset($notpost) && $notpost == 1 ? 1 : 0;

    if (empty($typeid2)) $typeid2 = 0;
    if (!isset($autokey)) $autokey = 0;
    if (!isset($remote)) $remote = 0;
    if (!isset($dellink)) $dellink = 0;
    if (!isset($autolitpic)) $autolitpic = 0;

    if (!isset($showpc)) $showpc = 0; else $showpc = 1;
    if (!isset($showios)) $showios = 0; else $showios = 1;
    if (!isset($showandroid)) $showandroid = 0; else $showandroid = 1;

    $categoryid = intval($categoryid);

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
    if (!TestPurview('a_Edit')) {
        if (TestPurview('a_AccEdit')) {
            CheckCatalog($typeid, "对不起，你没有操作栏目 {$typeid} 的文档权限！");
        } else {
            CheckArcAdmin($id, $cuserLogin->getUserID());
        }
    }

    $joymearctypes = $typeid2;
    //对保存的内容进行处理
    if (empty($pubdate)) {
        $pubdate = date('Y-m-d H:i:s', time());
    }
    $articlepubdate = $pubdate;
    $pubdate = GetMkTime($pubdate);
    $sortrank = AddDay($pubdate, $sortup);
    $ismake = $ishtml == 0 ? -1 : 0;
    $autokey = 1;
    $title = htmlspecialchars(cn_substrR($title, $cfg_title_maxlen));
    $shorttitle = cn_substrR($shorttitle, 36);
    $color = cn_substrR($color, 7);
    $writer = cn_substrR($writer, 20);
    $source = cn_substrR($source, 30);
    $description = cn_substrR($description, 250);

    $keywords = str_replace('，', ',', trim(cn_substrR($keywords, 60)));
    $keywords = str_replace('《', '', $keywords);
    $keywords = str_replace('》', '', $keywords);

    $filename = trim(cn_substrR($filename, 40));
    $isremote = (empty($isremote) ? 0 : $isremote);
    $serviterm = empty($serviterm) ? "" : $serviterm;
    if (!TestPurview('a_Check,a_AccCheck,a_MyCheck')) {
        $arcrank = -1;
    }
    $adminid = $cuserLogin->getUserID();

    //处理上传的缩略图
    if (empty($ddisremote)) {
        $ddisremote = 0;
    }
    $litpic = GetDDImage('none', $picname, $ddisremote);

    //分析body里的内容
    $body = AnalyseHtmlBody($body, $description, $litpic, $keywords, 'htmltext');
    // 添加wiki词条链接
    if ($wikiid) {
        $body = cmsWikiWords($body, explode(',', $wikiid));
    }

    //分析处理附加表数据
    $inadd_f = '';
    $inadd_v = '';
    if (!empty($dede_addonfields)) {
        $addonfields = explode(';', $dede_addonfields);
        $inadd_f = '';
        $inadd_v = '';
        if (is_array($addonfields)) {
            foreach ($addonfields as $v) {
                if ($v == '') {
                    continue;
                }
                $vs = explode(',', $v);
                if ($vs[1] == 'htmltext' || $vs[1] == 'textdata') //HTML文本特殊处理
                {
                    ${$vs[0]} = AnalyseHtmlBody(${$vs[0]}, $description, $litpic, $keywords, $vs[1]);
                } else {
                    if (!isset(${$vs[0]})) {
                        ${$vs[0]} = '';
                    }
                    ${$vs[0]} = GetFieldValueA(${$vs[0]}, $vs[1], $id);
                }
                $inadd_f .= ",`{$vs[0]}` = '" . ${$vs[0]} . "'";
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
    $guanzhutag = isset($guanzhutag) ? join(',', $guanzhutag) : '';
    //跳转网址的文档强制为动态
    if (preg_match("#j#", $flag)) $ishtml=$ismake = -1;
    $voteid = isset($voteid) ? $voteid : 0;
    $address = isset($address) ? $address : '';
//    $displaytag = $displaytag ? $displaytag : 0;
    //更新数据库的SQL语句,voteid='$voteid',
    $query = "UPDATE #@__archives SET
    typeid='$typeid',
    typeid2='$typeid2',
    sortrank='$sortrank',
    flag='$flag',
    click='$click',
    ismake='$ismake',
    arcrank='$arcrank',
    money='$money',
    title='$title',
    color='$color',
    writer='$writer',
    source='$source',
    litpic='$litpic',
    pubdate='$pubdate',
    notpost='$notpost',
    description='$description',
    keywords='$keywords',
    shorttitle='$shorttitle',
    filename='$filename',
    dutyadmin='$adminid',
    weight='$weight',
    showpc='$showpc',
    showios='$showios',
    showandroid='$showandroid',
    categoryid='$categoryid',
    clientpic='$clientpicname',
    clientnote='$clientnote',
    tagid='$guanzhutag',
    joymearctypes = '$typeid2'
    WHERE id='$id'; ";

    if (!$dsql->ExecuteNoneQuery($query)) {
        ShowMsg('更新数据库archives表时出错，请检查', -1);
        exit();
    }

    $cts = $dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id='$channelid' ");
    $addtable = trim($cts['addtable']);
    if ($addtable != '') {
        $useip = GetIP();
        $templet = empty($templet) ? '' : $templet;
        $addfield = '';
        if (in_array($addtable, array('dede_addonarticle', 'dede_addon17_lanmu'))) {
            $addfield .= ', htlistimg=\'' . $htlistimg . '\'';
            $addfield .= ', wenzhangid=\'' . $wenzhangid . '\'';
        }
        $addfield .= ', isvideo=\'' . $isvideo . '\'';
        $iquery = "UPDATE `$addtable` SET typeid='$typeid',body='$body'{$inadd_f},redirecturl='$redirecturl',templet='$templet'" . $addfield . ", userip='$useip' WHERE aid='$id'";
        if (!$dsql->ExecuteNoneQuery($iquery)) {
            ShowMsg("更新附加表 `$addtable`  时出错，请检查原因！", "javascript:;");
            exit();
        }
    }
    // 将 pubdate（发布时间） 更新到arctiny表
    $dsql->ExecuteNoneQuery("UPDATE `#@__arctiny` SET senddate='$pubdate' WHERE id='$id'");

    //-----------存储地理位置开始-------------
    // if($address){
    // $mongo = new HMongodb();
    // $mongo->selectDb("cms");
    // $mongo->insert("cms_address", array("aid"=>intval($id),"address"=>$address,"location"=>array(floatval($lng),floatval($lat))));
    // $mongo->update("cms_address", array("aid"=>intval($id)),array("aid"=>intval($id),"address"=>$address,"location"=>array(floatval($lng),floatval($lat))),array("upsert"=>1));
    // }
    //-----------存储地理位置结束-------------

    //-----------存相关文章开始-------------

    //var_dump($relevancetitle,$relevanceurl,$relevancetype);exit;
    $query = "update `#@__addonrelevance` set `status`='0' where aid=" . $id;
    $dsql->ExecuteNoneQuery($query);
    $relevancetitle = isset($relevancetitle) && is_array($relevancetitle) ? $relevancetitle : array();
    foreach ($relevancetitle as $k => $v) {
        if (empty($v) || empty($relevanceurl[$k]) || empty($relevancetype[$k])) continue;
        $relevancetype[$k] = intval($relevancetype[$k]);
        if (!empty($relevanceid[$k])) {
            $query = "update `#@__addonrelevance` set `title`='$v',`url`='$relevanceurl[$k]',`type`='$relevancetype[$k]',`status`='1' where id=" . intval($relevanceid[$k]);
        } else {
            $query = "INSERT INTO `#@__addonrelevance`(aid,`title`,`url`,`type`,`status`) Values('$id','$v','$relevanceurl[$k]','$relevancetype[$k]',1)";
        }
        if (!$dsql->ExecuteNoneQuery($query)) continue;
    }
    //保存文章路径
    if ($redirecturl) {
        $data = date('Y-m-d H:i:s', time()) . ' ' . $id . ' ' . $typeid . ' ' . codeurl($redirecturl) . ' ' . date('Y-m-d H:i:s', $pubdate) . "\n";
        saveArcUrl($id, $data);
    } else {
        saveArcUrl($id);
    }

    // 画报关注标签功能

    // $displaytag = $displaytag;
    $com = substr($_SERVER['HTTP_HOST'], strrpos($_SERVER['HTTP_HOST'], '.') + 1);
    if (!empty($guanzhutag) && ($typeid == 367 || $typeid == 368)) {
        $cmsurl = $apiUrl . "/joymeapp/gameclient/api/tagphp/updatearticle?archivesid=$id&tags=$guanzhutag&displaytag=";
        $res = joymeCurlGetFn($cmsurl);
        $rs = json_decode($res, true);
        if ($rs['rs'] == 0) {
            Log::info(__FILE__, $cmsurl . '__' . $res, '画报标签');
        } else {
            Log::error(__FILE__, $cmsurl . '__' . $res, '画报标签');
        }
    }

    // 文章游戏关联，是否为视频文章
    $data = array('archiveid' => $id, 'gameids' => $gameids, 'contenttype' => $isvideo);
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
        $arcgamesarr[$id] = $data;
        file_put_contents($arcgamesfilepath, serialize($arcgamesarr));
    }

    //-----------存相关文章结束-------------
    // 处理着迷附加表信息开始
    $emshow = !empty($emshow) ? 1 : 0;
    $row = $dsql->GetOne("SELECT * FROM `#@__joyme_arcaddtable` WHERE aid='$id'");
    if ($row['id']) {
        $upsql = "UPDATE `#@__joyme_arcaddtable` SET `emshow` = '$emshow'  WHERE aid='$id'";
        $dsql->ExecuteNoneQuery($upsql);
    } else {
        $insql = "INSERT INTO `#@__joyme_arcaddtable` (`aid`,`emshow`) VALUES ('$id','$emshow'); ";
        $dsql->ExecuteNoneQuery($insql);
    }
    // 处理着迷附加表信息结束
    //生成HTML
    UpIndexKey($id, $arcrank, $typeid, $sortrank, $tags);
    if ($cfg_remote_site == 'Y' && $isremote == "1") {
        if ($serviterm != "") {
            list($servurl, $servuser, $servpwd) = explode(',', $serviterm);
            $config = array('hostname' => $servurl, 'username' => $servuser,
                'password' => $servpwd, 'debug' => 'TRUE');
        } else {
            $config = array();
        }
        if (!$ftp->connect($config)) exit('Error:None FTP Connection!');
    }
    $artUrl = MakeArt($id, true, true, $isremote);
    if ($artUrl == '') {
        $artUrl = $cfg_phpurl . "/view.php?aid=$id";
    } else {
        $artUrl = $artUrl;
    }
    ClearMyAddon($id, $title);

    $typeid2Arr = explode(',', $typeid2);
    // 文章游戏关联，是否为视频文章
    $data = array('archiveid' => $id, 'gameids' => $gameids, 'contenttype' => $isvideo);

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
                    'aid' => $id,
                    'cid' => $cids['channelcids'],
                    'gid' => intval($gameids),
                    'atype' => $isvideo,
                    'source' => 1,
                    'pubdate' => $pubdate,
                    'url' => $artUrl,
                    'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE)
                );

                arcToChannel($channeldata);
            }
        }
    }

    global $domain;
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
                'archiveid' => $id,
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


    //返回成功信息
    $msg = "
    　　请选择你的后续操作：
    <a href='article_add.php?cid=$typeid'><u>发布新文章</u></a>
    &nbsp;&nbsp;
    <a href='archives_do.php?aid=" . $id . "&dopost=editArchives'><u>查看更改</u></a>
    &nbsp;&nbsp;
    <a href='$artUrl' target='_blank'><u>查看文章</u></a>
    &nbsp;&nbsp;
    <a href='catalog_do.php?cid=$typeid&dopost=listArchives'><u>管理文章</u></a>
    &nbsp;&nbsp;
    $backurl
    ";
    $msg = "<div style=\"line-height:36px;height:36px\">{$msg}</div>" . GetUpdateTest('article_edit', $joymearctypes);

    $wintitle = "成功更改文章！";
    $wecome_info = "文章管理::更改文章";
    $win = new OxWindow();
    $win->AddTitle("成功更改文章：");
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand", "&nbsp;", false);
    $win->Display();
}