<?php

/**
 * 管理目录配置文件
 *
 * @version        $Id: config.php 1 14:31 2010年7月12日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
    $userip = getenv('HTTP_CLIENT_IP');
} elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
    $userip = getenv('HTTP_X_FORWARDED_FOR');
} elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
    $userip = getenv('REMOTE_ADDR');
} elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
    $userip = $_SERVER['REMOTE_ADDR'];
}

//if (substr($userip, 0, 15) != '116.213.171.175' && substr($userip, 0, 15) != '116.213.171.174' && substr($userip, 0, 12) != '172.16.75.21' && substr($userip, 0, 7) != '192.168' && substr($userip, 0, 9) != '127.0.0.1' && substr($userip, 0, 13) != '60.169.38.131') {
//    #header('Content-type:text/html;charset=utf-8');
//    #echo '<script>window.location.href="http://www.joyme.com/404.html";</script>';exit;
//}

//if ($_SERVER['HTTP_HOST'] == 'article.joyme.com') {
//    echo '<script>window.location.href="http://cmsadmin.joyme.com' . $_SERVER['REQUEST_URI'] . '";</script>';
//    exit;
//}

define('DEDEADMIN', str_replace("\\", '/', dirname(__FILE__)));
require_once(DEDEADMIN . '/../include/common.inc.php');
require_once(DEDEINC . '/userlogin.class.php');
header('Cache-Control:private');
$dsql->safeCheck = FALSE;
$dsql->SetLongLink();
$cfg_admin_skin = 1; // 后台管理风格

if (file_exists(DEDEDATA . '/admin/skin.txt')) {
    $skin = file_get_contents(DEDEDATA . '/admin/skin.txt');
    $cfg_admin_skin = !in_array($skin, array(1, 2, 3, 4)) ? 1 : $skin;
}

//获得当前脚本名称，如果你的系统被禁用了$_SERVER变量，请自行更改这个选项
$dedeNowurl = $s_scriptName = '';
$isUrlOpen = @ini_get('allow_url_fopen');
$dedeNowurl = GetCurUrl();
$dedeNowurls = explode('?', $dedeNowurl);
$s_scriptName = $dedeNowurls[0];
$cfg_remote_site = empty($cfg_remote_site) ? 'N' : $cfg_remote_site;
$uploadimg = isset($uploadimg) ? $uploadimg : false;

// 脚本设置 false不检查登录状态， true 检查登录状态
$is_check = isset($is_check) ? $is_check : true;
if($is_check){
    //检验用户登录状态
    $cuserLogin = new userLogin();
    $isLogin = $cuserLogin->isLogin();

	if (!$isLogin) {
		$cuserLogin->authenticate();
	} else {
		//登录成功的初始化用户
		$cuserLogin->initCmsUserDB();
		//登录成功的刷新相关权限
		$cuserLogin->keepUser();
	}
}

if ($cfg_dede_log == 'Y') {
    $s_nologfile = '_main|_list';
    $s_needlogfile = 'sys_|file_';
    $s_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    $s_query = isset($dedeNowurls[1]) ? $dedeNowurls[1] : '';
    $s_scriptNames = explode('/', $s_scriptName);
    $s_scriptNames = $s_scriptNames[count($s_scriptNames) - 1];
    $s_userip = GetIP();
    if ($s_method == 'POST' || (!preg_match("#" . $s_nologfile . "#i", $s_scriptNames) && $s_query != '') || preg_match("#" . $s_needlogfile . "#i", $s_scriptNames)) {
        $inquery = "INSERT INTO `#@__log`(adminid,filename,method,query,cip,dtime)
             VALUES ('" . $cuserLogin->getUserID() . "','{$s_scriptNames}','{$s_method}','" . addslashes($s_query) . "','{$s_userip}','" . time() . "');";
        $dsql->ExecuteNoneQuery($inquery);
    }
}

//启用远程站点则创建FTP类
if ($cfg_remote_site == 'Y') {
    require_once(DEDEINC . '/ftp.class.php');
    if (file_exists(DEDEDATA . "/cache/inc_remote_config.php")) {
        require_once DEDEDATA . "/cache/inc_remote_config.php";
    }
    if (empty($remoteuploads))
        $remoteuploads = 0;
    if (empty($remoteupUrl))
        $remoteupUrl = '';
    $config = array(
        'hostname' => $GLOBALS['cfg_ftp_host'],
        'username' => $GLOBALS['cfg_ftp_user'],
        'password' => $GLOBALS['cfg_ftp_pwd'],
        'debug' => 'TRUE'
    );
    $ftp = new FTP($config);

    //初始化FTP配置
    if ($remoteuploads == 1) {
        $ftpconfig = array(
            'hostname' => $rmhost,
            'port' => $rmport,
            'username' => $rmname,
            'password' => $rmpwd
        );
    }
}

//管理缓存、管理员频道缓存
$cache1 = DEDEDATA . '/cache/inc_catalog_base.inc';
if (!file_exists($cache1))
    UpDateCatCache();
$cacheFile = DEDEDATA . '/cache/admincat_' . $cuserLogin->userID . '.inc';
if (file_exists($cacheFile))
    require_once($cacheFile);

//更新服务器
require_once (DEDEDATA . '/admin/config_update.php');

/**
 *  更新栏目缓存
 *
 * @access    public
 * @return    void
 */
function UpDateCatCache() {
    global $dsql, $cfg_multi_site, $cache1, $cacheFile, $cuserLogin;
    $cache2 = DEDEDATA . '/cache/channelsonlist.inc';
    $cache3 = DEDEDATA . '/cache/channeltoplist.inc';
    $dsql->SetQuery("SELECT id,reid,channeltype,issend,typename FROM `#@__arctype`");
    $dsql->Execute();
    $fp1 = fopen($cache1, 'w');
    $phph = '?';
    $fp1Header = "<{$phph}php\r\nglobal \$cfg_Cs;\r\n\$cfg_Cs=array();\r\n";
    fwrite($fp1, $fp1Header);
    while ($row = $dsql->GetObject()) {
        // 将typename缓存起来
        $row->typename = base64_encode($row->typename);
        fwrite($fp1, "\$cfg_Cs[{$row->id}]=array({$row->reid},{$row->channeltype},{$row->issend},'{$row->typename}');\r\n");
    }
    fwrite($fp1, "{$phph}>");
    fclose($fp1);
    $cuserLogin->ReWriteAdminChannel();
    @unlink($cache2);
    @unlink($cache3);
}

// 清空选项缓存
function ClearOptCache() {
    $tplCache = DEDEDATA . '/tplcache/';
    $fileArray = glob($tplCache . "inc_option_*.inc");
    if (count($fileArray) > 1) {
        foreach ($fileArray as $key => $value) {
            if (file_exists($value))
                unlink($value);
            else
                continue;
        }
        return TRUE;
    }
    return FALSE;
}

/**
 *  更新会员模型缓存
 *
 * @access    public
 * @return    void
 */
function UpDateMemberModCache() {
    global $dsql;
    $cachefile = DEDEDATA . '/cache/member_model.inc';

    $dsql->SetQuery("SELECT * FROM `#@__member_model` WHERE state='1'");
    $dsql->Execute();
    $fp1 = fopen($cachefile, 'w');
    $phph = '?';
    $fp1Header = "<{$phph}php\r\nglobal \$_MemberMod;\r\n\$_MemberMod=array();\r\n";
    fwrite($fp1, $fp1Header);
    while ($row = $dsql->GetObject()) {
        fwrite($fp1, "\$_MemberMod[{$row->id}]=array('{$row->name}','{$row->table}');\r\n");
    }
    fwrite($fp1, "{$phph}>");
    fclose($fp1);
}

/**
 *  引入模板文件
 *
 * @access    public
 * @param     string  $filename  文件名称
 * @param     bool  $isabs  是否为管理目录
 * @return    string
 */
function DedeInclude($filename, $isabs = FALSE) {
    return $isabs ? $filename : DEDEADMIN . '/' . $filename;
}

/**
 *  获取当前用户的ftp站点
 *
 * @access    public
 * @param     string  $current  当前站点
 * @param     string  $formname  表单名称
 * @return    string
 */
function GetFtp($current = '', $formname = '') {
    global $dsql;
    $formname = empty($formname) ? 'serviterm' : $formname;
    $cuserLogin = new userLogin();
    $row = $dsql->GetOne("SELECT servinfo FROM `#@__multiserv_config`");
    $row['servinfo'] = trim($row['servinfo']);
    if (!empty($row['servinfo'])) {
        $servinfos = explode("\n", $row['servinfo']);
        $select = "";
        echo '<select name="' . $formname . '" size="1" id="serviterm">';
        $i = 0;
        foreach ($servinfos as $servinfo) {
            $servinfo = trim($servinfo);
            list($servname, $servurl, $servport, $servuser, $servpwd, $userlist) = explode('|', $servinfo);
            $servname = trim($servname);
            $servurl = trim($servurl);
            $servport = trim($servport);
            $servuser = trim($servuser);
            $servpwd = trim($servpwd);
            $userlist = trim($userlist);
            $checked = ($current == $i) ? '  selected="selected"' : '';
            if (strstr($userlist, $cuserLogin->getUserName())) {
                $select.="<option value='" . $servurl . "," . $servuser . "," . $servpwd . "'{$checked}>" . $servname . "</option>";
            }
            $i++;
        }
        echo $select . "</select>";
    }
}

helper('cache');
/**
 *  根据用户mid获取用户名称
 *
 * @access    public
 * @param     int  $mid   用户ID
 * @return    string
 */
if (!function_exists('GetMemberName')) {

    function GetMemberName($mid = 0) {
        global $dsql;
        $rs = GetCache('memberlogin', $mid);
        if (empty($rs)) {
            $rs = $dsql->GetOne("SELECT * FROM `#@__member` WHERE mid='{$mid}' ");
            SetCache('memberlogin', $mid, $rs, 1800);
        }
        return $rs['uname'];
    }

}

/**
 *  joymearctypes 获取用户栏目
 *
 * @access    public
 * @param     int  $mid   用户ID
 * @return    string
 */
if (!function_exists('GetTypes')) {
    function GetTypes($ids = '0', $typeid) {
        if (empty($ids)) {
            return '';
        }
        $str = '';
        $alltypes = getalltypes();
        $typearr = explode(',', $ids);
        foreach($typearr as $val){
            if(empty($val) || $val == $typeid) continue;
            $str .= ',<a href="content_list.php?cid='.$val.'">'.$alltypes[$val]['typename'].'</a>';
        }
        return $str;
    }

}

function getalltypes(){
    global $dsql;
    $data = array();
    // 1190 栏目不在更新使用 排除
    $wheresql = ' WHERE id NOT IN (8,9,1190) AND reid NOT IN (8,9,1190)  AND topid NOT IN (8,9,1190)';
    $dsql->Execute( 'type', 'SELECT id, reid, topid, typename FROM #@__arctype'.$wheresql);
    while($row = $dsql->GetArray('type')){
        $data[$row['id']] = $row;
    }
    return $data;
}