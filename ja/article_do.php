<?php
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/mgdb.class.php');
if(empty($dopost))
{
	ShowMsg("对不起，请指定栏目参数！","article_do.php");
	exit();
}
/*--------------------------
//游戏库搜索
---------------------------*/
if($dopost=="joymegame")
{
	$mongo = new HMongodb();   
	$mongo->selectDb("game");   
	$gamename = new MongoRegex("/".$name."/i");
	$data = array('song_list'=>$mongo->find("game_db", array("gamename"=>$gamename), array("limit"=>100)));
	echo 'music.callback('.json_encode($data).')';
}

?>