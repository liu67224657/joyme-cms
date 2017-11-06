<?php
require_once(dirname(__FILE__)."/config.php");
$id = isset($id) && is_numeric($id) ? $id : 0;
$ENV_GOBACK_URL = empty($_COOKIE['ENV_GOBACK_URL'])? "category_main.php" : $_COOKIE['ENV_GOBACK_URL'];
if(empty($dopost))
{
	$dopost = "";
}
if($dopost=='save')
{
	if(!preg_match("/^\#[a-fA-F0-9]{6}$/i", $typeColor))
	{
		ShowMsg("颜色格式不对(例:#ff5533)！","-1");
		exit();
	}
	$typeColor = strtolower(substr($typeColor,1));
	$typeStatus = empty($typeStatus)?0:1;
	$typeArticle = empty($typeArticle)?0:4;
	if(!empty($url) && !strstr($url, 'http://')){
		$url = 'http://'.$url;
	}
	$regex = "/(http|https|ftp|file){1}(:\/\/)?([\da-z-\.]+)\.([a-z]{2,6})([\/\w \.-?&%-=]*)*\/?/";
	if(strlen($url)>8 && preg_match($regex, $url)==0){
		ShowMsg("URL格式错误！","-1");
		exit();
	}
	if($typeName == ''){
		ShowMsg("分类名称不可以为空！","-1");
		exit();
	}
	$row = $dsql->GetOne("Select count(id) as num From #@__category where typeName='$typeName'");
	if($row['num']>1)
	{
		ShowMsg("该分类已经存在！","-1");
		exit();
	}
	if($id){
		$query = "Update `#@__category` set typeName='$typeName',typeColor='$typeColor',typeStatus='$typeStatus', typeArticle='$typeArticle', url='$url' where id=$id ";
		$dsql->ExecuteNoneQuery($query);
		ShowMsg("修改成功！",$ENV_GOBACK_URL);
	}else{
		
		$query = "Insert Into #@__category(typeName,typeColor, typeArticle, url) Values('$typeName','$typeColor', '$typeArticle', '$url');";
		$dsql->ExecuteNoneQuery($query);
		ShowMsg("成功增加一个分类！","category_main.php");
	}
	exit();
}
//删除评论
elseif( $dopost == 'delete' )
{
	//$query = "Delete From `#@__category` where id=$id ";
	//$dsql->ExecuteNoneQuery($query);
	ShowMsg("成功删除指定的分类!",$_COOKIE['ENV_GOBACK_URL'],0,500);
	exit();
}
$query = "select * from `#@__category` where id=$id";
$row = $dsql->GetOne($query);

include DedeInclude('templets/category_edit.htm');

?>