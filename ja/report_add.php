<?php
/**
 * 广告添加
 *
 * @version        $Id: ad_add.php 1 8:26 2010年7月12日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
 
require(dirname(__FILE__)."/config.php");
//检查权限许可，总权限
CheckPurview('a_List,a_AccList,a_MyList');
require_once DEDEINC."/typelink.class.php";

if(empty($dopost)) $dopost = "";


if($dopost=="save")
{
	
	if(empty($name) || empty($type)){
		ShowMsg("名称和类型都不可以为空", "-1");
		exit();
	}
	$status = 1;
	
	$imgfile = $_FILES["file"]['tmp_name'];
	if(!is_uploaded_file($imgfile))
    {
    	ShowMsg("你没有选择上传的文件!", "-1");
    	exit();
    }
    $imgfile_name = $_FILES["file"]["name"];
    $ext = strtolower(substr($imgfile_name,strrpos($imgfile_name, '.'))); 
    
    if($ext !='.jpg' && $ext != '.png' && $ext != '.gif')
    {
    	ShowMsg("你所上传的图片类型只能是jpg、gif或者png哦", "-1");
    	exit();
    }

    $row = $dsql->GetOne("SELECT id FROM #@__reporter WHERE name = '$name'");
    if(is_array($row))
    {
        ShowMsg("在同名的主持人存在！","-1");
        exit();
    }
    
    $headicon = uploadImgToQiniu($imgfile,'article/reporter/'.time().$ext);
    
    $query = "
     INSERT INTO #@__reporter(name,type,headicon,status,introduce)
     VALUES('$name','$type','$headicon','$status','$introduce');
    ";
    $dsql->ExecuteNoneQuery($query);
    ShowMsg("成功增加一个主持人！","report.php");
    exit();
}
$row = array('id'=>'','name'=>'','type'=>'','headicon'=>'','status'=>'1');
$action = 'add';

include DedeInclude('templets/report_edit.htm');