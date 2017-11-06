<?php

use Joyme\core\Log;
use Joyme\net\Curl;
use Joyme\core\JoymeToolsUser;
use Joyme\qiniu\Qiniu_RS_PutPolicy;
use Joyme\qiniu\Qiniu_PutExtra;
use Joyme\qiniu\Qiniu_ImageView;
use Joyme\qiniu\Qiniu_Utils;
use Joyme\core\Utils;

function litimgurls($imgid = 0)
{
    global $lit_imglist, $dsql;
    //获取附加表
    $row = $dsql->GetOne("SELECT c.addtable FROM #@__archives AS a LEFT JOIN #@__channeltype AS c 
                                                            ON a.channel=c.id where a.id='$imgid'");
    $addtable = trim($row['addtable']);

    //获取图片附加表imgurls字段内容进行处理
    $row = $dsql->GetOne("Select imgurls From `$addtable` where aid='$imgid'");

    //调用inc_channel_unit.php中ChannelUnit类
    $ChannelUnit = new ChannelUnit(2, $imgid);

    //调用ChannelUnit类中GetlitImgLinks方法处理缩略图
    $lit_imglist = $ChannelUnit->GetlitImgLinks($row['imgurls']);

    //返回结果
    return $lit_imglist;
}

function IDReturnURL($ID)
{

    global $dsql;
    $query = "Select arc.*,tp.typedir,tp.typename,tp.corank,tp.isdefault,
	tp.defaultname,tp.namerule,tp.moresite,tp.siteurl,tp.sitepath
	from dede_archives arc left join dede_arctype tp on arc.typeid=tp.id where arc.id = " . $ID;
    $row = $dsql->GetOne($query);
    $ReturnURL = GetFileUrl($row['id'], $row['typeid'], $row['senddate'], $row['title'], $row['ismake'],
        $row['arcrank'], $row['namerule'], $row['typedir'], $row['money'], $row['filename'], $row['moresite'], $row['siteurl'], $row['sitepath']);
    return $ReturnURL;
}

function get_big_img_src($str)
{
    preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i', $str, $match);
    return $match[1];
}

function geturl($aid)
{
    global $lit_imglist, $dsql, $com;
    $query = "SELECT arc.*,ch.maintable,ch.addtable,ch.issystem,ch.editcon,
              tp.typedir,tp.typename,tp.corank,tp.namerule,tp.namerule2,tp.ispart,tp.moresite,tp.sitepath,tp.siteurl
           FROM `#@__arctiny` arc
           LEFT JOIN `#@__arctype` tp ON tp.id=arc.typeid
           LEFT JOIN `#@__channeltype` ch ON ch.id=tp.channeltype
           WHERE arc.id='$aid' ";
    $trow = $dsql->GetOne($query);
    $trow['maintable'] = (trim($trow['maintable']) == '' ? '#@__archives' : trim($trow['maintable']));
    if ($trow['issystem'] != -1) {
        $arcQuery = "SELECT arc.*,tp.typedir,tp.typename,tp.corank,tp.namerule,tp.namerule2,tp.ispart,tp.moresite,tp.sitepath,tp.siteurl
                   FROM `{$trow['maintable']}` arc LEFT JOIN `#@__arctype` tp on arc.typeid=tp.id
                   LEFT JOIN `#@__channeltype` ch on ch.id=arc.channel WHERE arc.id='$aid' ";
        $arcRow = $dsql->GetOne($arcQuery);
        $arcurl = GetFileUrl($arcRow['id'], $arcRow['typeid'], $arcRow['senddate'], $arcRow['title'], $arcRow['ismake'], $arcRow['arcrank'], $arcRow['namerule'], $arcRow['typedir'], $arcRow['money'], $arcRow['filename'], $arcRow['moresite'], $arcRow['siteurl'], $arcRow['sitepath']);
    } else {
        $arcurl = '';
    }
    $arcurl = str_replace('article', 'http://marticle.joyme.' . $com . '/marticle', $arcurl);
    if (strpos($arcurl, 'http') == false) {
        $com = $com == 'alpha' ? 'dev' : $com;
        $arcurl = 'http://marticle.joyme.' . $com . '/marticle' . $arcurl;
        return $arcurl;
    }
    return substr($arcurl, 1);// ?
}

function murl($url)
{
    $arcurl = str_replace('/article/', '/', $url);
    return $arcurl;
}

/**
 * 七牛---图片云迁移
 *
 */
function uploadImgToQiniu($filePath, $savePath = '')
{
    global $root_dir, $conf;
    if ($savePath == '') {
        $savePath = str_replace($root_dir, 'article', $filePath);
    }

    list($ret, $err) = Qiniu_Utils::Qiniu_SaveFile($conf['qiniu']['bucket'], $savePath, $filePath, true);
    if ($err !== null) {
        Log::error($err);
        return '';
    } else {
        $imgurl = $conf['qiniu']['attachurl'] . '/' . $ret['key'];
        return $imgurl;
    }
}

function uploadImg($resource)
{
    global $root_dir, $conf;
    $errmsg = '';
    $allowtype = array('image/jpeg','image/png','image/jpg','image/gif');
    $data = $resource;
    if($data['tmp_name'] == ''){
        $errmsg = '没有文件上传';
        echo $errmsg;exit;
    }
    if(!in_array($data['type'], $allowtype)){
        $errmsg = '文件不是图片类型文件,type:'.$data['type'];
        echo $errmsg;exit;
    }
    if($data['error'] != 0){
        $errmsg = '文件上传出错,error：'.$data['error'];
        echo $errmsg;exit;
    }
    if($data['size'] > 4*1024*1024){
        $errmsg = '文件大小超出4M,size：'.$data['size'];
        echo $errmsg;exit;
    }
    $savePath = 'article/images/'.date('Ym', time()).'/'.uniqid().'.'.str_replace('image/', '', $data['type']);
    $imgurl = uploadImgToQiniu($data['tmp_name'], $savePath);
    return $imgurl;
}

/**
 * 删除七牛图片
 */
function delImage($savePath)
{
    global $conf;
    Qiniu_Utils::Qiniu_DeleteFile($conf['qiniu']['bucket'], $savePath);
}

/**
 * CURL GET FUNCTION
 */
function joymeCurlGetFn($url)
{
//    $url = "http://localhost/web_services.php";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

/**
 * CURL POST FUNCTION
 */
function joymeCurlPostFn($url, $data)
{
//    $url = "http://localhost/web_services.php";
//    $data = array('c'=>3, 'd'=>4);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_ENCODING, "");
	
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

/****************
 * function GetOneImgUrl
 * @@ 功能：读取自定义字段图片地址
 *****************/
function GetOneImgUrl($img, $ftype = 1)
{
    if ($img <> '') {
        $dtp = new DedeTagParse();
        $dtp->LoadSource($img);
        if (is_array($dtp->CTags)) {
            foreach ($dtp->CTags as $ctag) {
                if ($ctag->GetName() == 'img') {
                    $width = $ctag->GetAtt('width');
                    $height = $ctag->GetAtt('height');
                    $imgurl = trim($ctag->GetInnerText());
                    $img = '';
                    if ($imgurl != '') {
                        if ($ftype == 1) {
                            $img .= $imgurl;
                        } else {
                            $img .= '<img src="' . $imgurl . '" width="' . $width . '" height="' . $height . '" />';
                        }
                    }

                }
            }
        }
        $dtp->Clear();
        return $img;
    }
}

// 记录文章链接
function saveArcUrl($aid, $data = '')
{
    global $dsql, $com;
    if (!is_dir('/opt/servicelogs/backlog')) return;
    $file = '/opt/servicelogs/backlog/aly03cms_arcurl.log_' . date('Y-m-d');
    // 跳转链接redirecturl
    if ($data) {
        file_put_contents($file, $data, FILE_APPEND);
    } else {
        // 查询文章
        $aquery = 'SELECT * FROM #@__archives WHERE id = ' . $aid;
        $dsql->Execute('me', $aquery);
        $article = $dsql->GetArray();
        // 查询栏目
        $cquery = 'SELECT * FROM #@__arctype WHERE id = ' . $article['typeid'];
        $dsql->Execute('me', $cquery);
        $column = $dsql->GetArray();
        // 文章链接
        $url = str_replace('{Y}', date('Y', $article['senddate']), $column['namerule']);
        $url = str_replace('{M}', date('m', $article['senddate']), $url);
        $url = str_replace('{D}', date('d', $article['senddate']), $url);
        $url = str_replace('{aid}', $article['id'], $url);
        $url = str_replace('{typedir}', $column['typedir'], $url);
        $url = str_replace('{cmspath}', $GLOBALS['cfg_cmspath'], $url);
        if (strpos($url, '/') !== 0) {
            $url .= '/' . $url;
        }
        $urlarr = $channelarr = array();
        $urlarr[] = $GLOBALS['domain'] . '/article' . $url;
        if ($column['channeltemp'] != '') {
            $channelarr = explode(',', $column['channeltemp']);
        }
        if (!empty($channelarr)) {
            foreach ($channelarr as $val) {
                $urlarr[] = $GLOBALS['domain'] . '/article/' . $val . $url;
            }
        }
        $url = 'http://webcache.joyme.' . $com . '/json/urlrule/desrule.do?urls=' . implode(',', $urlarr);
        $data = json_decode(gzdecode(joymeCurlGetFn($url)), true);
        if ($data[rs] == 1 && is_array($data['result'])) {
            foreach ($data['result'] as $key => $val) {
                if (!is_array($val)) continue;
                foreach ($val as $v) {
                    $url = $v;
                    $data = date('Y-m-d H:i:s', time()) . ' ' . $article['id'] . ' ' . $article['typeid'] . ' ' . codeurl($url) . ' ' . date('Y-m-d H:i:s', $article['pubdate']) . "\n";
                    file_put_contents($file, $data, FILE_APPEND);
                }
            }
        }
    }
}

function codeurl($url)
{
    $var = parse_url($url);
    $params = explode('/', $var['path']);
    $encodeurl = $var['scheme'] . "://" . $var['host'];
    foreach ($params as $param) {
        if (!empty($param)) {
            $encodeurl .= '/' . rawurlencode($param);
        }
    }
    return $encodeurl;
}

function is_mobile()
{

    // returns true if one of the specified mobile browsers is detected

    $regex_match = "/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";
    $regex_match .= "htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";
    $regex_match .= "blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";
    $regex_match .= "symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";
    $regex_match .= "jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";
    $regex_match .= ")/i";
    return isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE']) or preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT']));
}


function baiduPushUrl($data=array())
{
    if($data){
        global $domain;
        $arcurl  = GetFileUrl($data['aid'],$data['typeid'],$data['senddate'],$data['title'],$data['ismake'],$data['arcrank'],$data['namerule'],$data['typedir'],$data['money'],$data['filename'],$data['moresite'],$data['siteurl'],$data['sitepath']);
        if($arcurl){
            //拼接srcurl
            $srcurl = $domain.$arcurl;
            //baidu推送
            Utils::baiduPush($srcurl);
        }
    }
}

function GetOneDocUrl($aid)
{
    global $dsql;
    include_once(DEDEINC."/channelunit.func.php");
    $aid = trim(preg_replace('/[^0-9]/','',$aid));
 
    $chRow = $dsql->GetOne("Select arc.*,ch.maintable,ch.addtable,ch.issystem From `dede_arctiny` arc left join `dede_channeltype` ch on ch.id=arc.channel where arc.id='$aid' ");
 
    if(!is_array($chRow)) {
        return '';
    }
    else {
        if(empty($chRow['maintable'])) $chRow['maintable'] = 'dede_archives';
    }
 
    if($chRow['issystem']!=-1)
    {
        $nquery = " Select arc.*,tp.typedir,tp.topid,tp.namerule,tp.moresite,tp.siteurl,tp.sitepath
                    From `{$chRow['maintable']}` arc left join `dede_arctype` tp on tp.id=arc.typeid
                    where arc.id='$aid' ";
    }
    else
    {
        $nquery = " Select arc.*,1 as ismake,0 as money,'' as filename,tp.typedir,tp.topid,tp.namerule,tp.moresite,tp.siteurl,tp.sitepath
                    From `{$chRow['addtable']}` arc left join `dede_arctype` tp on tp.id=arc.typeid
                    where arc.aid='$aid' ";
    }
 
    $arcRow = $dsql->GetOne($nquery);
 
    $Url = GetFileUrl($aid,$arcRow['typeid'],$arcRow['senddate'],$reArr['title'],$arcRow['ismake'],$arcRow['arcrank'],$arcRow['namerule'],$arcRow['typedir'],$arcRow['money'],$arcRow['filename'],$arcRow['moresite'],$arcRow['siteurl'],$arcRow['sitepath']);
	// if(strpos($Url, 'http://')!==false || strpos($Url, 'https://')!==false){
		// $Url;
	// }else if(strpos($Url, '/')===0){
		// $Url = $GLOBALS['domain'].$Url;
	// }else{
		// $Url = $GLOBALS['domain'].'/'.$Url;
	// }
    return $Url;
}

// 20160523 内容页改版 右侧其他人也在看
function qtryzk($tid){
	global $dsql;
	$html = '<div class="article-Rdjyc">
	<h3 class="joyme-title fn-clear"><span class="fn-left">其他人也在看</span></h3><div>';
	
	$sql = 'SELECT id,title FROM dede_archives WHERE typeid = '.$tid.' AND arcrank > -1 AND ismake = 1 AND FIND_IN_SET("c", flag) ORDER BY pubdate DESC LIMIT 12';
	$dsql->Execute('me', $sql);
	$data = array();
	while($row = $dsql->GetArray()){
		$data[] = $row;
	}
	$temp = array_rand($data, 4);
    foreach($temp as $val){
		$html .= '<a href="'.GetOneDocUrl($data[$val]['id']).'" target="_blank">'.$data[$val]['title'].'</a>';
	}
	$html .= '</div></div>';
	return $html;
}

// 2016/8/25  wiki词条
function cmsWikiWords( $body, array $wikiid ){
	global $dsql;
	if($body == '' || !is_array($wikiid)){
		return $body;
	}
    require_once(DEDEINC.'/dedesql.class.php');
	$dbr = new DedeSql(FALSE);
	$dbr->SetSource($GLOBALS['cfg_dbhost'],$GLOBALS['cfg_dbuser'],$GLOBALS['cfg_dbpwd']);
	$dbr->Open();
	$dbr->SelectDB('webcache');
	$con = '"'.implode('","', $wikiid).'"';
	$selsql = 'SELECT keyword,url FROM wiki_keyword WHERE wiki_id IN ('.$con.')';
	$res = $dbr->Execute('me', $selsql);
	$data = array();
	while($row = $dbr->GetArray()){
		$data[] = $row;
	}
	foreach($data as $row){
		$body = str_replace($row['keyword'], '<a href="'.$row['url'].'" target="_blank">'.$row['keyword'].'</a>', $body);
	}
	$dsql->SelectDB($GLOBALS['cfg_dbname']);
	return $body;
}

// 文章关联游戏库数据上报
function arcGames($data){
    global $apiUrl;
    $url = $apiUrl.'/collection/api/gamearchive/bind';
    $curl = new Curl();
    $res = $curl->Get($url, $data);
    $rs = json_decode($res, true);
    if($rs['rs'] == 0){
        Log::info(__FILE__, $res, '文章游戏关联');
    }else{
        Log::error(__FILE__, $res, '文章游戏关联');
    }
}
// 文章关联渠道后台数据上报
function arcToChannel($data){
    global $channelApiUrl;
    $url = $channelApiUrl.'?c=source&a=savedata';
    $curl = new Curl();
    $res = $curl->Post($url, $data);
    $rs = json_decode($res, true);
    if($rs['code'] == 1){
        Log::info(__FILE__, $res, '文章渠道上报');
    }else{
        Log::error(__FILE__, $res, '文章渠道上报');
    }
}

//wifi内容过滤
function changeContentForWifi($content){
    $content = str_replace( '<span>' ,'' ,$content );
    $content = str_replace( '</span>' ,'' ,$content );
    $content = str_replace( '<strong>' ,'' ,$content );
    $content = str_replace( '</strong>' ,'' ,$content );
	$content = preg_replace('/color: rgb\(\d+, \d+, \d+\);/','',$content);
	return $content;
}

//文章发布上报java更新
function arcJavaContentPost($param){
    global $com,$domain;
    $curl = new Curl();
    $aurl = $domain.$param['weburl'];
//    $aurl = str_replace("pc","wap",$aurl);
    $wurl = "http://webcache.joyme.".$com."/json/urlrule/desrule.do";
    $curl->SetGzip(true);
    $res = $curl->Post($wurl,array(
        'urls' => $aurl
    ));
    $res = json_decode($res, true);
    if($res['rs']==1){
        $arrs = $res['result'][0];
        if($arrs){
            $param['weburl'] = $arrs[$aurl];
            $url = "http://wikiservice.joyme.".$com."/api/wiki/content/post";
            $res = $curl->Post($url,$param);
            $rs = json_decode($res, true);
            if($rs['rs'] == 1){
                Log::info(__FILE__, $res, '文章发布上报wikiservice更新');
            }else{
                Log::error(__FILE__, $res, $param,'文章发布上报wikiservice更新');
            }
        }else{
            Log::info(__FILE__, $res, 'url配置规则为空');
        }
    }else{
        Log::error(__FILE__, $res,'获取webcacheurl失败');
    }
}

//上报文章更新状态
function arcJavaUpdateStatus($param){
    global $com;
    $url = "http://wikiservice.joyme.".$com."/api/wiki/content/updatestatus";
    $curl = new Curl();
    $res = $curl->Post($url,$param);
    $rs = json_decode($res, true);
    if($rs['rs'] == 1){
        Log::info(__FILE__, $res, '文章状态上报wikiservice更新');
    }else{
        Log::error(__FILE__, $res, '文章状态上报wikiservice更新');
    }
}

//wikiapp文章获取游戏名称
function arcWikiAppGetGameName($arcid)
{
    global $apiUrl;
    $url = $apiUrl."/collection/api/gamearchive/getgames";
    $curl = new Curl();
    $res = $curl->Get($url,array(
        'archiveid' => $arcid
    ));
    $rs = json_decode($res, true);
    if($rs['rs'] == 1){
        return $rs['result'][0]['gameName'];
    }else{
        Log::error(__FILE__, $rs, '文章获取游戏名称');
        return '';
    }
}

//原创文章提交到百度
function arcBaiduOriginalPost($arcurl,$title)
{
    global $com,$domain,$channelApiUrl;
    $curl = new Curl();
    $aurl = $domain.$arcurl;
    $wurl = "http://webcache.joyme.".$com."/json/urlrule/desrule.do";
    $curl->SetGzip(true);
    $res = $curl->Post($wurl,array(
        'urls' => $aurl
    ));
    $res = json_decode($res, true);
    if($res['rs']==1){
        $arrs = $res['result'][0];
        if($arrs){
            if($arrs[$aurl]){
                $ch = curl_init();
                curl_setopt_array($ch,array(
                    CURLOPT_URL=>'http://data.zz.baidu.com/urls?site=http://www.joyme.com/&token=WzGLirMD1oFFXN4n&type=original',
                    CURLOPT_POST=>true,
                    CURLOPT_RETURNTRANSFER=>true,
                    CURLOPT_POSTFIELDS=>$arrs[$aurl],
                    CURLOPT_HTTPHEADER=>array('Content-Type: text/plain'),
                    CURLOPT_TIMEOUT=>60
                ));
                $result = curl_exec($ch);
                curl_close($ch);
                $rs = json_decode($result, true);
                if($rs['success'] == 1){
                    Log::info(__FILE__, $rs, '原创文章提交到百度');
                }else{
                    Log::error(__FILE__, $rs,'原创文章提交到百度');
                }
                //把结果记录到百度原创管理后台
                $channel_url = $channelApiUrl.'?c=baiduoriginalsource&a=savedata';
                $channel_res = $curl->Post($channel_url, array(
                    'source' => 2,
                    'title' => $title,
                    'url' => $arrs[$aurl],
                    'result' => $result,
                    'addtime' => time()
                ));
                $channel_rs = json_decode($channel_res, true);
                if($channel_rs['rs'] == 1){
                    Log::info(__FILE__, $channel_rs, '百度原创管理后台上报');
                }else{
                    Log::error(__FILE__, $channel_rs, '百度原创管理后台上报');
                }
            }
        }else{
            Log::info(__FILE__, $res, 'url配置规则为空');
        }
    }else{
        Log::error(__FILE__, $res,'获取webcacheurl失败');
    }
}