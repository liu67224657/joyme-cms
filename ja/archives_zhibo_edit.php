<?php
/**
 * 文档编辑
 *
 * @version        $Id: archives_edit.php 1 8:26 2010年7月12日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/config.php");
CheckPurview('a_Edit,a_AccEdit,a_MyEdit');
require_once(DEDEINC."/customfields.func.php");
require_once(DEDEADMIN."/inc/inc_archives_functions.php");

if(empty($dopost)) $dopost = '';

if($dopost!='save')
{
    require_once(DEDEADMIN.'/inc/orig_inc_catalog_options.php');
    require_once(DEDEINC."/dedetag.class.php");
    ClearMyAddon();
    $aid = intval($aid);

    //读取归档信息
    $arcQuery = "SELECT ch.typename as channelname,arc.*
    FROM `#@__archives` arc
    LEFT JOIN `#@__channeltype` ch ON ch.id=arc.channel WHERE arc.id=$aid";

    $arcRow = $dsql->GetOne($arcQuery);
    if(!is_array($arcRow))
    {
        ShowMsg("读取档案基本信息出错!","-1");
        exit();
    }
    // $arcaddQuery = "SELECT * FROM `dede_addonzhibo` WHERE aid='$aid'";
    // $arcaddRow = $dsql->GetOne($arcaddQuery);
    // if(!is_array($arcaddRow))
    // {
        // ShowMsg("读取档案基本信息出错!","-1");
        // exit();
    // }
    // $arcRow = array_merge($arcRow, $arcaddRow);
    $query = "SELECT * FROM `#@__channeltype` WHERE id='".$arcRow['channel']."'";
    $cInfos = $dsql->GetOne($query);
    if(!is_array($cInfos))
    {
        ShowMsg("读取频道配置信息出错!","javascript:;");
        exit();
    }
    // $addtable = $cInfos['addtable'];
    // $addRow = $dsql->GetOne("SELECT * FROM `$addtable` WHERE aid='$aid'");
    $channelid = $arcRow['channel'];
    $tags = GetTags($aid);
    $reportersql = "SELECT id,name FROM dede_reporter WHERE `status` = 1";
    $dsql->SetQuery($reportersql);
    $dsql->Execute();
    $reporters = array();
    while($row = $dsql->GetArray()){
        $reporters[] = $row;
    }
    include DedeInclude("templets/archives_zhibo_edit.htm");
    exit();
}
/*--------------------------------
function __save(){  }
-------------------------------*/
else if($dopost=='save')
{
    require_once(DEDEINC.'/image.func.php');
    require_once(DEDEINC.'/oxwindow.class.php');
    $notpost = isset($notpost) && $notpost == 1 ? 1: 0;

    if(!isset($autokey)) $autokey = 0;
    if(!isset($remote)) $remote = 0;
    if(!isset($dellink)) $dellink = 0;
    if(!isset($autolitpic)) $autolitpic = 0;
    if(!isset($writer)) $writer = '';
    $arcrank = 0;
    if($typeid==0)
    {
        ShowMsg("请指定文档的栏目！","-1");
        exit();
    }
    if(empty($channelid))
    {
        ShowMsg("文档为非指定的类型，请检查你发布内容的表单是否合法！","-1");
        exit();
    }
    if(!CheckChannel($typeid,$channelid))
    {
        ShowMsg("你所选择的栏目与当前模型不相符，请选择白色的选项！","-1");
        exit();
    }
    if(!TestPurview('a_Edit'))
    {
        if(TestPurview('a_AccEdit'))
        {
            CheckCatalog($typeid,"对不起，你没有操作栏目 {$typeid} 的文档权限！");
        }
        else
        {
            CheckArcAdmin($id,$cuserLogin->getUserID());
        }
    }

    //对保存的内容进行处理
    $pubdate = GetMkTime($pubdate);
	$senddate = GetMkTime($senddate);
	$tagid = isset($reporter) ? join(',',$reporter) : '';
	$shorttitle = preg_replace("#\"#", '＂', $address);
    $sortrank = 0;
    $ismake = 1;
    $title = cn_substrR($title, $cfg_title_maxlen);
    $source = '';
    $description = cn_substrR($description, $cfg_auot_description);
    $keywords = '';
    $keywords = trim(cn_substrR($keywords, 60));
    $isremote  = (empty($isremote)? 0  : $isremote);
    $serviterm=empty($serviterm)? "" : $serviterm;
    if(!TestPurview('a_Check,a_AccCheck,a_MyCheck')) $arcrank = -1;

    $adminid = $cuserLogin->getUserID();

    //处理上传的缩略图
    if(empty($ddisremote)) $ddisremote = 0;

    $litpic = GetDDImage('none', $picname, $ddisremote);

    //分析处理附加表数据
    $inadd_f = '';
    $inadd_v = '';
    if(!empty($dede_addonfields))
    {
        $addonfields = explode(';',$dede_addonfields);
        $inadd_f = '';
        $inadd_v = '';
        if(is_array($addonfields))
        {
            foreach($addonfields as $v)
            {
                if($v=='')
                {
                    continue;
                }
                $vs = explode(',',$v);
                if($vs[1]=='htmltext'||$vs[1]=='textdata') //HTML文本特殊处理
                {
                    ${$vs[0]} = AnalyseHtmlBody(${$vs[0]},$description,$litpic,$keywords,$vs[1]);
                }else
                {
                    if(!isset(${$vs[0]}))
                    {
                        ${$vs[0]} = '';
                    }
                    ${$vs[0]} = GetFieldValueA(${$vs[0]},$vs[1],$id);
                }
                $inadd_f .= ",`{$vs[0]}` = '".${$vs[0]}."'";
            }
        }
    }

    //更新数据库的SQL语句
    $inQuery = "UPDATE `#@__archives` SET
    typeid='$typeid',
    sortrank='$sortrank',
    notpost='$notpost',
    ismake='$ismake',
    tagid='$tagid',
    title='$title',
    source='$source',
    litpic='$litpic',
    description='$description',
    keywords='$keywords',
    dutyadmin='$adminid',
	shorttitle='$address',
	pubdate='$pubdate',
	senddate='$senddate',
	clientnote = '$clientnote',
	clientpic = '$clientpicname'
    WHERE id='$id'; ";
    if(!$dsql->ExecuteNoneQuery($inQuery))
    {
        ShowMsg("更新数据库archives表时出错，请检查！","-1");
        exit();
    }

    // $cts = $dsql->GetOne("SELECT addtable From `#@__channeltype` WHERE id='$channelid' ");
    // $addtable = trim($cts['addtable']);
    // if($addtable!='')
    // {
        // $useip = GetIP();
        // $iquery = "UPDATE `$addtable` SET typeid='$typeid'{$inadd_f},redirecturl='$redirecturl',userip='$useip',compere='$compere' WHERE aid='$id' ";
        // if(!$dsql->ExecuteNoneQuery($iquery))
        // {
            // ShowMsg("更新附加表 `$addtable`  时出错，请检查原因！","javascript:;");
            // exit();
        // }
    // }
//保存文章路径
    $redirecturl = '';
    if($redirecturl){
	$data = date('Y-m-d H:i:s', time()).' '.$id.' '.$typeid.' '.codeurl($redirecturl).' '.date('Y-m-d H:i:s', $pubdate)."\n";
	saveArcUrl($id, $data);
    }else{
	saveArcUrl($id);
    }
    //生成HTML
    UpIndexKey($id, $arcrank, $typeid, $sortrank, $tags);
    if($cfg_remote_site=='Y' && $isremote=="1")
    {    
        if($serviterm!="")
        {
            list($servurl, $servuser, $servpwd) = explode(',', $serviterm);
            $config=array( 'hostname' => $servurl, 'username' => $servuser, 'password' => $servpwd,'debug' => 'TRUE');
        } else {
            $config=array();
        }
        if(!$ftp->connect($config)) exit('Error:None FTP Connection!');
    }
    $artUrl = MakeArt($id, TRUE, TRUE, $isremote);
    if($artUrl=='')
    {
        $artUrl = $cfg_phpurl."/view.php?aid=$id";
    }
    ClearMyAddon($id, $title);
    ShowMsg("编辑成功！","content_zhibo_list.php?channelid=29");exit;
    //返回成功信息
    $msg = "
    　　请选择你的后续操作：
    <a href='archives_add.php?cid=$typeid'><u>发布新文档</u></a>
    &nbsp;&nbsp;
    <a href='archives_do.php?aid=".$id."&dopost=editArchives'><u>查看更改</u></a>
    &nbsp;&nbsp;
    <a href='$artUrl' target='_blank'><u>查看文档</u></a>
    &nbsp;&nbsp;
    <a href='catalog_do.php?cid=$typeid&dopost=listArchives'><u>管理文档</u></a>
    &nbsp;&nbsp;
    $backurl
    ";

    $wintitle = "成功更改文档！";
    $wecome_info = "文档管理::更改文档";
    $win = new OxWindow();
    $win->AddTitle("成功更改文档：");
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand","&nbsp;",false);
    $win->Display();
}