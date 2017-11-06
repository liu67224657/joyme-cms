<?php
/**
 *
 * 编辑模块
 * 
 */
require_once(dirname(__FILE__)."/../include/common.inc.php");
$aid = (isset($aid) && is_numeric($aid)) ? $aid : 0;
if($aid==0) die(' Request Error! ');

$revalue = '';
$sql = 'SELECT emshow FROM dede_joyme_arcaddtable WHERE aid = '.$aid;
$row = $dsql->getOne($sql);
if(!$row || $row['emshow'] == 0){
	$rs = array('rs'=>0, 'show'=>0);// 不展示
}else{
	$rs = array('rs'=>0, 'show'=>1);// 展示
}
echo $_GET['callback'].'('.json_encode($rs).')';
exit;

if(isset($tp) && $tp == 'm'){
	if(!is_dir(DEDEDATA.'/cache/writer')){MkdirAll(DEDEDATA.'/cache/writer');}
	$cacheFile = DEDEDATA.'/cache/writer/arcwriter-m-'.$mid.'.htm';
	if( isset($nocache) || !file_exists($cacheFile) || time() - filemtime($cacheFile) > $cfg_puccache_time ){
		$revalue .= '<div class="tj-wiki">';
		$arcsql = 'SELECT `mid`,writer FROM dede_archives WHERE id = '.$aid;
		$arc = $dsql->getOne($arcsql);

		$writersql = 'SELECT avatar, intro FROM dede_admin WHERE id = '.$arc['mid'];
		$writer = $dsql->getOne($writersql);
		$revalue .= '<cite><img src="'.$writer['avatar'].'" width="100%"></cite>';
		$revalue .= '<div class="wiki-text"><font>'.$arc['writer'].'</font><p>'.$writer['intro'].'</p></div></div>';

		$orthersql = 'SELECT id, title FROM dede_archives WHERE id != '.$aid.' `mid` = '.$arc['mid'].' ORDER BY id DESC limit 4';
		$dsql->Execute('me', $orthersql);
		$revalue .= '<div class="tj-list"><h3 class="tj-tit">该编辑的其他文章</h3><div>';
		while($rs = $dsql->GetArray()){
			$revalue .= '<a href="'.GetOneDocUrl($rs['id']).'">'.$rs['title'].'</a>';
		}
		$revalue .= '</div></div>';
		$fp = fopen($cacheFile, 'w');
		fwrite($fp, $revalue);
		fclose($fp);
	}
	$str = file_get_contents($cacheFile);
	$rs = array('rs'=>0, 'html'=>$str);
	echo $_GET['callback'].'('.json_encode($rs).')';
	exit;
}


if(!is_dir(DEDEDATA.'/cache/writer')){MkdirAll(DEDEDATA.'/cache/writer');}
$cacheFile = DEDEDATA.'/cache/writer/arcwriter-'.$mid.'.htm';
if( isset($nocache) || !file_exists($cacheFile) || time() - filemtime($cacheFile) > $cfg_puccache_time ){
	$revalue .= '<dl class="fn-clear">';
	$arcsql = 'SELECT `mid`,writer FROM dede_archives WHERE id = '.$aid;
	$arc = $dsql->getOne($arcsql);

	$writersql = 'SELECT avatar, intro FROM dede_admin WHERE id = '.$arc['mid'];
	$writer = $dsql->getOne($writersql);
	$revalue .= '<dt><img src="'.$writer['avatar'].'" alt=""></dt>';
	$revalue .= '<dd><h6>'.$arc['writer'].'</h6><p>'.$writer['intro'].'</p></dd></dl><div><h5>该编辑的其他文章：</h5>';

	$orthersql = 'SELECT id, title FROM dede_archives WHERE `mid` = '.$arc['mid'].' ORDER BY id DESC limit 3';
	$dsql->Execute('me', $orthersql);
	while($rs = $dsql->GetArray()){
		$revalue .= '<a href="'.GetOneDocUrl($rs['id']).'">'.$rs['title'].'</a>';
	}
	$revalue .= '</div>';
	$fp = fopen($cacheFile, 'w');
    fwrite($fp, $revalue);
    fclose($fp);
}

$str = file_get_contents($cacheFile);
$rs = array('rs'=>0, 'html'=>$str);
echo $_GET['callback'].'('.json_encode($rs).')';