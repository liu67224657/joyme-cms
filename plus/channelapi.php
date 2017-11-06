<?php
/**
 * Description:多渠道api接口
 * Author: gradydong
 * Date: 2017/5/4
 * Time: 16:19
 * Copyright: Joyme.com
 */
require_once(dirname(__FILE__)."/../include/common.inc.php");

use Joyme\core\Request;
use Joyme\db\JoymeDb;
global $cfg_dbhost,$cfg_dbuser,$cfg_dbpwd,$cfg_dbname,$cfg_dbprefix;
$db_config = array(
    'hostname' => $cfg_dbhost,
    'username' => $cfg_dbuser,
    'password' => $cfg_dbpwd,
    'database' => $cfg_dbname
);
//执行action
$action = Request::getParam('action');
if($action=="searchtitle") {
    $title = Request::getParam('title');
    if ($title) {
        $table = $cfg_dbprefix . "archives";
        $db = new JoymeDb($db_config, $table);
        $lists = $db->select("*", array(
            'title' => array('like', '%' . $title . '%'),
        ), '', '', '');
        $data = array(
            'rs' => '1',
            'msg' => 'success',
            'result' => $lists,
        );
    } else {
        $data = array(
            'rs' => '-1',
            'msg' => 'no title',
            'result' => array()
        );
    }
}
elseif ($action=="searcharctype"){
    $typeid = (int)Request::getParam('typeid');
    if ($typeid) {
        $table = $cfg_dbprefix . "arctype";
        $db = new JoymeDb($db_config, $table);
        $lists = $db->select("*", array(
            'id' => $typeid,
        ));
        if($lists){
            $list = $lists[0];
        }else{
            $list = array();
        }
        $data = array(
            'rs' => '1',
            'msg' => 'success',
            'result' => $list,
        );
    } else {
        $data = array(
            'rs' => '-1',
            'msg' => 'no typeid',
            'result' => array()
        );
    }
}else{
    $data = array(
        'rs'=>'-1',
        'msg'=>'no action',
        'result'=>array()
    );
}
echo json_encode($data);