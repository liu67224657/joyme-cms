<?php
/**
 * 栏目选项函数
 *
 * @version        $Id: inc_catalog_options.php 1 10:32 2010年7月21日Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
 
/**
 *  获取选项列表
 *
 * @access    public
 * @param     string  $selid  选择ID
 * @param     string  $userCatalog  用户类目
 * @param     string  $channeltype  频道类型
 * @return    string
 */
function GetOptionList($selid=0, $userCatalog=0, $channeltype=0)
{
    global $OptionArrayList, $channels, $dsql, $cfg_admin_channel, $admin_catalogs;

    $dsql->SetQuery("SELECT id,typename FROM `#@__channeltype` ");
    $dsql->Execute();
    $channels = Array();
    while($row = $dsql->GetObject()) $channels[$row->id] = $row->typename;

    $OptionArrayList = '';

    //当前选中的栏目
    if($selid > 0)
    {
        $row = $dsql->GetOne("SELECT id,typename,ispart,channeltype FROM `#@__arctype` WHERE id='$selid'");
        if($row['ispart']==1) $OptionArrayList .= "<option value='".$row['id']."' class='option1' selected='selected'>".$row['typename']."(封面频道)</option>\r\n";
        else $OptionArrayList .= "<option value='".$row['id']."' selected='selected'>".$row['typename']."</option>\r\n";
    }

    //是否限定用户管理的栏目
    if( $cfg_admin_channel=='array' )
    { 
        if(count($admin_catalogs)==0)
        {
            $query = "SELECT id,typename,ispart,channeltype FROM `#@__arctype` WHERE 1=2 ";
        }
        else
        {
            $admin_catalog = join(',', $admin_catalogs);
            $dsql->SetQuery("SELECT reid FROM `#@__arctype` WHERE id IN($admin_catalog) GROUP BY reid ");
            $dsql->Execute();
            $topidstr = '';
            while($row = $dsql->GetObject())
            {
                if($row->reid==0) continue;
                $topidstr .= ($topidstr=='' ? $row->reid : ','.$row->reid);
            }
            $admin_catalog .= ','.$topidstr;
            $admin_catalogs = explode(',', $admin_catalog);
            $admin_catalogs = array_unique($admin_catalogs);
            $admin_catalog = join(',', $admin_catalogs);
            $admin_catalog = preg_replace("#,$#", '', $admin_catalog);
            $query = "SELECT id,typename,ispart,channeltype FROM `#@__arctype` WHERE id IN($admin_catalog) AND reid=0 AND ispart<>2 order by sortrank";
        }
    }
    else
    {
        $query = "SELECT id,typename,ispart,channeltype FROM `#@__arctype` WHERE ispart<>2 AND reid=0 ORDER BY sortrank ASC ";
    }

    $dsql->SetQuery($query);
    $dsql->Execute();

    while($row=$dsql->GetObject())
    {
        $sonCats = '';
        LogicGetOptionArray($row->id, '─', $channeltype, $dsql, $sonCats);
        if($sonCats != '')
        {
            if($row->ispart==1) $OptionArrayList .= "<option value='".$row->id."' class='option1'>".$row->typename."(封面频道)</option>\r\n";
            else if($row->ispart==2) $OptionArrayList .= '';
            else if( empty($channeltype) && $row->ispart != 0 ) $OptionArrayList .= "<option value='".$row->id."' class='option2'>".$row->typename."(".$channels[$row->channeltype].")</option>\r\n";
            else $OptionArrayList .= "<option value='".$row->id."' class='option3'>".$row->typename."</option>\r\n";
            $OptionArrayList .= $sonCats;
        }
        else
        {
            if($row->ispart==0 && (!empty($channeltype) && $row->channeltype == $channeltype) )
            {
                $OptionArrayList .= "<option value='".$row->id."' class='option3'>".$row->typename."</option>\r\n";
            } else if($row->ispart==0 && empty($channeltype) )
            { 
                // 专题
                $OptionArrayList .= "<option value='".$row->id."' class='option3'>".$row->typename."</option>\r\n";
            }
        }
    }
    return $OptionArrayList;
}

/**
 *  获取文章栏目列表
 *
 * @access    public
 * @param     string  $selid  选择ID
 * @param     string  $userCatalog  用户类目
 * @param     string  $channeltype  频道类型
 * @return    string
 */
function GetTypeList($selid=0, $userCatalog=0, $channeltype=0)
{
    global $OptionArrayList, $channels, $dsql, $cfg_admin_channel, $admin_catalogs;

    $dsql->SetQuery("SELECT id,typename FROM `#@__channeltype` ");
    $dsql->Execute();
    $channels = Array();
    while($row = $dsql->GetObject()) $channels[$row->id] = $row->typename;

    $OptionArrayList = '';
    //当前选中的栏目
//    if($selid > 0 && false)
//    {
//        $row = $dsql->GetOne("SELECT id,reid,typename,ispart,channeltype FROM `#@__arctype` WHERE id='$selid'");
//        if($row['ispart']==1) $OptionArrayList .= "<option value='".$row['id']."' class='option1' selected='selected'>".$row['typename']."(封面频道)</option>\r\n";
//        else $OptionArrayList .= "<option value='".$row['id']."' selected='selected'>".$row['typename']."</option>\r\n";
//    }

    //是否限定用户管理的栏目
    if( $cfg_admin_channel=='array' )
    {
        if(count($admin_catalogs)==0)
        {
            $query = "SELECT id,reid,typename,ispart,channeltype,channeltemp FROM `#@__arctype` WHERE 1=2 ";
        }
        else
        {
            $admin_catalog = join(',', $admin_catalogs);
            $dsql->SetQuery("SELECT reid FROM `#@__arctype` WHERE id IN($admin_catalog) GROUP BY reid ");
            $dsql->Execute();
            $topidstr = '';
            while($row = $dsql->GetObject())
            {
                if($row->reid==0) continue;
                $topidstr .= ($topidstr=='' ? $row->reid : ','.$row->reid);
            }
            $admin_catalog .= ','.$topidstr;
            $admin_catalogs = explode(',', $admin_catalog);
            $admin_catalogs = array_unique($admin_catalogs);
            $admin_catalog = join(',', $admin_catalogs);
            $admin_catalog = preg_replace("#,$#", '', $admin_catalog);
            $query = "SELECT id,reid,typename,ispart,channeltype,channeltemp FROM `#@__arctype` WHERE id IN($admin_catalog) AND reid=0 AND ispart<>2 order by sortrank";
        }
    }
    else
    {
        $query = "SELECT id,reid,typename,ispart,channeltype,channeltemp FROM `#@__arctype` WHERE ispart<>2 AND reid=0 ORDER BY sortrank ASC ";
    }

    $dsql->SetQuery($query);
    $dsql->Execute();
    $typearr = array();
    while($row=$dsql->GetObject())
    {
        $row->level = 1;
        $sonCats = '';
        LogicGetOptionArray($row->id, 1, $channeltype, $dsql, $sonCats, 2, $typearr);
        if($sonCats != '')
        {$typearr[] = $row;
            if($row->ispart==1) $OptionArrayList .= "<option value='".$row->id."' class='option1'>".$row->typename."(封面频道)</option>\r\n";
            else if($row->ispart==2) $OptionArrayList .= '';
            else if( empty($channeltype) && $row->ispart != 0 ) $OptionArrayList .= "<option value='".$row->id."' class='option2'>".$row->typename."(".$channels[$row->channeltype].")</option>\r\n";
            else $OptionArrayList .= "<option value='".$row->id."' class='option3'>".$row->typename."</option>\r\n";
            $OptionArrayList .= $sonCats;
        }
        else
        {
//            if($row->ispart==0 && (!empty($channeltype) && $row->channeltype == $channeltype) )
            if($row->ispart==0 && (!empty($channeltype)) )
            {
                $typearr[] = $row;
//                $OptionArrayList .= "<option value='".$row->id."' class='option3'>".$row->typename."</option>\r\n";
            } else if($row->ispart==0 && empty($channeltype) )
            { 
                // 专题
                $typearr[] = $row;
//                $OptionArrayList .= "<option value='".$row->id."' class='option3'>".$row->typename."</option>\r\n";
            }
        }
    }
    return $typearr;
}

function LogicGetOptionArray($id,$step,$channeltype,&$dsql, &$sonCats, $level=2, &$typearr = array())
{   
    global $OptionArrayList, $channels, $cfg_admin_channel, $admin_catalogs;
    $dsql->SetQuery("Select id,reid,typename,ispart,channeltype,channeltemp From `#@__arctype` where reid='".$id."' And ispart<>2 order by sortrank asc");
    $dsql->Execute($id);
    while($row=$dsql->GetObject($id))
    {
        $row->level = $level;
        if($cfg_admin_channel != 'all' && !in_array($row->id, $admin_catalogs))
        {
            continue;
        }
//        if($row->channeltype==$channeltype && $row->ispart==1)
        if($row->ispart==1)
        {
            $typearr[] = $row;
            $sonCats .= "<option value='".$row->id."' class='option1'>$step".$row->typename."</option>\r\n";
        }
//        else if( ($row->channeltype==$channeltype && $row->ispart==0) || empty($channeltype) )
        else if( $row->ispart==0 || empty($channeltype) )
        {
            $typearr[] = $row;
            $sonCats .= "<option value='".$row->id."' class='option3'>$step".$row->typename."</option>\r\n";
        }
        LogicGetOptionArray($row->id,$step.'─',$channeltype,$dsql, $sonCats, $level+1, $typearr);
    }
}

function typeidSelectHtml($arr, $typeids){
    global $joymeTpls;
    if($typeids == '') return defaultselecthtml($arr);
    
    $str = '';
    $ids = explode(',', $typeids);
    $tmp = array();
    foreach($arr as $val){
        $tmp[$val->id] = $val;
    }
    foreach($ids as $key=>$val){
        $selectarr = array();
        getselecthtml($tmp, $val, $selectarr);
        krsort($selectarr);
        if($key==0){
            $str .= '<tr><td width="90">&nbsp;所属栏目：</td><td><input type="hidden" value="'.$typeids.'" id="joymearctypes" name="joymearctypes">'
                    .implode('', $selectarr).'</td></tr>';
        }else{
            $str .= '<tr><td></td><td>'.implode('', $selectarr).'<button type="button" class="joymearctypedel">X</button>点击删除此列</td></tr>';
        }
    }
    
    return $str;
}

function getselecthtml($tmp, $id, &$selectarr=array()){
    $str = "<select name='arctypes' data-level='1'>";
    foreach($tmp as $value){
        if($value->level != $tmp[$id]->level || $value->reid != $tmp[$id]->reid) continue;
        if($value->id == $id) $selected = ' selected="selected" ';
        else $selected = '';

        $str .= "<option value='{$value->id}' {$selected}>{$value->typename}</option>";
        $level = $value->level;
    }
    $str .= "</select>";
    $selectarr[] = $str;
    if($tmp[$id]->reid){
        getselecthtml($tmp, $tmp[$id]->reid, $selectarr);
    }
}

function defaultselecthtml($tmp){
    $str = '<tr><td width="90">&nbsp;所属栏目：</td><td><input type="hidden" name="joymearctypes" id="joymearctypes" value="">'
            .'<select name="arctypes" data-level="1" data-column="1"><option value="0">请选栏目</option>';
    foreach($tmp as $val){
        if($val->level != 1) continue;
        $str .= "<option value='{$val->id}'>{$val->typename}</option>";
    }
    $str .= '</select></td></tr>';
    return $str;
}