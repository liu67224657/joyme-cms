// joyme wiki words js
$(function(){
	
	var tmp = location.host.split('.');
	var env = tmp.pop();
	var webcacheapi = 'http://webcache.joyme.'+env;
	
	$('#editwikiwordform').submit(function(){
		var wordObj = $(':input[name="word"]');
		var urlObj = $(':input[name="wordurl"]');
		if(wordObj.val() == ''){
			wordObj.parent().next().html('<span style="color:red;">请输入词条名称</span>');
			return false;
		}else if(urlObj.val() == ''){
			urlObj.parent().next().html('<span style="color:red;">请输入链接地址</span>');
			return false;
		}else if(!isURL(urlObj.val())){
			urlObj.parent().next().html('<span style="color:red;">请输入正确的链接地址(如:http://www.joyme.com)</span>');
			return false;
		}
	});
	
	$('#editwikiwordform').find(':input').blur(function(){
		if($(this).val() != ''){
			$(this).parent().next().html('');
		}
	});
	
	$('#cancel').click(function(){
		var word = $(':input[name="word"]').val();
		var wordurl = $(':input[name="wordurl"]').val();
		if( word || wordurl ){
			var res = confirm('是否放弃本次编辑');
			if(res){
				history.back(-1);
			}
		}else{
			history.back(-1);
		}
	});
	
	$('#selAll').click(function(){
		if($(this).prop('checked')){
			$(".words").prop('checked', true);
		}else{
			$(".words").prop('checked', false);
		}
	});
	// del
	$('#wordsDel').click(function(){
		var ids = getWordIds();
		var wikiid = $('#wikiid').val();
		if(confirm('确定要删除么？')){
			if( ids.length>0 && wikiid ){
				var data = {wikiid:wikiid,keywordIds:ids.join(',')};
				postData(data, 'del');
			}else if( ids.length == 0 ){
				alert('请选择要删除的词条');
			}
		}
	});
	// 单个删除
	$('.wordDel').click(function(){
		var id = $(this).attr('data-id');
		var wikiid = $('#wikiid').val();
		if(id && wikiid && confirm('确定要删除么？')){
			var data = {wikiid:wikiid,keywordIds:id};
			$(this).parents('tr').remove();
			postData(data, 'del');
		}
	});
	// wordsRead
	$('#wordsRead').click(function(){
		var ids = getWordIds();
		var wikiid = $('#wikiid').val();
		if(ids.length>0 && wikiid){
			var data = {wikiid:wikiid,keywordids:ids.join(','),type:1};
			postData(data, 'read');
		}else{
			alert('请选择要标记的词条');
		}
	});
	// allRead
	$('#allRead').click(function(){
		var wikiid = $('#wikiid').val();
		if(wikiid){
			var data = {wikiid:wikiid,keywordids:'',type:2};
			postData(data, 'read');
		}
	});
	
	// common fn
	function getWordIds(){
		var ids = [];
		$('.words:checked').each(function(){
			var id = $(this).val();
			if(id){
				ids.push(id);
			}
		});
		return ids;
	}
	
	function postData(data, type){
		if(type == 'del'){
			var apiurl = webcacheapi+'/wiki/keyword/delete.do';
		}else if(type == 'read'){
			var apiurl = webcacheapi+'/wiki/keyword/updatestatus.do';
		}else{
			return false;
		}
		$.ajax({
			url: apiurl,
			type: "post",
			async: false,
			data: data,
			dataType: "jsonp",
			jsonpCallback: "postDataCallBack",
			success: function(req) {
				res = req[0];
				if(res.rs == 105 ){
					return false;
				}
				if(res.rs!=1){
					alert(res.msg);
					return false;
				}
				if(type == 'del' && $('.words:checked').length>0){
					$('.words:checked').parents('tr').remove();
				}else if(type == 'read'){
					if(data.keywordids){
						$('.words:checked').parent().next().children('.redtag').remove();
					}else{
						$('.redtag').remove();
					}
				}
			},
			error: function() {}
		});
	}
});

function isURL(str){
	// return !!str.match(/(((^https?:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)$/g);
	return !!str.match(/(https?|ftp|file):\/\/[-A-Za-z0-9+&@#\/%?=~_|!:,.;]+[-A-Za-z0-9+&@#\/%=~_|]/g);
}