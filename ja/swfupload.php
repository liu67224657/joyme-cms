<?php
/**
 * swfupload上传
 *
 * @version        $Id: swfupload.php 1 16:22 2010年7月20日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
$uploadimg = true;
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/image.func.php');
/************************
//上传
function Upload(){  }
*************************/
if(empty($dopost))
{
    ini_set('html_errors', '0');
    if ( empty($Filedata) || !is_uploaded_file($Filedata) )
    {
        echo 'ERROR: Upload Error! ';
        exit(0);
    }

    //把文件移动到临时目录
    $tmpdir = DEDEDATA.'/uploadtmp';
    if(!is_dir($tmpdir))
    {
        MkdirAll($tmpdir, $cfg_dir_purview);
        CloseFtp();
        if(!is_dir($tmpdir))
        {
            echo "ERROR: Create {$tmpdir} dir Error! ";
            exit(0);
        }
    }
    
    $FiledataNew = str_replace("\\", '/', $Filedata);
    $FiledataNew = $tmpdir.'/'.preg_replace("/(.*)[\/]/isU", "", $FiledataNew);
    move_uploaded_file($Filedata, $FiledataNew);

    $info = $ftype = $sname = '';
    $srcInfo = GetImageSize($FiledataNew, $info);
    //检测文件类型
    if (!is_array($srcInfo))
    {
        @unlink($Filedata);
        echo "ERROR: Image info Error! ";
        exit(0);
    }
    else
    {
        switch ($srcInfo[2])
        {
            case 1:
                $ftype = 'image/gif';
                $sname = '.gif';
                break;
            case 2:
                $ftype = 'image/jpeg';
                $sname = '.jpg';
                break;
            case 3:
                $ftype = 'image/png';
                $sname = '.png';
                break;
            case 6:
                $ftype = 'image/bmp';
                $sname = '.bmp';
                break;
        }
    }
    if($ftype=='')
    {
        @unlink($Filedata);
        echo "ERROR: Image type Error! ";
        exit(0);
    }
    
    //保存原图
    $filedir = $cfg_image_dir.'/'.MyDate($cfg_addon_savetype, time());
//    if(!is_dir(DEDEROOT.$filedir))
//    {
//        MkdirAll($cfg_basedir.$filedir, $cfg_dir_purview);
//        CloseFtp();
//    }
    $filename = $cuserLogin->getUserID().'-'.dd2char(MyDate('ymdHis', time()));
//    if( file_exists($cfg_basedir.$filedir.'/'.$filename.$sname) )
//    {
//        for($i=50; $i <= 5000; $i++)
//        {
//            if( !file_exists($cfg_basedir.$filedir.'/'.$filename.'-'.$i.$sname) )
//            {
//                $filename = $filename.'-'.$i;
//                break;
//            }
//        }
//    }
    $fileurl = $filedir.'/'.$filename.$sname;// 例：/uploads/allimg/151207/96-15120G53F2.jpg-》http://joymepic.joyme.com/article/
    $imgurl = uploadImgToQiniu($FiledataNew, 'article'.$fileurl);
//    $rs = copy($FiledataNew, $cfg_basedir.$fileurl);
    if(!$imgurl)
    {
        echo "ERROR: Copy Uploadfile Error! ";
        exit(0);
    }
    // 照片添加水印
//    WaterImg($cfg_basedir.$fileurl, 'up');
    $title = $filename.$sname;

    $inquery = "INSERT INTO `#@__uploads`(title,url,mediatype,width,height,playtime,filesize,uptime,mid)
        VALUES ('$title','$imgurl','$ftype','0','0','0','".filesize($FiledataNew)."','".time()."','".$cuserLogin->getUserID()."'); ";
	if(!empty($arcid))
	{
		$inquery = "INSERT INTO `#@__uploads`(arcid,title,url,mediatype,width,height,playtime,filesize,uptime,mid)
        VALUES ('$arcid','$title','$imgurl','$ftype','0','0','0','".filesize($FiledataNew)."','".time()."','".$cuserLogin->getUserID()."'); ";
	}
	
	$dsql->ExecuteNoneQuery($inquery);
    $fid = $dsql->GetLastID();
//    AddMyAddon($fid, $fileurl);

    //生成缩略图
    $width = imageSX($FiledataNew);
    $height = imageSY($FiledataNew);
    if($width>=$height){
        $width = $cfg_ddimg_width;
        $height = $width/($cfg_ddimg_width/$cfg_ddimg_height);
    }else{
        $height = $cfg_ddimg_width;
        $width = $height/($cfg_ddimg_height/$cfg_ddimg_width);
    }
    $imagevariable = $imgurl.'?imageView2/1/w/'.$width.'/h/'.$height;
//$width/$height = $cfg_ddimg_width/$cfg_ddimg_height
    unlink($FiledataNew);
//    ob_start();
//    ImageResizeNew($cfg_basedir.$fileurl, $cfg_ddimg_width, $cfg_ddimg_height, '', false);
//    $imagevariable = ob_get_contents();
//    ob_end_clean();
    
    //保存信息到 session
    if (!isset($_SESSION['file_info'])) $_SESSION['file_info'] = array();
    if (!isset($_SESSION['bigfile_info'])) $_SESSION['bigfile_info'] = array();
    if (!isset($_SESSION['fileid'])) $_SESSION['fileid'] = 1;
    else $_SESSION['fileid']++;
    
    $_SESSION['bigfile_info'][$_SESSION['fileid']] = $imgurl;
    $_SESSION['file_info'][$_SESSION['fileid']] = $imagevariable;
    echo "FILEID:".$_SESSION['fileid'];
    exit(0);
}
/************************
//生成缩图
function GetThumbnail(){  }
*************************/
else if($dopost=='thumbnail')
{
    if( empty($id) )
    {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'No ID';
        exit(0);
    }
    if (!is_array($_SESSION['file_info']) || !isset($_SESSION['file_info'][$id]))
    {
        header('HTTP/1.1 404 Not found');
        exit(0);
    }
    $img = file_get_contents($_SESSION['file_info'][$id]);
    header("Content-type:image/jpeg");
    header('Content-Length: '.$img);
    echo $img;
    exit(0);
}
/************************
//删除指定ID的图片
*************************/
else if($dopost=='del')
{
    if(!isset($_SESSION['bigfile_info'][$id]))
    {
        echo '';
        exit();
    }
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__uploads` WHERE url LIKE '{$_SESSION['bigfile_info'][$id]}'; ");
//    @unlink($cfg_basedir.$_SESSION['bigfile_info'][$id]);
    $res = parse_url($_SESSION['bigfile_info'][$id]);
    $path = ltrim($res['path'], '/');
    delImage($path);
    $_SESSION['file_info'][$id] = '';
    $_SESSION['bigfile_info'][$id] = '';
    echo "<b>已删除！</b>";
    exit();
}
/************************
//获取图片地址
*************************/
else if($dopost=='addtoedit')
{
    if(!isset($_SESSION['bigfile_info'][$id]))
    {
        echo '';
        exit();
    }
	echo $_SESSION['bigfile_info'][$id];
	exit();
}
/************************
//获取本地图片的缩略预览图
function GetddImg(){  }
*************************/
else if($dopost=='ddimg')
{
    //生成缩略图
//    ob_start();
//    if(!preg_match("/^(http:\/\/)?([^\/]+)/i", $img)) $img = $cfg_basedir.$img;
//    ImageResizeNew($img, $cfg_ddimg_width, $cfg_ddimg_height, '', false);
//    $imagevariable = ob_get_contents();
//    ob_end_clean();
//    echo $img.'?'.rand(100,999);exit;
    $imagevariable = file_get_contents($img.'?'.rand(1000,9999));
    header('Content-type: image/jpeg');
    header('Content-Length: '.strlen($imagevariable));
    echo $imagevariable;
    exit();
}
/************************
//删除指定的图片(编辑图集时用)
*************************/
else if($dopost=='delold')
{
    $res = parse_url($picfile);
    $path = ltrim($res['path'], '/');
    delImage($path);
//    if(!file_exists($imgfile) && !is_dir($imgfile) && preg_match("#^".$cfg_medias_dir."#", $imgfile))
//    {
//        @unlink($imgfile);
//    }
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__uploads` WHERE url LIKE '{$picfile}'; ");
    echo "<b>已删除！</b>";
    exit();
}