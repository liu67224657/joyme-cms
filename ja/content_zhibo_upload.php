<?php
/**
 * 文件上传
 */
require_once(dirname(__FILE__).'/config.php');
if($_POST['image'] == 'true'){
	$errmsg = '';
	$allowtype = array('image/jpeg','image/png','image/jpg','image/gif');
	$data = $_FILES['upimg'];
	if($data['tmp_name'] == ''){
		$errmsg = '没有文件上传';
		reerror($errmsg);exit;
	}
	if(!in_array($data['type'], $allowtype)){
		$errmsg = '文件不是图片类型文件,type:'.$data['type'];
		reerror($errmsg);exit;
	}
	if($data['error'] != 0){
		$errmsg = '文件上传出错,error：'.$data['error'];
		reerror($errmsg);exit;
	}
	if($data['size'] > 4*1024*1024){
		$errmsg = '文件大小超出4M,size：'.$data['size'];
		reerror($errmsg);exit;
	}
	$savePath = '/article/images/'.date('Ym', time()).'/'.date('d', time()).time().'.'.str_replace('image/', '', $data['type']);
	$imgurl = uploadImgToQiniu($data['tmp_name'], $savePath);
	remsg($imgurl);
}

function remsg($url){
	echo '<script type="text/javascript">window.parent.imguploaded("'.$url.'")</script>';
}

function reerror($error){
	echo '<script type="text/javascript">window.parent.uploaderror("'.$error.'")</script>';
}