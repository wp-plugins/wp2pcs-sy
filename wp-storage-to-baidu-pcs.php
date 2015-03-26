<?php

/*
*
* # 实现把百度网盘作为网站的附件存储工具
* # 可以实现网盘文件的外链
* # 为了减轻网站本身的压力，本工具规定用户自己先将图片上传到网盘，本地使用时提供一个对话框，站长可以看到自己网盘上已经有的图片，选择某一个图片作为外链
* # 图片也指附件，其他附件就不提供直接外链，而提供下载地址
*
*/


// 提交控制面板中的信息时
add_action('admin_init','wp_storage_to_pcs_action');
function wp_storage_to_pcs_action(){
	if(is_multisite() && !current_user_can('manage_network')){
		return;
	}elseif(!current_user_can('edit_theme_options')){
		return;
	}
	// 更新设置
	if(!empty($_POST) && isset($_POST['page']) && $_POST['page'] == $_GET['page'] && isset($_POST['action']) && $_POST['action'] == 'wp_storage_to_pcs_update'){
		check_admin_referer();
		// 更新备份到的目录
		$remote_dir = trim($_POST['wp_storage_to_pcs_remote_dir']);
		if(!$remote_dir || empty($remote_dir)){
			wp_die('请填写附件在网盘中的存储目录！');
			exit;
		}
		$remote_dir =  WP2PCS_REMOTE_ROOT.trailing_slash_path($remote_dir);
		update_option('wp_storage_to_pcs_remote_dir',$remote_dir);
		// 更新图片URL前缀
		$image_perfix = trim($_POST['wp_storage_to_pcs_image_perfix']);
		update_option('wp_storage_to_pcs_image_perfix',$image_perfix);
		$image_copy = $_POST['wp_storage_to_pcs_image_copy'];
		if($image_copy)update_option('wp_storage_to_pcs_image_copy',$image_copy);
		else delete_option('wp_storage_to_pcs_image_copy');
		// 更新文件下载URL前缀
		$download_perfix = trim($_POST['wp_storage_to_pcs_download_perfix']);
		update_option('wp_storage_to_pcs_download_perfix',$download_perfix);
		$download_copy = $_POST['wp_storage_to_pcs_download_copy'];
		if($download_copy)update_option('wp_storage_to_pcs_download_copy',$download_copy);
		else delete_option('wp_storage_to_pcs_download_copy');
		// 更新视频 /分辨率
		$video_perfix = trim($_POST['wp_storage_to_pcs_video_perfix']);
		update_option('wp_storage_to_pcs_video_perfix',$video_perfix);
		//$video_copy = $_POST['wp_storage_to_pcs_video_copy'];
		//if($video_copy)update_option('wp_storage_to_pcs_video_copy',$video_copy);
		//else delete_option('wp_storage_to_pcs_video_copy');
		$video_size = $_POST['wp_storage_to_pcs_video_size'];
		update_option('wp_storage_to_pcs_video_size',$video_size);
		// 更新音乐
		$audio_perfix = trim($_POST['wp_storage_to_pcs_audio_perfix']);
		update_option('wp_storage_to_pcs_audio_perfix',$audio_perfix);
		$audio_copy = $_POST['wp_storage_to_pcs_audio_copy'];
		if($audio_copy)update_option('wp_storage_to_pcs_audio_copy',$audio_copy);
		else delete_option('wp_storage_to_pcs_audio_copy');
		// 更新流媒体
		$media_perfix = trim($_POST['wp_storage_to_pcs_media_perfix']);
		update_option('wp_storage_to_pcs_media_perfix',$media_perfix);
		$media_copy = $_POST['wp_storage_to_pcs_media_copy'];
		if($media_copy)update_option('wp_storage_to_pcs_media_copy',$media_copy);
		else delete_option('wp_storage_to_pcs_media_copy');
		// 更新防盗链
		$link_protact = trim($_POST['wp_storage_to_pcs_outlink_protact']);
		update_option('wp_storage_to_pcs_outlink_protact',$link_protact);

		// 完成，跳转
		wp_redirect(wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page'].'&time='.time().'#wp-to-pcs-storage-form');
		exit;
	}
}

// 下面是后台控制面板
function wp_storage_to_pcs_panel(){
	$remote_dir = get_option('wp_storage_to_pcs_remote_dir');
	// 前缀
	$image_perfix = get_option('wp_storage_to_pcs_image_perfix');
	$download_perfix = get_option('wp_storage_to_pcs_download_perfix');
	$video_perfix = get_option('wp_storage_to_pcs_video_perfix');
	$audio_perfix = get_option('wp_storage_to_pcs_audio_perfix');
	$media_perfix = get_option('wp_storage_to_pcs_media_perfix');
	//防盗链
	$link_protact = get_option('wp_storage_to_pcs_outlink_protact');
	$video_size = get_option('wp_storage_to_pcs_video_size');
?>
<div class="postbox" id="wp-to-pcs-storage-form">
	<h3>PCS存储设置 <a href="javascript:void(0)" class="tishi-btn" id="wp-to-pcs-storage-tishi-btn">+</a></h3>	
	<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
	<form method="post">
		<p>使用网盘中的哪个目录：<?php echo WP2PCS_REMOTE_ROOT; ?><input type="text" name="wp_storage_to_pcs_remote_dir"  class="regular-text" value="<?php echo str_replace(WP2PCS_REMOTE_ROOT,'',$remote_dir); ?>" /></p>
		<p class="tishi hidden">使用网盘中的某一个目录作为你存储图片或附件的根目录，例如你填写“uploads”，那么到时候就会采用这个目录下的文件作为附件。</p>
		<p>图片访问前缀：
			<input type="text" name="wp_storage_to_pcs_image_perfix" value="<?php echo $image_perfix; ?>" />
			被访问<input type="text" name="wp_storage_to_pcs_image_copy" value="<?php echo get_option('wp_storage_to_pcs_image_copy'); ?>" style="width:50px;" />次以上强制缓存到本地
		</p>
		<p class="tishi hidden">访问前缀是指用户访问你的网站的什么URL时才会调用网盘中的图片，例如你填写的是“img”，那么用户在访问“<?php echo home_url('/img/test.jpg'); ?>”时，屏幕上就会打印在你的网盘目录“<?php echo WP2PCS_REMOTE_ROOT; ?>uploads/test.jpg”这张图片。为了提高不同空间的兼容性，默认为“?img”的形式。</p>
		<p class="tishi hidden">强制缓存到本地：当你的某个图片或视频被访问的次数超过了你设置的这个次数，说明这个图片或视频需要经常使用，缓存到你的网站空间更有利。要使用该功能需要满足：1.你的网站空间有可写的权限；2.访问前缀中不能包含?。填写0或留空时，表示不使用这个功能。</p>
		<p class="tishi hidden">如果你的网站开启了Memory Cache，图片的访问计数将保存在Cache中，如果没有开启Memory Cache，图片访问计数将保存至WordPress数据库中。</p>
		<p <?php if(!AUDIO_SHORTCODE)echo 'class="tishi hidden"'; ?>>MP3&nbsp;音乐前缀：
			<input type="text" name="wp_storage_to_pcs_audio_perfix" value="<?php echo $audio_perfix; ?>" /> 
			被访问<input type="text" name="wp_storage_to_pcs_audio_copy" value="<?php echo get_option('wp_storage_to_pcs_audio_copy'); ?>" style="width:50px;" />次以上强制缓存到本地
		</p>
		<p>原始文件前缀：
			<input type="text" name="wp_storage_to_pcs_media_perfix" value="<?php echo $media_perfix; ?>" /> 
			被访问<input type="text" name="wp_storage_to_pcs_media_copy" value="<?php echo get_option('wp_storage_to_pcs_media_copy'); ?>" style="width:50px;" />次以上强制缓存到本地
		</p>
		<p>下载访问前缀：
			<input type="text" name="wp_storage_to_pcs_download_perfix" value="<?php echo $download_perfix; ?>" />
			被访问<input type="text" name="wp_storage_to_pcs_download_copy" value="<?php echo get_option('wp_storage_to_pcs_download_copy'); ?>" style="width:50px;" />次以上强制缓存到本地
		</p>
		<p <?php if(!VIDEO_SHORTCODE)echo 'class="tishi hidden"'; ?>>M3U8视频前缀：
			<input type="text" name="wp_storage_to_pcs_video_perfix" value="<?php echo $video_perfix; ?>" /> 
			视频分辨率：<select name="wp_storage_to_pcs_video_size">
				<option value="MP4_480P" <?php selected($video_size,'MP4_480P'); ?>>MP4 480P</option>
				<option value="M3U8_854_480" <?php selected($video_size,'M3U8_854_480'); ?>>M3U8 854*480</option>
				<option value="M3U8_640_480" <?php selected($video_size,'M3U8_640_480'); ?>>M3U8 640*480</option>
				<option value="M3U8_480_360" <?php selected($video_size,'M3U8_480_360'); ?>>M3U8 480*360</option>
				<option value="M3U8_480_224" <?php selected($video_size,'M3U8_480_224'); ?>>M3U8 480*224</option>
				<option value="M3U8_320_240" <?php selected($video_size,'M3U8_320_240'); ?>>M3U8 320*240</option>
			</select>
		</p>
		<p>开启防盗链(视频无效)：&nbsp;&nbsp;
			<input type="checkbox" name="wp_storage_to_pcs_outlink_protact" value="1" <?php if($link_protact) echo("checked");?>/> 
		</p>
		<p><input type="submit" value="确定" class="button-primary" /></p>
		<input type="hidden" name="action" value="wp_storage_to_pcs_update" />
		<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
		<?php wp_nonce_field(); ?>
	</form>
	</div>
	<div class="inside tishi hidden" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
		<p>你还需要注意一些兼容性问题。这不是指插件本身的问题，而是指与其他环境的冲突，例如你使用了CDN缓存服务，就有可能造成图片缓存而不能被访问；如果你使用了其他插件来优化你的图片URL，也最好将这些插件重新设计。</p>
	</div>
</div>
<script>
jQuery(function($){
	/*
	$('.hide-for-oauth-code').each(function(){
		if($(this).find('input[type=checkbox]').is(':checked')){
			$(this).next('.hide-for-copy').hide();
		}else{
			$(this).next('.hide-for-copy').show();
		}
	});
	*/

	$('.hide-for-copy')
	/*
	.each(function(){
		var value = $(this).find('input').val();
		if(value > 0){
			$(this).removeClass('tishi').show();
		}
	})
	*/
	.find('input').focusout(function(){
		var value = $(this).parent().parent().find('input:first').val();
		if(value.indexOf('?') >= 0 || value.indexOf('index.php') >= 0){
			$(this).val('');
			alert('访问前缀中包含?或index.php，不能使用该功能。');
			return;
		}
		$(this).parent().removeClass('tishi');
	});
	$('.hide-for-oauth-code:visible').change(function(){
		if($(this).find('input[type=checkbox]').is(':checked')){
			$(this).next('.hide-for-copy').hide();
		}else{
			var value = $(this).next('.hide-for-copy').find('input').val();
			if(value > 0 || $('#wp-to-pcs-storage-tishi-btn').text() == '-'){
				$(this).next('.hide-for-copy').show();
			}else {
				$(this).next('.hide-for-copy').hide();				
			}
		}
	});
});
</script>
<?php
}