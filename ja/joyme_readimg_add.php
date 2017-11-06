<?php
/**
 * 阅读心情组添加
 *
 * @version        $Id: sys_admin_user_add.php 1 16:22 2010年7月20日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/config.php");
// CheckPurview('sys_User');
// require_once(DEDEINC."/typelink.class.php");
if(empty($dopost)) $dopost='';

if($dopost=='add')
{
    if($status == 1){
        $query = "UPDATE `#@__joyme_readimg` SET status=0 WHERE status = 1";
        $dsql->ExecuteNoneQuery($query);
    }
    $img1 = uploadImg($_FILES['image1']);
    $img2 = uploadImg($_FILES['image2']);
    $img3 = uploadImg($_FILES['image3']);
    $img4 = uploadImg($_FILES['image4']);
	$adminid = $cuserLogin->getUserID();
    $addsql = "INSERT INTO `#@__joyme_readimg` (`title`,`status`,`mid`,`img1`,`img2`,`img3`,`img4`)
               VALUES ('$title','$status','$adminid','$img1','$img2','$img3','$img4'); ";
    $dsql->ExecuteNoneQuery($addsql);

    $id = $dsql->GetLastID();
    if($id <= 0 )
    {
        die($dsql->GetError().' 数据库出错！');
    }
	ShowMsg('成功增加一组心情图片！', 'joyme_readimg.php');
    exit();
}
include DedeInclude('templets/joyme_readimg_add.htm');