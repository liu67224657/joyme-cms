<?php
	

    /**
     * Created by JetBrains PhpStorm.
     * User: pengzhang
     * Date: 14-4-09
     * Time: 上午11:50
     */
    header("Content-Type: text/html; charset=utf-8");
    error_reporting(E_ERROR | E_WARNING);
    date_default_timezone_set("Asia/chongqing");
    include "Uploader.class.php";
	$root_dir = str_replace('\\', '/', dirname(dirname(dirname(dirname(__FILE__)))));
	include $root_dir."/data/common.inc.php";
	include $root_dir."/include/extend.func.php";
    //上传图片框中的描述表单名称，
    $title = htmlspecialchars($_POST['pictitle'], ENT_QUOTES);
    $path = htmlspecialchars($_POST['dir'], ENT_QUOTES);
    $globalConfig = include( "config.php" );
    $imgSavePathConfig = $globalConfig[ 'imageSavePath' ];

    //获取存储目录
    if ( isset( $_GET[ 'fetch' ] ) ) {

        header( 'Content-Type: text/javascript' );
        echo 'updateSavePath('. json_encode($imgSavePathConfig) .');';
        return;

    }

    //上传配置
    $config = array(
        "savePath" => $imgSavePathConfig,
        "maxSize" => 8000, //单位KB
        "allowFiles" => array(".gif", ".png", ".jpg", ".jpeg", ".bmp"),
        "fileNameFormat" => $_POST['fileNameFormat']
    );

    if ( empty( $path ) ) {

        $path = $config[ 'savePath' ][ 0 ];

    }

    //上传目录验证
    if ( !in_array( $path, $config[ 'savePath' ] ) ) {
        //非法上传目录
        echo '{"state":"\u975e\u6cd5\u4e0a\u4f20\u76ee\u5f55"}';
        return;
    }

    $config[ 'savePath' ] = '../../../'.$path . '/';

    //生成上传实例对象并完成上传
    $up = new Uploader("upfile", $config);

    /**
     * 得到上传文件所对应的各个参数,数组结构
     * array(
     *     "originalName" => "",   //原始文件名
     *     "name" => "",           //新文件名
     *     "url" => "",            //返回的地址
     *     "size" => "",           //文件大小
     *     "type" => "" ,          //文件类型
     *     "state" => ""           //上传状态，上传成功时必须返回"SUCCESS"
     * )
     */
    $info = $up->getFileInfo();
	$info['imgurl'] = uploadImgToQiniu($root_dir.'/'.str_replace('../', '', $info['url']));
	/**
	 * 为gif的图片生成jpeg的静态图片
	 *
	 */
	$imgPath = $up->getImgPath();
	if($info['type'] == '.gif'){
		$input = $imgPath.'/'.$info['name'];
		$output = $imgPath.'/'.str_replace('gif', 'jpeg', $info['name']);
		$image=imagecreatefromgif($input);
		imagejpeg($image,$output);
		imagedestroy($image);
	}
	
	$picname_info=getimagesize($info[ "url" ]);
	$pic_size_th = $picname_info[0];
	$pic_size_eh = $picname_info[1];

    /**
     * 向浏览器返回数据json数据
     * {
     *   'url'      :'a.jpg',   //保存后的文件路径
     *   'title'    :'hello',   //文件描述，对图片来说在前端会添加到title属性上
     *   'original' :'b.jpg',   //原始文件名
     *   'state'    :'SUCCESS'  //上传状态，成功时返回SUCCESS,其他任何值将原样返回至图片上传框中
     * }
     */
	 if($title==$info["originalName"]){
		$title = '';
	 }
	 if($pic_size_th>300 && $pic_size_eh>300){
		$info[ "imgurl" ] .= '?watermark/1/image/aHR0cDovL2pveW1lcGljLmpveW1lLmNvbS9hcnRpY2xlL3VwbG9hZHMvMTYwODE5LzgwLTE2MFE5MUZaMzQzOC5wbmc=/dissolve/70/gravity/SouthEast/ws/0.13';
	 }
	 
	 echo "{'url':'"  . $info[ "imgurl" ] .  "','title':'" . $title . "','original':'" . $info["originalName"] . "','state':'" . $info["state"] . "','width':'".$pic_size_th."','height':'".$pic_size_eh."'}";
    //echo "{'url':'" . $info["url"] . "','title':'" . $title . "','original':'" . $info["originalName"] . "','state':'" . $info["state"] . "'}";

	
