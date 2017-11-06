<?php
/**
 * 阅读心情组编辑
 *
 * @version        $Id: joyme_readimg_edit.php 1 16:22 2010年7月20日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__).'/config.php');
if(empty($dopost)) $dopost = '';
$id = preg_replace("#[^0-9]#", '', $id);

if($dopost=='saveedit')
{
    $setimgsql = '';
    $setimgsql .= $_FILES['image1']['tmp_name'] != '' ? ', img1 = \''.uploadImg($_FILES['image1']).'\'' : '';
    $setimgsql .= $_FILES['image2']['tmp_name'] != '' ? ', img2 = \''.uploadImg($_FILES['image2']).'\'' : '';
    $setimgsql .= $_FILES['image3']['tmp_name'] != '' ? ', img3 = \''.uploadImg($_FILES['image3']).'\'' : '';
    $setimgsql .= $_FILES['image4']['tmp_name'] != '' ? ', img4 = \''.uploadImg($_FILES['image4']).'\'' : '';
       
    if($status == 1){
            $query = "UPDATE `#@__joyme_readimg` SET status=0 WHERE status = 1";
            $dsql->ExecuteNoneQuery($query);
    }
    $adminid = $cuserLogin->getUserID();
    $query = "UPDATE `#@__joyme_readimg` SET title='$title',status='$status',mid='$adminid' ".$setimgsql." WHERE id='$id'";
    $dsql->ExecuteNoneQuery($query);
    ShowMsg("成功一组心情！", "joyme_readimg.php");
    exit();
}

// 展示编辑页面
$row = $dsql->GetOne("SELECT * FROM `#@__joyme_readimg` WHERE id='$id'");
include DedeInclude('templets/joyme_readimg_edit.htm');





