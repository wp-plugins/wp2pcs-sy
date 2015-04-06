<?php

/*
*
* # 这个文件是用来实现从百度网盘获取附件列表，并让站长可以选择插入到文章中
* # http://wordpress.stackexchange.com/questions/85351/remove-other-tabs-in-new-wordpress-media-gallery
*

http://sumtips.com/2012/12/add-remove-tab-wordpress-3-5-media-upload-page.html
https://gist.github.com/Fab1en/4586865
http://wordpress.stackexchange.com/questions/76980/add-a-menu-item-to-wordpress-3-5-media-manager
http://cantina.co/2012/05/15/tutorial-writing-a-wordpress-plugin-using-the-media-upload-tab-2/
http://wordpress.stackexchange.com/questions/76980/add-a-menu-item-to-wordpress-3-5-media-manager
http://stackoverflow.com/questions/5671550/jquery-window-send-to-editor
http://wordpress.stackexchange.com/questions/50873/how-to-handle-multiple-instance-of-send-to-editor-js-function
http://codeblow.com/questions/jquery-window-send-to-editor/
http://wordpress.stackexchange.com/questions/85351/remove-other-tabs-in-new-wordpress-media-gallery
*/

function wp_storage_to_pcs_add_help_page_netdisk() {
	global $wp2pcs_page_netdisk;
	$screen = get_current_screen();
	if ( $screen->id != $wp2pcs_page_netdisk )
        return;
	$screen->add_help_tab( array(
		'id'		=> 'wp2pcs-sy-netdisk',
		'title'		=> '说明',
		'content'	=> '<p>
			<ul>
				<li>点击之后背景变绿的是图片，变红的是链接，变蓝的是视频，变紫的是音乐。点击上传按钮会进入你的网盘目录，你上传完文件之后，再点击刷新按钮就可以看到上传完成后的图片。当你进入多个子目录之后，点击返回按钮返回网盘存储根目录。</li>
				<li>本插件的本地上传功能比较弱，请在网盘中上传（客户端或网页端都可以），完成之后请点击刷新按钮以查看新上传的文件。</li>
				<li>最后，强烈建议文件名、文件夹名使用常规的命名方法，不包含特殊字符，尽可能使用小写字母，使用-作为连接符，使用小写扩展名，由于命名特殊引起的问题，请自行排查。</li>
			</ul>
		</p>'
	) );
	$screen->add_help_tab( array(
		'id'		=> 'wp2pcs-sy-orderby',
		'title'		=> '排序',
		'content'	=> '<p><ul><li>选定排序类型后需要点击“刷新”按钮才会安装新的排序规则排序。</li><li>默认排序规则为按修改时间倒排序。</li></ul></p>'
	) );
}
// 在上面产生的百度网盘选项中要显示出网盘内的文件
//add_action('media_upload_file_from_pcs','wp_storage_to_pcs_media_tab_box');
function wp_storage_to_pcs_file_manage() {
	// 当前路径相关信息
	$remote_dir = get_option('wp_storage_to_pcs_remote_dir');	
	if(isset($_GET['dir']) && !empty($_GET['dir'])){
		$dir_pcs_path = str_replace('\\','',$_GET['dir']);
	}else{
		$dir_pcs_path = $remote_dir;
	}
	$access_token = get_option('wp_to_pcs_access_token');
	if(isset($_GET['orderby']) && !empty($_GET['orderby']))
		$orderby = $_GET['orderby'];
	else
		$orderby = 'time-desc';
?>
<style>
a{text-decoration:none;}
/*html,body{background-color:#fff;background-attachment:fixed;}
#opt-on-pcs-tabs{padding:0 1em 0 1em;border-bottom:1px solid #dedede;margin-bottom:1em;font-size:1.1em;
	width:100%;
	position:fixed;
	_position:absolute;
	left:0;
	top:0;
	_top:expression(documentElement.scrollTop);
	background:#fff;
}
#opt-on-pcs-tabs .right{margin-right:3em;}
#files-on-pcs{margin:10px;}*/
.file-on-pcs{width:120px;height:128px;overflow:hidden;float:left;margin:5px;padding:2px;}
.file-thumbnail{width:120px;height:96px;overflow:hidden;text-align:center;vertical-align:middle;display:table-cell;}/*background-color:#ccc;*/
/*.file-type-dir .file-thumbnail{background-color:#FDCE5F;}box-shadow: 0px 0px 0px 1px rgba(0, 0, 0, 0.1) inset;
.file-type-video .file-thumbnail{background-color:#000000;}
.file-type-audio .file-thumbnail{background-color:#8A285C;}background: none repeat scroll 0% 0% rgba(255, 255, 255, 0.8);*/
.file-thumbnail img{max-width:100%;height:auto;vertical-align:middle;}
.file-name{line-height:1em;margin-top:3px;text-align:center;word-break:break-all;}
.selected{background-color:#008000;color:#fff;}
.selected-file{background-color:#A30000;color:#fff;}
.selected-video{background-color:#2E2EFF;color:#fff;}
.selected-audio{background-color:#FF00FF;color:#000000;}
.opt-area{margin:0 10px;padding-bottom:20px;}
.alert{color:#D44B25;margin:0 10px;padding-bottom:20px;}
.hidden{display:none;}
#upload-to-pcs{text-align:center;padding-top:150px;}
.page-navi{font-size:14px;text-align:center;background-color:#E62114;}
.page-navi a{color:#fff;text-decoration:none;}
#prev-page{padding:5px;}
#next-page a{padding:5px;display:block;}
#next-page a:hover{background-color:#1BA933;}
/*#prev-page a:hover{background-color:#1BA933;}*/
#rename-file{width:118px;height:16px;line-height:16px;border:0;background:#fff;padding:0;}
#back-to-top-btn{
	position:fixed;
	display:block;
	bottom:100px;
	right:10px!important;
	width:50px;
	line-height:50px;
	border:1px solid #aaa;
	text-align:center;
	vertical-align:middle;
	background-color:#eee;
	filter:alpha(opacity=90);
	opacity:0.9;
}
#back-to-top-btn:hover{background-color:#1BA933;color:#fff;filter:alpha(opacity=60);opacity:0.6;}
</style>
<script>
jQuery(function($){
	// 选择要插入的附件
	$('#files-on-pcs div.can-select').live('click',function(e){
		$('div.selected').each(function(){
			$('.selected').removeClass('selected');
			$('.selected-video').removeClass('selected-video');
			$('.selected-audio').removeClass('selected-audio');
			$('.selected-file').removeClass('selected-file');
		});
		var $this = $(this),
			$file_type = $this.attr('data-file-type');
		//if($('#rename-file').is(":visible"))return;
		$this.toggleClass('selected');
		if($file_type == 'file'){
			$(this).toggleClass('selected-file');
		}else if($file_type == 'video'){
			$(this).toggleClass('selected-video');
		}else if($file_type == 'audio'){
			$(this).toggleClass('selected-audio');
		}
	});
	// 调整图片信息
	/*$('.selected .file-name').live('click',function(){
		var $this = $(this),
			$text = $this.text();
		if($('#rename-file').is(":visible"))$text = $('#rename-file').val();
		$this.html('<input type="text" value="'+$text+'" id="rename-file" />');
		$('#rename-file').focus();
	});
	$('#rename-file').live('focusout',function(){
		var $this = $('#rename-file'),
			$fileName = $this.parent(),
			$text = $this.val();
		if($text==''){
			$text = $fileName.parent().attr('data-file-name');
		}else{
			$fileName.parent().attr('data-file-name',$text);
		}
		$fileName.text($text);
	}).live('keypress',function(e){
		var e = document.all ? window.event : e;
		if(e.keyCode == "13"){
			$(this).trigger('focusout');
		}
	});*/
	// 点击关闭按钮
	/*$('#close-btn').click(function(){
		window.parent.tb_remove();
	});*/
	// 清除选择的图片
	$('#clear-btn').click(function(){
		$('.selected').removeClass('selected');
		$('.selected-video').removeClass('selected-video');
		$('.selected-audio').removeClass('selected-audio');
		$('.selected-file').removeClass('selected-file');
	});
	// 选择排序
	$(':radio[name="wp2pcs-file-orderby"]').click(function(){
		var orderby = $('input[name="wp2pcs-file-orderby"]:checked').val();
		var ah = window.location.href;
		var newquery;
		if(ah.indexOf("orderby")>0)
			newquery = '';
		else
			newquery = 'orderby=' + orderby;
		var query_str = ah.substring(ah.indexOf("?")+1,ah.length).split(/&/);
		for (var i=0; i<query_str.length ; i++ ){
			if(query_str[i].substring(0,query_str[i].indexOf('=')) == 'orderby')
				newquery = newquery + 'orderby=' + orderby;
			else {
				if(newquery == '')
					newquery = query_str[i];
				else
					newquery = newquery + '&' + query_str[i];
			}
		};
		$("#reflush").attr("href",ah.substring(0,ah.indexOf("?") + 1) + newquery);
	});
	// 点击上传按钮
	$('#upload-to-pcs-submit').click(function(){
		var $upload_path = '<?php echo urlencode($dir_pcs_path); ?>/',
			$file_name = $('#upload-to-pcs-input').val().match(/[^\/|\\]*$/)[0],
			$action = 'https://pcs.baidu.com/rest/2.0/pcs/file?method=upload&access_token=<?php echo get_option("wp_to_pcs_access_token"); ?>&ondup=newcopy&path=' + $upload_path + encodeURIComponent($file_name);
		<?php if(strpos(get_option('wp_storage_to_pcs_image_perfix'),'?') !== false && 0) : // 关闭中文监测 ?>
		if(/.*[\u4e00-\u9fa5]+.*$/.test($file_name)){
			alert('不支持含有汉字的图片名');
			return false;
		}
		<?php endif; ?>
		if($file_name != ''){
			$('#upload-to-pcs-refresh').addClass('hidden');
			$('#upload-to-pcs-from').attr('action',$action).submit();
			$('#upload-to-pcs-processing').removeClass('hidden');
			$is_uploading = setInterval(function(){
				$('#upload-to-pcs-window').load(function(){
					$('#upload-to-pcs-refresh').removeClass('hidden');
					$('#upload-to-pcs-processing').addClass('hidden');
					var $href = window.location.href;
					window.location.href = $href;
					clearInterval($is_uploading);
				});
			},500);
		}
	});
	// 点击切换到上传面板
	$('#show-upload-area').toggle(function(e){
		e.preventDefault();
		$('#files-on-pcs,#next-page,#prev-page,#opt-area,#manage-buttons').hide();
		$('#upload-to-pcs').show();
		$(this).text('返回列表');
	},function(e){
		e.preventDefault();
		$('#upload-to-pcs').hide();
		$('#files-on-pcs,#next-page,#prev-page,#opt-area,#manage-buttons').show();
		$(this).text('上传到这里');
	});
	// 点击下一页
	$('#next-page a').live('click',function(e){
		e.preventDefault();
		var $this = $(this),
			$href = $this.attr('href'),
			$loading = $this.attr('data-loading');
		if($loading=='true')return;
		$.ajax({
			url:$href,
			dataType:'html',
			beforeSend:function(){
				$this.text('正在加载...');
				$this.attr('data-loading','true');
			},
			success:function(data){
				var getHtml = $(data),
					getCode = $('<code></code>').append(getHtml),
					getList = $('#files-on-pcs',getCode).html(),
					getNextPage = $('#next-page',getCode),
					//getPrevPage = $('#prev-page',getCode),
					/*getList = $('#files-on-pcs',getHtml).html(),
					getNextPage = $('#next-page',getHtml),*/
					nextPageLink = $('a',getNextPage).attr('href');
				if(getList == undefined) {
					$this.text('加载失败，重新加载下一页');
					$this.attr('data-loading','false');
				}else {
					$('#files-on-pcs').append('<hr style="border:0;background:#ccc;height:2px;margin:10px 0;clear:both;" />' + getList);
					if(nextPageLink != undefined){
						$this.attr('href',nextPageLink);
						$this.text('下一页');
						$this.attr('data-loading','false');
					}else{
						$('#next-page').hide().remove();
					}
				}
			}
		});
	});
});
</script>
<div class="wrap" id="wp2pcs-file-dashbord">
	<h2>WP2PCS-SY 百度网盘文件浏览</h2>
	<div class="media-toolbar wp-filter" id="opt-on-pcs-tabs">
		<p>当前位置：<a href="<?php echo remove_query_arg(array('dir','paged')); ?>">HOME</a><?php
		if(isset($_GET['dir']) && !empty($_GET['dir'])){
			$current_path = str_replace($remote_dir,'',$dir_pcs_path);
			$current_dir_string = array();
			$current_path_arr = array_filter(explode('/',$current_path));
			if(!empty($current_path_arr))foreach($current_path_arr as $key => $current_dir){
				$current_dir_string[] = urlencode($current_dir);
				$current_dir_link = implode('/',$current_dir_string);
				$current_dir_link = add_query_arg('dir',$remote_dir.$current_dir_link);
				$current_dir_link = '/<a href="'.$current_dir_link.'">'.$current_dir.'</a>';
				echo $current_dir_link;
			}
		}
		?> <?php if((is_multisite() && current_user_can('manage_network')) || (!is_multisite() && current_user_can('edit_theme_options'))): ?><a href="#upload-to-pcs" class="button-primary right" id="show-upload-area">上传到这里</a><?php endif; ?></p>
		<p id="manage-buttons">
			<button id="clear-btn" class="button">取消选择</button>
			<!--button id="close-btn" class="button">关闭</button-->
			<?php if($access_token != 'false') : ?><a href="http://pan.baidu.com/disk/home#dir/path=<?php echo urlencode($dir_pcs_path); ?>" target="_blank" class="button">文件管理</a><?php endif; ?>
			<a href="" class="button" id="reflush">刷新</a>
			<span style="margin-left:30px;">
			排序：<input type="radio" name="wp2pcs-file-orderby" value="time-desc" <?php checked($orderby == 'time-desc'); ?>>时间倒排</input>
			<input type="radio" name="wp2pcs-file-orderby" value="time-asc" <?php checked($orderby == 'time-asc'); ?>>时间顺排</input>
			<input type="radio" name="wp2pcs-file-orderby" value="name-desc" <?php checked($orderby == 'name-desc'); ?>>文件名倒排</input>
			<input type="radio" name="wp2pcs-file-orderby" value="name-asc" <?php checked($orderby == 'name-asc'); ?>>文件名顺排</input></span>
		</p>
		<div class="clear"></div>
	</div>
	<div id="files-on-pcs">
	<?php
		if(isset($_GET['paged']) && is_numeric($_GET['paged']) && $_GET['paged'] > 1){
			$paged = $_GET['paged'];
		}else{
			$paged = 1;
		}
		$files_per_page = 7*5;// 每行7个，行数可以自己修改
		$limit = (($paged-1)*$files_per_page).'-'.($paged*$files_per_page);
		$files_on_pcs = wp_storage_to_pcs_media_list_files($dir_pcs_path,$limit,$orderby);
		$files_count = count($files_on_pcs);
		//print_r($files_on_pcs);
		if(!empty($files_on_pcs))foreach($files_on_pcs as $file){
			$file_path = explode('/',$file->path);
			$file_name = $file_path[count($file_path)-1];
			$file_ext = substr($file_name,strrpos($file_name,'.')+1);
			$file_type = strtolower($file_ext);
			for($i=0;$i<count($file_path);$i++){
				$file_path[$i] = urlencode($file_path[$i]);
			}
			$file->path = implode('/',$file_path);
			$link = false;
			$thumbnail = false;
			$class = '';
			// 判断是否为图片
			if(in_array($file_type,array('jpg','jpeg','png','gif','bmp'))){
				$thumbnail = wp_storage_to_pcs_media_thumbnail($file->path);
				$file_type = 'image';
			}
			// 判断是否为视频
			elseif(in_array($file_type,array('asf','avi','flv','mkv','mov','mp4','wmv','3gp','3g2','mpeg','ts','rm','rmvb','m3u8'))){
				$file_type = 'video';
				$class .= ' file-type-video ';
			}
			// 判断是否为音频
			elseif($file_type == 'mp3'){ //array('ogg','mp3','wma','wav','mp3pro','mid','midi')
				$file_type = 'audio';
				$class .= ' file-type-audio ';
			}
			else{
				$file_type = 'file';
			}
			// 判断是否为文件（图片）还是文件夹
			if($file->isdir === 0){
				$class .= ' file-type-file can-select ';
			}else{
				$class .= ' file-type-dir ';
				$link = true;
				$file_type = 'dir';
			}
			if($link)echo '<a href="'.add_query_arg('dir',$file->path).'">';
			echo '<div class="file-on-pcs'.$class.'" data-file-name="'.$file_name.'" data-file-type="'.$file_type.'" data-file-path="'.$file->path.'">';
			echo '<div class="file-thumbnail">';
			if($thumbnail)echo '<img src="'.$thumbnail.'?thumbnail=true" />';
			elseif($file_type == 'dir')echo '<img src="'.plugins_url('asset/folder.png',WP2PCS_PLUGIN_NAME).'" />';
			elseif($file_type == 'video')echo '<img src="'.plugins_url('asset/video.png',WP2PCS_PLUGIN_NAME).'" />';
			elseif($file_type == 'audio')echo '<img src="'.plugins_url('asset/audio.png',WP2PCS_PLUGIN_NAME).'" />';
			else echo '<img src="'.plugins_url('asset/archive.png',WP2PCS_PLUGIN_NAME).'" />';
			echo '</div>';
			echo '<div class="file-name">';
			echo $file_name;
			echo '</div>';
			echo '</div>';
			if($link)echo '</a>';
		}
	?>
	</div>
	<div style="clear:both;"></div>
	<div id="upload-to-pcs" class="hidden">
		<form name="input" action="#" method="post" target="upload-to-pcs-window" enctype="multipart/form-data" id="upload-to-pcs-from">
			<input type="file" name="select" id="upload-to-pcs-input" />
			<input type="button" value="上传" class="button-primary" id="upload-to-pcs-submit" />
			<a href="" class="button hidden" id="upload-to-pcs-refresh">成功，刷新查看</a>
			<img src="<?php echo plugins_url('asset/loading.gif',WP2PCS_PLUGIN_NAME); ?>" class="hidden" id="upload-to-pcs-processing" />
		</form>
		<iframe name="upload-to-pcs-window" id="upload-to-pcs-window" style="display:none;"></iframe>
	</div>
	<div class="opt-area" id="opt-area">
		<?php
		if($paged > 1){
			echo '<p id="prev-page" class="page-navi">';
			echo '<a href="'.remove_query_arg('paged').'">第一页</a>';
			echo '<a href="'.add_query_arg('paged',$paged-1).'">上一页</a></p>';
		}
		if($files_count >= $files_per_page)echo '<p id="next-page" class="page-navi"><a href="'.add_query_arg('paged',$paged+1).'">下一页</a><p>';
		?>
	</div>
	<a href="javascript:void(0)" title="返回顶部">
		<div id="back-to-top-btn" onclick="jQuery('html,body').animate({scrollTop:0},500)">回顶部</div>
	</a>
</div>
<?php
}


function wp_storage_to_pcs_clear_tmpfile($re) {
	$re = str_replace('/','_',get_option('wp_storage_to_pcs_remote_dir')).$re;
	$re = '/^'.str_replace('?', '.?', str_replace('*', '.*', str_replace('.', '\.', $re))).'/i';
	$tmp_dir = trailing_slash_path(WP2PCS_TMP_DIR,WP2PCS_IS_WIN);

	//遍历当前目录下所有文件
	$all_files = scandir($tmp_dir);
	$result = 0;
	foreach($all_files as $filename){
		//跳过当前目录和上一级目录
		if(in_array($filename,array('.', '..'))) continue;

		//进入到$filename文件夹下
		$full_name = $tmp_dir.$filename;

		//判断当前路径是否是一个文件夹
		//否则判断文件类型,匹配则删除
		if(is_dir($full_name)) continue;
		elseif(preg_match($re,$filename)) {
			@unlink($full_name);
			$result++;
			//$result = $full_name;
		}
	}
	return $result;
}

function wp_storage_to_pcs_get_file_count() {
	// 计算临时文件数量
	$all_files = scandir(WP2PCS_TMP_DIR);
	$tmpfile_perfix = str_replace('/', '_', get_option('wp_storage_to_pcs_remote_dir'));
	$tmp_file_count = count(preg_grep("/^$tmpfile_perfix.*/i", $all_files));
	//$thumbnail_count = count(preg_grep("/^$tmpfile_perfix.*\.thumbnail$/i", $all_files));
	return $tmp_file_count;// array($tmp_file_count,$thumbnail_count);
}

// 添加清楚缓存动作
add_action('admin_init','wp_to_pcs_action_clear_cache');
function wp_to_pcs_action_clear_cache() {
	// 权限控制
	if(is_multisite() && !current_user_can('manage_network')){
		return;
	}elseif(!current_user_can('edit_theme_options')){
		return;
	}
	if(!empty($_POST) && isset($_POST['page']) && $_POST['page'] == $_GET['page'] && isset($_POST['action']) && $_POST['action'] == 'wp_storage_to_pcs_cache_clear'){
		check_admin_referer();
		$result = 0;
		if(isset($_POST['wp_storage_to_pcs_cache_clear_all']) && $_POST['wp_storage_to_pcs_cache_clear_all'] == '删除全部'){
			$result = wp_storage_to_pcs_clear_tmpfile('*$');
		}
		elseif(isset($_POST['wp_storage_to_pcs_cache_clear_re']) && $_POST['wp_storage_to_pcs_cache_clear_re'] == '删除指定文件')
			$result = wp_storage_to_pcs_clear_tmpfile(trim($_POST['wp_storage_to_pcs_cache_clear_re_input']).'$');
		/*elseif(isset($_POST['wp_storage_to_pcs_cache_clear_thumbnail']) && $_POST['wp_storage_to_pcs_cache_clear_thumbnail'] == '删除缩略图'){
			$result = wp_storage_to_pcs_clear_tmpfile('*.thumbnail$');
		}*/
		wp_redirect(wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page'].'&cl='.$result.'&time='.time());
		exit;
	}
}

// 下面是后台控制面板
function wp_storage_to_pcs_cache_panel(){
	$tmp_file_count = wp_storage_to_pcs_get_file_count();
?>
<?php if (isset($_GET['cl']) && $_GET['cl'] != '0') : //?>
	<div id="message" class="updated"><p><?php echo '已成功删除'.$_GET['cl'].'个缓存文件。'; ?></p></div>
<?php elseif (isset($_GET['cl']) && $_GET['cl'] == '0') :?>
	<div id="message" class="updated"><p><?php echo '没有相匹配的文件被删除。';?></p></div>
<?php endif; ?>
<div class="postbox" id="wp-to-pcs-cache-form">
	<h3>缓存文件清理 <a href="javascript:void(0)" class="tishi-btn" id="wp-to-pcs-storage-clear-tishi-btn">+</a></h3>	
	<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
	<form method="post">
		<p>PCS文件缓存目录：<?php echo WP2PCS_TMP_DIR; ?>，缓存文件数量：<?php echo $tmp_file_count;?><!--，其中缩略图:<?php echo $tmp_file_count[1];?>--></p>
		<p>删除缓存文件：<?php echo str_replace('/','_',get_option('wp_storage_to_pcs_remote_dir'));?><input type="text" name="wp_storage_to_pcs_cache_clear_re_input" value="" />（支持通配符*?）
		<p class="tishi hidden">*代表零个或多个字符，？代表一个字符。请勿输入其它正则表达式字符，防止删除非预期的文件。</p>
		<p class="tishi hidden">缓存文件的文件名是以百度网盘中的完整路径名，并把‘/’替换成‘_’，添加后缀'.tmp'<!--(缩略图添加的后缀为‘.thumbnail)-->来命名的。</p>
		<p>
			<input type="submit" name="wp_storage_to_pcs_cache_clear_all" value="删除全部" class="button-primary" onclick="if(!confirm('确定要删除全部缓存文件吗？'))return false;" />&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="submit" name="wp_storage_to_pcs_cache_clear_re" value="删除指定文件" class="button-primary" onclick="if(!confirm('确定要删除指定规则的文件吗？'))return false;" /><!--&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="submit" name="wp_storage_to_pcs_cache_clear_thumbnail" value="删除缩略图" class="button-primary" onclick="if(!confirm('确定要删除所有缩略图吗？'))return false;" />-->
		</p>
		<input type="hidden" name="action" value="wp_storage_to_pcs_cache_clear" />
		<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
		<?php wp_nonce_field(); ?>
	</form>
	</div>
</div>
<?php
}