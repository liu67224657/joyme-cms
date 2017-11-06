<?php
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/datalistcp.class.php');
require_once(DEDEINC.'/common.func.php');

//检查权限
CheckPurview('a_List,a_AccList,a_MyList');

if(!isset($typeid)) $typeid = 0;
if(!isset($typeid2)) $typeid2 = 0;

if(!isset($wid)) $wid = 0;
if(!isset($arcrank)) $arcrank = -1;

$ENV_GOBACK_URL = empty($_COOKIE["ENV_GOBACK_URL2"]) ? "archives_wiki_list.php" : $_COOKIE["ENV_GOBACK_URL2"];

if(empty($wid) || empty($typeid) ){
	ShowMsg('对不起，你没指定运行参数！',$ENV_GOBACK_URL);
	exit();
}
$oknum = 0;
$where = array(' 1=1 ');
$where[] = ' aid>0 ';
$where[] = " id in(".implode(',',$wid).") ";
$sql = 'select count(*) as zz from joyme_archives_wiki where '.join(' AND ',$where);
$dsql->SetQuery($sql);//将SQL查询语句格式化
$dsql->Execute();//执行SQL操作
$zz = $dsql->GetArray();
if($zz['zz'] > 0){
	ShowMsg('对不起，你选中的条目有被推荐了！',$ENV_GOBACK_URL);
	exit();
}

foreach($wid as $k=>$id){
	//$sortrank = $pubdate = empty($time[$k])?time():$time[$k];
	$sortrank = $pubdate = $senddate = time();
	
	
	if(trim($titles[$k]) == '')
	{
		ShowMsg('标题不能为空', $ENV_GOBACK_URL);
		exit();
	}

	$row = $dsql->GetOne("SELECT ispart,channeltype,typename FROM `#@__arctype` WHERE id='$typeid' ");
	$channelid = $row['channeltype'];
	$channle_name = $row['typename'];
	
	if(!empty($typeid2)){
		$sql = "SELECT ispart,channeltype,typename FROM `#@__arctype` WHERE id in({$typeid2}) ";
		$dsql->SetQuery($sql);//将SQL查询语句格式化
		$dsql->Execute();//执行SQL操作
		while($row = $dsql->GetArray()){
			$channle_name .= ','.$row['typename'];
		}
	}
	
	
	
	$adminid = $cuserLogin->getUserID();
	//生成文档ID
    $arcID = GetIndexKey(0,$typeid,$sortrank,$channelid,$senddate,$adminid);
    if(empty($arcID))
    {
        ShowMsg("无法获得主键，因此无法进行后续操作！","-1");
        exit();
    }
    
    //分析处理附加表数据
    $inadd_f = $inadd_v = '';
    if(!empty($dede_addonfields))
    {
    	$addonfields = explode(';',$dede_addonfields);
    	if(is_array($addonfields))
    	{
    		foreach($addonfields as $v)
    		{
    			if($v=='') continue;
    			$vs = explode(',',$v);
    			if($vs[1]=='htmltext'||$vs[1]=='textdata')
    			{
    				${$vs[0]} = AnalyseHtmlBody(${$vs[0]},$description,$litpic,$keywords,$vs[1]);
    			}
    			else
    			{
    				if(!isset(${$vs[0]})) ${$vs[0]} = '';
    				${$vs[0]} = GetFieldValueA(${$vs[0]},$vs[1],$arcID);
    			}
    			$inadd_f .= ','.$vs[0];
    			$inadd_v .= " ,'".${$vs[0]}."' ";
    		}
    	}
    }
    
    //保存到主表
    
    $title = preg_replace("#\"#", '＂', $titles[$k]);
    $title = htmlspecialchars(cn_substrR($title,$cfg_title_maxlen));
    $shorttitle = cn_substrR($shorttitle,36);
    $writer = empty($user[$k])?'未知':$user[$k];
    $source = '着迷WIKI';
    $litpic = empty($picname[$k])?'':$picname[$k];
    $description = empty($des[$k])?'':$des[$k];
    $description = cn_substrR($description,$cfg_auot_description);
    $redirecturl = empty($url[$k])?'':$url[$k];
    
    $flag = ($litpic=='' ? 'j' : 'p,j');
    
    $query = "INSERT INTO `#@__archives`(id,typeid,typeid2,sortrank,flag,ismake,channel,arcrank,click,money,title,shorttitle,color,writer,source,litpic,pubdate,senddate,mid,voteid,notpost,description,keywords,filename,dutyadmin,weight,showpc,showios,showandroid,categoryid,clientpic,clientnote,tagid,joymearctypes)
    VALUES ('$arcID','$typeid','$typeid2','$sortrank','$flag','-1','$channelid','$arcrank','0','0',
    '$title','$shorttitle','','$writer','$source','$litpic','$pubdate','$senddate',
    '$adminid','0','1','$description','','','$adminid','$arcID','0','0','0','0','','','','$typeid2');";
    
    if(!$dsql->ExecuteNoneQuery($query))
    {
    	$gerr = $dsql->GetError();
    	$dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
    	ShowMsg("把数据保存到数据库主表 `#@__archives` 时出错，请把相关信息提交给DedeCms官方。".str_replace('"','',$gerr),"javascript:;");
    	exit();
    }
    
    //保存到附加表
    $cts = $dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id='$channelid' ");
    $addtable = trim($cts['addtable']);
    if(empty($addtable))
    {
    	$dsql->ExecuteNoneQuery("DELETE FROM `#@__archives` WHERE id='$arcID'");
    	$dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
    	ShowMsg("没找到当前模型[{$channelid}]的主表信息，无法完成操作！。","javascript:;");
    	exit();
    }
    $useip = GetIP();
    $templet = empty($templet) ? '' : $templet;
    $addfieldkey = '';
    $addfieldval = '';
    if(in_array($addtable, array('dede_addonarticle', 'dede_addon17_lanmu'))){
    	$addfieldkey .= ', htlistimg';
    	$addfieldval .= ', \'\'';
    	$addfieldkey .= ', wenzhangid';
    	$addfieldval .= ', \'\'';
    }
    $addfieldkey .= ', isvideo';
    $addfieldval .= ', \'0\'';
    $body = '';
    $query = "INSERT INTO `{$addtable}`(aid,typeid,redirecturl,templet,userip,body{$inadd_f} ".$addfieldkey.") Values('$arcID','$typeid','$redirecturl','$templet','$useip','$body'{$inadd_v} ".$addfieldval.")";
    if(!$dsql->ExecuteNoneQuery($query))
    {
    	$gerr = $dsql->GetError();
    	$dsql->ExecuteNoneQuery("Delete From `#@__archives` where id='$arcID'");
    	$dsql->ExecuteNoneQuery("Delete From `#@__arctiny` where id='$arcID'");
    	ShowMsg("把数据保存到数据库附加表 `{$addtable}` 时出错，请把相关信息提交给DedeCms官方。".str_replace('"','',$gerr),"javascript:;");
    	exit();
    }
    // 将 pubdate（发布时间） 更新到arctiny表
    $dsql->ExecuteNoneQuery("UPDATE `#@__arctiny` SET senddate='$pubdate' WHERE id='$arcID'");
    // 更新joyme_archives_wiki表
    $dsql->ExecuteNoneQuery("UPDATE `joyme_archives_wiki` SET rec_time='$pubdate',aid='$arcID',channle_name='{$channle_name}' WHERE id='$id'");
    $oknum++;
}

ShowMsg('成功导入了'.$oknum.'条跳转链接！',$ENV_GOBACK_URL);


?>