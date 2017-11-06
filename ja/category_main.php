<?php
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/datalistcp.class.php');
require_once(DEDEINC.'/common.func.php');
setcookie('ENV_GOBACK_URL',$dedeNowurl,time()+3600,'/');

$sql = "Select * From `#@__category`";

$dlist = new DataListCP('', true);
$dlist->SetTemplet(DEDEADMIN."/templets/category_main.htm");
$dlist->SetSource($sql);
$dlist->display();
?>