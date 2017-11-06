// joyme 后台修改功能，公共js库

// 游戏查询
window.domainArr = window.location.host.split('.');
window.cmsGames = '';
var cmshost = 'http://' + window.location.host;
var apihost = 'http://api.joyme.'+window.domainArr[2];
var webcacheapi = 'http://webcache.joyme.'+window.domainArr[2];

$(function(){
	// WIKI 词条关联
	$('#wikiname').keyup(function(){
		var load = false;
		var apiurl = webcacheapi+'/wiki/title/query.do';
		var name = $(this).val();
		if( name == '' ){
			$('#wikilist').hide();
			return false;
		}else if( load ){
			return false;
		}
		load = true;
		var data = {pnum:1,psize:20,name:name};
		
		$.ajax({
			url: apiurl,
			type: "post",
			async: false,
			data: data,
			dataType: "jsonp",
			jsonpCallback: "postDataCallBack",
			success: function(req) {
				res = req[0];
				var len = res.result.rows.length;
				if( res.rs == 1 && len > 0 ){
					var list = '';
					for(var i=0; i<len; i++){
						var data = res.result.rows[i];
						list += '<span id="'+ data.wikiId +'">'+ data.wikiName +'</span>';
					}
					$('#wikilist').html(list);
					$('#wikilist').show();
					load = false;
					name = '';
					return false;
				}else{
					$('#wikilist').hide();
				}
			},
			error: function() {
				$('#wikilist').hide();
			}
		});
	});
	// 点击选择wiki
	$('#wikilist').on('click', 'span', function(){
		$('#wikilist').hide();
		var wikiid = $(this).attr('id');
		
		var ids = $('#wikiid').val();
		if(ids.indexOf(wikiid) == -1){
			$('#chwiki').append($(this).append('<em></em>'));
			ids = ids == '' ? wikiid : ids+','+wikiid;
			$('#wikiid').val(ids);
		}
	});
	// 点击删除已选
	$('#chwiki').on('click', 'span', function(){
		var wikiid = $(this).attr('id');
		$(this).remove();
		var ids = $('#wikiid').val();
		var tmp = ids.split(',');
		tmp.remove(wikiid);
		$('#wikiid').val(tmp.join(','));
	});
	
	Array.prototype.indexOf = function(val) { 
		for (var i = 0; i < this.length; i++) {
			if (this[i] == val){
				return i; 
			}
		} 
		return -1; 
	};
	
	Array.prototype.remove = function(val) { 
		var index = this.indexOf(val); 
		if (index > -1) { 
			this.splice(index, 1);
		} 
	};
	
	/**
	 **栏目联动
	**/
	$('#joymearctype').on('change', 'select', function(){
		var isnext = $(this).next('select').length;
		if(isnext){
			$(this).nextAll('select').remove();
		}
		var num = $(this).siblings('select').first().attr('data-column');
		num = num || $(this).attr('data-column');
		var str = getOpArcType($(this).val(), $(this).attr('data-level'), num);
		if(str){
			$(this).after(str);
			return;
		}
		// 记录数据
		var typeids = new Array();
		$('#joymearctype tr').each(function(){
			var tid = $(this).find('select').last(':selected').val();
			if($.inArray(tid, typeids) == -1 && tid != undefined){
				typeids.push(tid);
			}
		});
		$("#joymearctypes").val(typeids.join(','));
	});
	// 添加一列
	$('#joymearctypeadd').click(function(){
		var op = '';
		for(var i in joymearctypes){
			if(joymearctypes[i].level != 1) continue;
			op += "<option value='"+joymearctypes[i].id+"'>"+joymearctypes[i].typename+"</option>";
		}
		var num = $('#joymearctype tr').length;
		var str = "<tr><td></td><td><select name='arctypes' data-level='1' data-column='"+num+"'><option value='0'>请选栏目</option>"+op+"</select><button class='joymearctypedel' type='button'>X</button>点击删除此列</td></tr>";
		$('#joymearctype>tbody').children(":last").before(str);
	});
	// 删除一列
	$('#joymearctype').on('click', '.joymearctypedel', function(){
		if(!window.confirm('你确定要删除吗？')){
            return false;
        }
		var seid = $(this).siblings('select').last(':selected').val();
		$(this).parent().parent().remove();
		// 记录数据
		var typeids = new Array();
		$('#joymearctype tr').each(function(){
			var tid = $(this).find('select').last(':selected').val();
			if($.inArray(tid, typeids) == -1 && tid != undefined){
				typeids.push(tid);
			}
		});
		$("#joymearctypes").val(typeids.join(','));
	});
	
	/**
	 **tag 标签处理
	**/
	$('#showTags').click(function(){
		var x = parseInt((document.body.clientWidth-600)/2);
		var y = parseInt((document.body.clientHeight-300)/2);
		if($('.tagbox').length>0){
			$('.tagbox').show();
			return false;
		}
		var tags = getAllTags();
		var tablehtml = '<table><tr>';
		for(var i in tags){
			if(i == 'remove')continue;
			tablehtml += '<td>'+tags[i].tag+'</td>';
			if(i!=0 && i%8 ==0) tablehtml += '</tr><tr>';
		}
		tablehtml += '<tr></table>';
		var dialog = '<style type="text/css">.tagbox{border:1px solid #c0c0c0;background:#FFF8DC;height:300px;width:600px;z-index:1000;top:45%;left:'+x+'px;position:absolute;padding:5px;}.tagbox span{float:right;cursor:pointer;}.tagbox table tr td{border:1px solid #c0c0c0;cursor:pointer;background:#BFBFBF;pading:2px;}</style><div class="tagbox"><div>标签搜索:<input type="text" id="tagsearch" name="tagsearch"><button type="button" id="tagsearchbut">搜索</button> <span id="closstagsearch">关闭</span></div><div id="taglist">'+tablehtml+'</div></div>';

		$('body').append(dialog);
		
		$('.tagbox').on('click', 'td', function(){
			var tagval = $('#tags').val();
			var seval = $(this).html();
			if(tagval == ''){
				$('#tags').val(seval);
				return false;
			}
			var tmp = new Array();
			tmp = tagval.split(',');
			if($.inArray(seval, tmp) == -1){
				tmp.push(seval);
			}
			$('#tags').val(tmp.join(','));
		});
		
		$('.tagbox').on('click', '#tagsearchbut', function(){
			var searchkey = $("#tagsearch").val();
			var tags = getAllTags(searchkey);
			var tablehtml = '<table><tr>';
			for(var i in tags){
				if(i == 'remove')continue;
				tablehtml += '<td>'+tags[i].tag+'</td>';
				if(i!=0 && i%8 ==0) tablehtml += '</tr><tr>';
			}
			tablehtml += '<tr></table>';
			$("#taglist").html(tablehtml);
		});
		
		$('.tagbox').on('click', 'span', function(){
			$('.tagbox').hide();
		});
	});
	
	/**
	 **游戏关联
	**/
	$('#searchgamebutton').click(function(){
		var gameName = $("#gameName").val();
		joymeFindGame(gameName);
	});
	
	$('#checkgames').on('click', 'span', function(){
		$(this).remove();
		var ids = new Array();
		$('#checkgames span').each(function(){
			ids.push($(this).attr('data-id'));
		});
		$('#gameids').val(ids.join(','));
	});
	
	// 快速添加游戏
	$('#addgame').on('click', function(){
		//var gametype = $("#gametype").find("option:selected").val();
		var gameplatform = new Array();
		$.ajax({
			url: apihost+'/collection/api/gamearchive/getgameplatform',
			// url: 'http://172.16.77.77:8080/collection/api/gamearchive/getgameplatform',
			type: "post",
			async: false,
			data: {},
			dataType: "jsonp",
			jsonpCallback: "gameplatforms",
			success: function (req) {
				data = req[0];
				if(data.rs == 1){
					//gameplatform = data.result;
					showplatform(data.result);
					$("#queding").on("click", platformsure);
				}else{
					alert(data.msg);
				}
			},
			error: function () {
				alert('gameplatforms程序错误');
			}
		});
	});
});

// 游戏平台选择确定按钮
function platformsure(){
	var ptype = ['sj', 'dn', 'zj', 'ds'];
	// var gametype = $("#gametype").find("option:selected").val();
	var name = $.trim($(":input[name='gamename']").val());
	if(name == ''){
		alert('游戏名不能为空');
		return false;
	}
	var platform = {};
	$.each(ptype, function(i, n){
		var data = new Array();
		$(":checkbox[name='"+n+"']:checked").each(function(){
			data.push($(this).val());
		});
		platform[n] = data;
	});
	// if(gametype == 'mobile' && $.isEmptyObject(platform.sj)){
		// alert('类型选择不能为空');
		// return false;
	// }
	if($.isEmptyObject(platform.sj) && $.isEmptyObject(platform.dn) && $.isEmptyObject(platform.zj) && $.isEmptyObject(platform.ds)){
		alert('类型选择不能为空');
		return false;
	}
	$("#mdialog").hide();
	addgame(platform, name);
}

// 添加新游戏
function addgame(platform, name){
	$.ajax({
		url: apihost+'/collection/api/gamearchive/creategame',
		// url: 'http://172.16.77.77:8080/collection/api/gamearchive/creategame',
		type: "post",
		async: false,
		data: {gamename:name, mplatform:platform.sj.join(','),
			pcplatform:platform.dn.join(','),
			pspplatform:platform.zj.join(','),
			tvplatform:platform.ds.join(',')},
		dataType: "jsonp",
		jsonpCallback: "addgameback",
		success: function (req) {
			data = req[0];
			if(data.rs==1 || data.rs == '-90001'){
				addgamerelation(data.result.gameId, data.result.gameName);
			}else{
				alert(data.msg);
			}
			
		},
		error: function () {
			alert('addgame程序错误');
		}
	});
}

// 展示游戏平台
function showplatform(gameplatform){
	var html = '';
	var ptype = ['sj', 'dn', 'zj', 'ds'];
	var zhtype = ['手机游戏', '电脑游戏', '掌机游戏', '电视游戏'];
	// var gametype = $("#gametype").find("option:selected").val();
	$.each(gameplatform, function(key, val){
		// if(gametype == 'mobile' && key !=1){
			// var style = ' style="display:none;" ';
		// }else if(gametype == 'other' && key ==1){
			// var style = ' style="display:none;" ';
		// }else{
			// var style = ' style="display:block;" ';
		// }
		html += '<tr><td>'+zhtype[key-1]+'：</td><td>';
		$.each(val, function(i, n){
			html += '<input type="checkbox" name="'+ptype[key-1]+'" class="'+ptype[key-1]+'" value="'+n.code+'">'+n.desc;
		});
		html += '</td></tr>';
	});
		
	var tablehtml = '<table><tr><td>游 戏 名：</td><td><input type="text" name="gamename"></td></tr><tr><td>平    台：</td><td></td></tr>'+html+'<tr><td><button id="queding" type="button">确定</button></td><td></td></tr></table>';
	
	$("#dbox").html(tablehtml);
	$("#mdialog").show();
}

// 多级联动选项拼接
function getOpArcType(val, level, num){
	level = parseInt(level);
	var op = '';
	for(var i in joymearctypes){
		if(joymearctypes[i].level != level+1 || joymearctypes[i].reid != val) continue;
		op += "<option value='"+joymearctypes[i].id+"'>"+joymearctypes[i].typename+"</option>";
	}
	var str = '';
	if(op != ''){
		str = "<select name='arctypes' data-level='"+(level+1)+"' data-column='"+num+"'><option value='0'>请选栏目</option>"+op+"</select>";
	}
	return str;
}

// 获取tags
function getAllTags(searchkey){
	searchkey = searchkey || '';
	var data = new Array();
	$.ajax({
		url: cmshost+'/plus/api.php?a=getAllTags&searchkey='+searchkey,
		type: "post",
		async: false,
		data: {},
		dataType: "jsonp",
		jsonpCallback: "getalltags",
		success: function (req) {
			data = req;
		},
		error: function () {
			alert('joymeFindGame程序错误');
		}
	});
	return data
}

function joymeFindGame(gameName){
	if(gameName == '') return;
	$.ajax({
		url: apihost+'/collection/api/gamearchive/searchgame',
		type: "post",
		async: false,
		data: {searchtext:gameName},
		dataType: "jsonp",
		jsonpCallback: "getGameMsg",
		success: function (req) {
			var resMsg = req[0];
			if(resMsg.rs == 1 && resMsg.result){
				setGamesSelectBox(resMsg.result);
			};
		},
		error: function () {
			alert('joymeFindGame程序错误');
		}
	});
}

function setGamesSelectBox(data){
	//var x = parseInt((document.body.clientWidth-600)/2);
	//var y = parseInt((document.body.clientHeight-300)/2);
	var tablehtml = '<table><tr>';
	for(var i in data){
		if(i == 'remove')continue;
		tablehtml += '<td data-id="'+data[i].gameId+'">'+data[i].gameName+'</td>';
		if(i!=0 && i%8 ==0) tablehtml += '</tr><tr>';
	}
	tablehtml += '<tr></table>';
	$("#dbox").html(tablehtml);
	$("#mdialog").show();
	$('#dbox').find('td').unbind("click", addgamerelation);
	$('#dbox').find('td').bind("click", addgamerelation);
}

// 关闭弹窗
function xdialog(){
	$("#mdialog").hide();
}
// 点击确定，添加所选游戏
function addGames(id){
	if(window.cmsGames == '') return false;
	var checkedGames = $('input[type="checkbox"][name="games[]"]:checked');
	var gameIdArr = new Array();
	$('input[type="checkbox"][name="games[]"]:checked').each(function(){
		gameIdArr.push($(this).val());
	});
	if(gameIdArr == ''){
		alert('没有任何选择');
		return false;
	}
	$('#gameids').val(gameIdArr.join(','));
	var arr = new Array();
	for(var i in window.cmsGames){
		if($.inArray(String(window.cmsGames[i].gameId), gameIdArr) != -1){
			arr.push('<span>'+resMsg.result[i].gameName+'('+resMsg.result[i].gameId+')'+'[X]</span>');
		}
	}
	$('#checkgames').html(arr.join(','));
	joymeCancel(id);
}

// 添加游戏关联
function addgamerelation(gameid, gamename){
	var gameid = $(this).attr('data-id') || gameid ;
	var gamename = gamename || $(this).html();
	var html = '<span data-id="'+gameid+'">'+gamename+'[X]</span>';
	var oldids = $('#gameids').val();
	var oldidsarr = oldids.split(',');
	var oldhtml = $('#checkgames').html();
	$('#gameids').val(gameid);
	$('#checkgames').html(html);
	/*if(oldids == ''){
		$('#gameids').val(gameid);
		$('#checkgames').html(html);
	}else if($.inArray(gameid, oldidsarr) == -1){
		$('#gameids').val(oldids+','+gameid);
		$('#checkgames').html(oldhtml+','+html);
	}*/
}
// 取消游戏关联
function delgamerelation(){
	
}

// 取消按钮
function joymeCancel(id){
	$('#'+id).remove();
}


