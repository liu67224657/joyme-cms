<?php

/**
* joyme base class
**/

class JoymeBase{
	public function __construct(){}
	
	public function getPageListHtml($data, $url){
		$listhtml = '';
		if($data['maxPage'] > 1){
			if(!$data['firstPage']){
				$listhtml .= '<a href="'.$url.'">首页</a>';
				$listhtml .= '<a href="'.$url.'&pnum='.($data['curPage']-1).'">上一页</a>';
			}
			
			foreach($data['displayingPages'] as $val){
				if($val == $data['curPage']){
					$listhtml .= '<span class="on">'.$val.'</span>';
				}else{
					$listhtml .= '<a href="'.$url.'&pnum='.$val.'">'.$val.'</a>';
				}
			}
			
			if(!$data['lastPage']){
				$listhtml .= '<a href="'.$url.'&pnum='.($data['curPage']+1).'">下一页</a>';
				$listhtml .= '<a href="'.$url.'&pnum='.$data['maxPage'].'">尾页</a>';
			}
		}
		$listhtml .= "<span>共{$data['maxPage']}页{$data['totalRows']}条</span>";
		return $listhtml;
	}
}