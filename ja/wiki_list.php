<?php
/**
 * 内容列表
 * content_s_list.php、content_i_list.php、content_select_list.php
 * 均使用本文件作为实际处理代码，只是使用的模板不同，如有相关变动，只需改本文件及相关模板即可
 *
 * @version        $Id: content_list.php 1 14:31 2010年7月12日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */

require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/../data/wiki.conf.php');
require_once(DEDEINC.'/dedesql.class.php');
require_once(DEDEINC.'/datalistcp.class.php');
//require_once(DEDEADMIN.'/inc/inc_list_functions.php');
//require_once(DEDEADMIN."/inc/dedetag.class.php");

if(!isset($wikikey)) $wikikey = '';
if(!isset($wikititle)) $wikititle = '';
if(!isset($wikiediter)) $wikiediter = '';
if(!isset($pageno)) $pageno = 1;
//var_dump($cuserLogin);exit;
if($wikikey != ''){
    $userwikisql = 'SELECT wikidbs FROM dede_admin WHERE id = '.$cuserLogin->userID;
    $res = $dsql->GetOne($userwikisql);
    $wikidbs = explode(',', $res['wikidbs']);
    if(!in_array($wikikey, $wikidbs)){
        ShowMsg("没有权限查看该wiki数据!","-1");exit;
    }
}

if($wikikey != ''){
    $wikidbconf['dbname'] = $wikikey.'wiki';
    $dsql->SetSource($wikidbconf['host'], $wikidbconf['username'], $wikidbconf['password'], $wikidbconf['dbname'], '');
    $dsql->linkID  = @mysql_connect($wikidbconf['host'],$wikidbconf['username'],$wikidbconf['password']);
    $dsql->Open();
    $dlist = new DataListCP();
    $dlist->pageSize = 30;
    $limit = ' limit '.$dlist->pageSize*($pageno-1) .', '.$dlist->pageSize;
    //模板
    if(empty($s_tmplets)) $s_tmplets = 'templets/wiki_list.htm';
    $dlist->SetTemplate(DEDEADMIN.'/'.$s_tmplets);
    $wheresql = '';
    if($wikititle){
        $wheresql .= ' AND page_title like "%'.$wikititle.'%"';
    }
    if($wikiediter){
        $wheresql .= ' AND rev_user_text like "%'.$wikiediter.'%"';
    }
    
    //查询
    $query = 'SELECT page_id,page_title,page_touched,rev_timestamp,rev_user_text,rev_id '
            .'FROM page LEFT JOIN revision ON page_latest=rev_id WHERE page_namespace = 0 '
            .$wheresql.'ORDER BY page_touched DESC ';
    $dlist->SetSource($query);
    $dlist->PreLoad();
    $list = $dlist->GetArcList('');
    
    $tmpdata = wikidata($wikikey, $list);
    $pageids = array();
    if(!empty($tmpdata)){
        foreach($tmpdata as $val){
            $pageids[] = $wikikey.'|'.$val['page_id'];
        }
    }else{
        foreach($list as $val){
            $pageids[] = $wikikey.'|'.$val['page_title'];
        }
    }

    $url = $webcacheurl.'/json/pagestat/pvlist.do?pageids='.implode(',', $pageids).'&pagetype=2';
    $data = json_decode(gzdecode(joymeCurlGetFn($url)), true);
    $wikidata = array();
    if($tmpdata){
        foreach($tmpdata as $key=>$val){
            $wikidata[$key] = $val;
            if(!empty($data['result'][$val['page_id']])){
                $wikidata[$key]['pcpv'] = $data['result'][$val['page_id']]['pcPv'];
                $wikidata[$key]['mpv'] = $data['result'][$val['page_id']]['mPv'];
                $wikidata[$key]['sumpv'] = $data['result'][$val['page_id']]['pvSum'];
                $wikidata[$key]['ctotal'] = $data['result'][$val['page_id']]['replySum'];
            }
        }
    }else{
        foreach ($list as $val){
            foreach($data['result'] as $key=>$v){
                $s = explode('|', $key);
                if($s[1] == $val['page_title']){
                    $wikidata[$val['page_id']]['pcpv'] = $v['pcPv'];
                    $wikidata[$val['page_id']]['mpv'] = $v['mPv'];
                    $wikidata[$val['page_id']]['sumpv'] = $v['pvSum'];
                    $wikidata[$val['page_id']]['ctotal'] = $v['replySum'];
                    break;
                }
            }
        }
    }
    
}else{
    $dlist = new DataListCP();
    if(empty($s_tmplets)) $s_tmplets = 'templets/wiki_list.htm';
    $dlist->SetTemplate(DEDEADMIN.'/'.$s_tmplets);
}
$dlist->SetParameter('wikikey', $wikikey);
$dlist->SetParameter('wikititle', $wikititle);
$dlist->SetParameter('wikiediter', $wikiediter);

$doPreLoad = false;
$dlist->Display($doPreLoad);
$dlist->Close();

function wikidata($wikikey, $res){
    global $dsql,$wikidbconf;
    $data = array();
    $dsql->SetSource($GLOBALS['cfg_dbhost'], $GLOBALS['cfg_dbuser'], $GLOBALS['cfg_dbpwd'], 'wikiurl', '');
    $dsql->linkID  = @mysql_connect($GLOBALS['cfg_dbhost'], $GLOBALS['cfg_dbuser'], $GLOBALS['cfg_dbpwd']);
    $dsql->Open();
    $querywiki = 'SELECT joyme_template_id FROM joyme_template WHERE wiki = "'.$wikikey.'"';
    $row = $dsql->GetOne($querywiki);
    if(isset($row['joyme_template_id']) && $row['joyme_template_id']){
        $isugcwiki = false;
    }else{
        $isugcwiki = true;
    }
    if(!$isugcwiki){
        $titles = array();
        foreach($res as $val){
            $titles[] = $val['page_title'];
        }
        $querywiki = 'SELECT * FROM wiki_page WHERE wiki_url IN ("'.  implode('","', $titles).'")';
        $dsql->Execute('wiki', $querywiki);
        $data = array();
        while($row = $dsql->GetArray('wiki')){
            $key = '';
            foreach($res as $val){
                if($val['page_title'] == $row['wiki_url']){
                    $key = $val['page_id'];
                    break;
                }
            }
            $data[$key] = $row;
        }
    }
    $wikidbconf['dbname'] = $wikikey.'wiki';
    $dsql->SetSource($wikidbconf['host'], $wikidbconf['username'], $wikidbconf['password'], $wikidbconf['dbname'], '');
    $dsql->linkID  = @mysql_connect($wikidbconf['host'],$wikidbconf['username'],$wikidbconf['password']);
    $dsql->Open();
    return $data;
}


function getPv($id, $data, $type){
//    echo $id.'<br>';return;
    if(!is_array($data)){
        echo 0;
    }else if(!empty($data[$id]) && !empty($data[$id][$type])){
        if($data[$id][$type]>0 && $data[$id][$type]<=$GLOBALS['cfg_pva']){
            echo '<span style="color:'.$GLOBALS['cfg_pva_color'].'">'.$data[$id][$type].'</span>';
        }else if($data[$id][$type]>$GLOBALS['cfg_pva'] && $data[$id][$type]<=$GLOBALS['cfg_pvb']){
            echo '<span style="color:'.$GLOBALS['cfg_pvb_color'].'">'.$data[$id][$type].'</span>';
        }else if($GLOBALS['cfg_pvb']<=$data[$id][$type]){
            echo '<span style="color:'.$GLOBALS['cfg_pvc_color'].'">'.$data[$id][$type].'</span>';
        }else{
            echo $data[$id][$type];
        }
    }else{
        echo 0;
    }
}