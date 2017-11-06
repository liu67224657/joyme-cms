
var water = 'watermark/1/image/aHR0cDovL2pveW1lcGljLmpveW1lLmNvbS9hcnRpY2xlL3VwbG9hZHMvMTYwODE5LzgwLTE2MFE5MUZaMzQzOC5wbmc=/dissolve/70/gravity/SouthEast/ws/0.13';
// 初始化水印选择按钮
$(function(){
	var ch = false;
	$('#thumbnailsEdit').find('img').each(function(){
		var v = $(this).attr('src');
		if(v.indexOf('watermark') > 0){
			ch = true;
		}
	});
	if(!ch && $('#thumbnailsEdit').find('img').length>0){
		$('#addwartermark').prop('checked', false);
	}
});

$('#addwartermark').click(function(){
	if($(this).prop('checked')){
		// 添加水印
		$('#thumbnailsEdit').find(':input[name^="imgurl"]').each(addwartermark);
		$('#thumbnailsEdit').find(':input[name^="imgddurl"]').each(addwartermark);
		$('#thumbnailsEdit,#thumbnails').find('img').each(function(){
			var v = $(this).attr('src');
			if(v.indexOf('watermark')==-1 && v.indexOf('?')>0){
				$(this).attr('src', v+'|'+water);
			}else if(v.indexOf('watermark')==-1){
				$(this).attr('src', v+'?'+water);
			}
		});
		var imgdata = JSON.parse($('#images').val());
		for(var i in imgdata){
			imgdata[i].imgsrc = imgdata[i].imgsrc+'?'+water;
		}
		$('#images').val(JSON.stringify(imgdata));
	}else{
		// 去除水印
		$('#thumbnailsEdit').find(':input[name^="imgurl"]').each(removewartermark);
		$('#thumbnailsEdit').find(':input[name^="imgddurl"]').each(removewartermark);
		$('#thumbnailsEdit,#thumbnails').find('img').each(function(){
			var v = $(this).attr('src');
			if(v.indexOf('watermark')>0){
				$(this).attr('src', v.substring(0,v.indexOf('watermark')-1));
			}
		});
		var imgdata = JSON.parse($('#images').val());
		for(var i in imgdata){
			var v = imgdata[i].imgsrc;
			imgdata[i].imgsrc = v.substring(0, v.indexOf('watermark')-1);
		}
		$('#images').val(JSON.stringify(imgdata));
	}
	
	function addwartermark(){
		var v = $(this).val();
		if(v.indexOf('watermark')==-1 && v.indexOf('?')>0){
			$(this).val(v+'|'+water);
		}else if(v.indexOf('watermark')==-1){
			$(this).val(v+'?'+water);
		}
	}
	
	function removewartermark(){
		var v = $(this).val();
		if(v.indexOf('watermark')>0){
			$(this).val(v.substring(0,v.indexOf('watermark')-1));
		}
	}
});

/**
** uploadimg.js 七牛图片上传
**/
var uploader = Qiniu.uploader({
    runtimes: 'html5,flash,html4',    //上传模式,依次退化
    browse_button: 'pickfiles',       //上传选择的点选按钮，**必需**
    uptoken_url: '/plus/api.php?a=getImageUptoken',
	domain: imgdomain,
		//bucket 域名，下载资源时用到，**必需**
	//container: 'container',           //上传区域DOM ID，默认是browser_button的父元素，
	max_file_size: '100mb',           //最大文件体积限制
	flash_swf_url: 'js/plupload/Moxie.swf',  //引入flash,相对路径
	max_retries: 3,                   //上传失败最大重试次数
	dragdrop: true,                   //开启可拖曳上传
	drop_element: 'container',        //拖曳上传区域元素的ID，拖曳文件或文件夹后可触发上传
	chunk_size: '4mb',                //分块上传时，每片的体积
	auto_start: true,                 //选择文件后自动上传，若关闭需要自己绑定事件触发上传
	filters: {
			mime_types : [{ title : "Image files", extensions : "jpg,jpeg,gif,png" }],
			//prevent_duplicates : true //不允许选取重复文件
		},
	init: {
		'FilesAdded': function(up, files) {
			up.idnum = imgfiles.length;
			plupload.each(files, function(file) {
				// 文件添加进队列后,处理相关的事情
			});
		},
		'BeforeUpload': function(up, file) {
			   // 每个文件上传前,处理相关的事情
		},
		'UploadProgress': function(up, file) {
			   // 每个文件上传时,处理相关的事情
		},
		'FileUploaded': function(up, file, info) {
			var domain = up.getOption('domain');
			var res = JSON.parse(info);
			console.log($('#addwartermark').prop('checked'));
			if($('#addwartermark').prop('checked')){
				var sourceLink = domain + res.key+'?watermark/1/image/aHR0cDovL2pveW1lcGljLmpveW1lLmNvbS9hcnRpY2xlL3VwbG9hZHMvMTYwODE5LzgwLTE2MFE5MUZaMzQzOC5wbmc=/dissolve/70/gravity/SouthEast/ws/0.13';
			}else{
				var sourceLink = domain + res.key;
			}
			up.idnum++;
			imgfiles.push({'imgsrc':sourceLink,'idnum':up.idnum, 'isuse':1});
			addImage(sourceLink, up.idnum);
		},
		'Error': function(up, err, errTip) {
			if(err.status == 401){
				alert('操作超时，请您刷新页面');
			}else{
				alert(errTip);
			}
		},
		'UploadComplete': function() {
			//console.log(imgfiles);
			document.getElementById('images').value = JSON.stringify(imgfiles);
			//队列文件处理完毕后,处理相关的事情
		},
		'Key': function(up, file) {
			var myDate = new Date();
			var ext = file.type.substr(file.type.indexOf('/')+1);
			var key = "article/uploads/"+myDate.getFullYear()+''+myDate.getMonth()+'/'+myDate.getDate()+''+myDate.getTime()+''+Math.round(Math.random()*1000)+'.'+ext;
			// do something with key here
			return key
		}
	}
});

function delImg(id) { 
	imgfiles[id-1].isuse = 0;
	document.getElementById('images').value = JSON.stringify(imgfiles);
} 

