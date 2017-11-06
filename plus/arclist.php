<?php
require_once(dirname(__FILE__)."/../include/common.inc.php");
require_once(DEDEINC.'/channelunit.class.php');
require_once(DEDEINC.'/taglib/arcpagelist.lib.php');
  
$pnum = empty($pnum)? 0 : intval(preg_replace("/[^\d]/",'', $pnum));
$limit = empty($limit)? 4 : intval(preg_replace("/[^\d]/",'', $limit));
$typeid = empty($typeid)? 0 : intval(preg_replace("/[^\d]/",'', $typeid));

if($typeid==0 || $pnum==0) die(" Request Error! ");

if($typeid > 0)
{
    $titlelen = AttDef($titlelen,60);
    $infolen = AttDef($infolen,240);
    $imgwidth = AttDef($imgwidth,120);
    $imgheight = AttDef($imgheight,120);
    $listtype = AttDef($listtype,'all');
    $arcid = AttDef($arcid,0);
    $channelid = AttDef($channelid,0);
    $orderby = AttDef($orderby,'default');
    $orderWay = AttDef($order,'desc');
    $subday = AttDef($subday,0);
    $line = $row;
    $artlist = '';
    //通过页面及总数解析当前页面数据范围
    $strnum = ($pnum-1) * $limit;
    $limitsql = " LIMIT $strnum,$limit ";
    $innertext = '<a href="[field:arcurl/]" >
                <div class="row">
                    <div class="col-lg-3 col-md-3 col-sm-4 col-xs-4">
                        <img src="[field:litpic/]">
                    </div>
                    <div class="col-lg-9 col-md-9 col-sm-8 col-xs-8">
                        <div class="news-title">[field:title/]</div>
                        <div class="news-desc">[field:info/]...</div>
                        <div class="news-date">[field:pubdate function=\'strftime("%Y.%m.%d %H:%M",@me)\'/]</div>
                    </div>
                </div>
                </a>';//模板
          
  //处理列表内容项
    $query = "SELECT arc.*,tp.typedir,tp.typename,tp.corank,tp.isdefault,tp.defaultname,tp.namerule,tp.namerule2,tp.ispart,tp.moresite,tp.siteurl,tp.sitepath FROM `#@__archives` arc LEFT JOIN `#@__arctype` tp ON arc.typeid=tp.id
      WHERE arc.arcrank=0 and arc.typeid IN (".GetSonIds($typeid).") ORDER BY arc.pubdate desc $limitsql";
    $dsql->SetQuery($query);
    $dsql->Execute('alist');
    $dtp2 = new DedeTagParse();
    $dtp2->SetNameSpace('field', '[', ']');
    $dtp2->LoadString($innertext);
    $GLOBALS['autoindex'] = 0;
    $ids = array();
  
    for($i=0; $i<$limit; $i++)
    {
        for($j=0; $j<1; $j++)
        {
            if($row = $dsql->GetArray("alist"))
            {
                $ids[] = $row['id'];
                //处理一些特殊字段
                $row['info'] = $row['infos'] = cn_substr($row['description'],$infolen);
                $row['id'] =  $row['id'];
  
                if($row['corank'] > 0 && $row['arcrank']==0)
                {
                    $row['arcrank'] = $row['corank'];
                }
  
                $row['filename'] = $row['arcurl'] = str_replace( '/article/pc','',GetFileUrl($row['id'],$row['typeid'],$row['senddate'],$row['title'],$row['ismake'], $row['arcrank'],$row['namerule'],$row['typedir'],$row['money'],$row['filename'],$row['moresite'],$row['siteurl'],$row['sitepath']));
  
                $row['typeurl'] = GetTypeUrl($row['typeid'],$row['typedir'],$row['isdefault'],$row['defaultname'],$row['ispart'],
                $row['namerule2'],$row['moresite'],$row['siteurl'],$row['sitepath']);
  
                if($row['litpic'] == '-' || $row['litpic'] == '')
                {
                    $row['litpic'] = $GLOBALS['cfg_cmspath'].'/images/defaultpic.gif';
                }
                if(!preg_match("#^http:\/\/#", $row['litpic']) && $GLOBALS['cfg_multi_site'] == 'Y')
                {
                    $row['litpic'] = $GLOBALS['cfg_mainsite'].$row['litpic'];
                }
                $row['picname'] = $row['litpic'];
                $row['stime'] = GetDateMK($row['pubdate']);
                $row['typelink'] = "<a href='".$row['typeurl']."'>".$row['typename']."</a>";
                $row['image'] = "<img src='".$row['picname']."' border='0' width='$imgwidth' height='$imgheight' alt='".preg_replace("#['><]#", "", $row['title'])."'>";
                $row['imglink'] = "<a href='".$row['filename']."'>".$row['image']."</a>";
                $row['fulltitle'] = $row['title'];
                $row['title'] = cn_substr($row['title'],$titlelen);
                if($row['color']!='') $row['title'] = "<font color='".$row['color']."'>".$row['title']."</font>";
                if(preg_match('#b#', $row['flag'])) $row['title'] = "<strong>".$row['title']."</strong>";
                //$row['title'] = "<b>".$row['title']."</b>";
  
                $row['textlink'] = "<a href='".$row['filename']."'>".$row['title']."</a>";
  
                $row['plusurl'] = $row['phpurl'] = $GLOBALS['cfg_phpurl'];
                $row['memberurl'] = $GLOBALS['cfg_memberurl'];
                $row['templeturl'] = $GLOBALS['cfg_templeturl'];
  
                if(is_array($dtp2->CTags))
                {
                    foreach($dtp2->CTags as $k=>$ctag)
                    {
                        if($ctag->GetName()=='array')
                        {
                            //传递整个数组，在runphp模式中有特殊作用
                            $dtp2->Assign($k,$row);
                        } else {
                            if(isset($row[$ctag->GetName()])) $dtp2->Assign($k,$row[$ctag->GetName()]);
                            else $dtp2->Assign($k,'');
                       }
                    }
                    $GLOBALS['autoindex']++;
                }
                $artlist .= $dtp2->GetResult()."\r\n";
            }//if hasRow
            else {
                $artlist .= '';
            }
        }//Loop Col
    }//loop line
    $dsql->FreeResult("alist");    
} else
{
     die(" Request Error! ");
}
AjaxHead();
$ret = 'arclistquerycallback(['.json_encode($artlist).'])';
echo $ret;
exit();