<?php

// true强制采用外链，false则根据后台的设置来，视频采用m3u8格式输出，流量其实不大
// 由于百度网盘视频转码不是能解码所有文件，所以建议只使用avi/rm/mkv等主流视频格式，flv格式都有可能效果不佳

// 创建一个函数，用来在wordpress中打印图片地址
function wp2pcs_video_src($video_path = false){
	// video_path是指相对于后台保存的存储目录的路径
	// 例如 $file_path = /test/test.avi
	// 注意最前面加/
	$video_perfix = trim(get_option('wp_storage_to_pcs_video_perfix'));
	$video_src = "/$video_perfix/".$video_path;
	$video_src = str_replace('//','/',$video_src);
	return home_url($video_src);
}

// 创建短代码来打印视频
function wp2pcs_video_shortcode($atts){
	extract(shortcode_atts(array(
		'src' => '',
		'cover' => '',
		'width' => '640',
		'height' => '480',
		'stretch' => 'bestfit',
		'refresh' => 'false'
	),$atts));

	static $video_id = 1;
	if($video_id == 1){
		echo '<script src="'.plugins_url("/asset/player.js",WP2PCS_PLUGIN_NAME).'"></script>';
		echo '<script type="text/javascript" src="http://cybertran.baidu.com/cloud/media/assets/cyberplayer/1.0/cyberplayer.html5.min.js"></script>';
		//echo '<script type="text/javascript" src="'.plugins_url("asset/cyberplayer.min.js",WP2PCS_PLUGIN_NAME).'"></script>';
	}
	else $video ++;

	$width = $width ? $width : '640';
	$height = $height ? $height : '480';
	$stretch = $stretch ? $stretch : 'uniform'; //'bestfit';
	$refresh = $refresh ? $refresh : 'true';

	// 处理视频文件名，以解决文件路径中存在空格和中文的情况
	if(empty($src)){
		return;
	}
	$video_perfix = trim(get_option('wp_storage_to_pcs_video_perfix'));
	$src = urldecode($src);
	$src = str_replace_first(home_url('/').$video_perfix,'',$src);
	$video_ext = substr($src,strrpos($src,'.'));
	$video_fn =  base64_encode(substr($src, 0, strlen($src)-strlen($video_ext)));
	$video_fn = str_replace('+','-',$video_fn);
	$video_fn = str_replace('/','_',$video_fn);
	$video_fn = str_replace('=','',$video_fn);
	$src = wp2pcs_video_src($video_fn.$video_ext);

	$player_id = get_php_run_time();
	$player_id = str_replace('.','',$player_id);

	//$player = '<div style="background:#000;display:block;margin:0 auto;width:'.$width.'px;height:'.$height.'px;"><div id="videoplayer_'.$player_id.'"></div></div>';
	$player = '<div style="background:#000000;display:table;margin:auto;width:auto;height:auto;"><div id="videoplayer_'.$player_id.'"></div></div>';
	if($refresh === 'true')$player .= '<p align="center" class="videoplayer-source"><a href="'.$src.'" target="refreshvideo" style="color:#999;font-size:0.8em;" title="刷新后重新加载本页才能观看完整的视频">刷新视频资源</a><iframe frameborder="0" framescroll="no" name="refreshvideo" style="float:right;width:1px;height:1px;overflow:hidden;"></iframe></p>';
	$player .= '<script type="text/javascript">
	var ua = navigator.userAgent,
		isandroid = ua.indexOf("Android") > -1 || ua.indexOf("Linux") > -1,
		isiPhone = ua.indexOf("iPhone") > -1,
		isiPad = ua.indexOf("iPad") > -1,
		ismobile = isandroid||isiPhone||isiPad;
	if(ismobile){
		document.getElementById("videoplayer_'.$player_id.'").innerHTML =\'<video src="'.$src.'" controls="controls" width="'.$width.'" height="'.$height.'"></video>\';
	}else{
		var player_'.$player_id.' = cyberplayer("videoplayer_'.$player_id.'").setup({
			width : '.$width.',
			height : '.$height.',
			backcolor : "#000000",
			stretching : "'.$stretch.'",
			file : "'.$src.'",
			image : "'.$cover.'",
			autoStart : 0,
			repeat : "none",
			volume : 100,
			controlbar : "over",
			ak : "'.(defined(WP2PCS_APP_KEY) ? WP2PCS_APP_KEY : 'dqSQouI90u33xGGUZzMWASZY').'",
			sk : "'.(defined(WP2PCS_APP_SECRET) ? substr(WP2PCS_APP_SECRET,0,16) : 'Buy4OzNfX2GEIRhL').'"
		});
	}</script>';
		//falshplayer: "http://pan.baidu.com/res/static/thirdparty/flashvideo/player/cyberplayer.swf",
	return $player;
}
add_shortcode('video','wp2pcs_video_shortcode');

// 通过对URI的判断来获得图片远程信息
add_action('init','wp_storage_print_video',-1);
function wp_storage_print_video(){
	// 只用于前台使用视频
	if(is_admin()){
		return;
	}

	$current_uri = urldecode($_SERVER["REQUEST_URI"]);
	$query_pos = strpos($current_uri,'?');
	// 如果URL中有参数
	if($query_pos !== false){
		$current_uri = substr($current_uri,0,$query_pos);
	}

	$video_perfix = trim(get_option('wp_storage_to_pcs_video_perfix'));
	$video_uri = $current_uri;
	$video_path = '';

	// 如果不存在前缀，就不执行了
	if(!$video_perfix){
		return;
	}

	//防盗链
	/*if(get_option('wp_storage_to_pcs_outlink_protact') && !strpos($_SERVER['HTTP_REFERER'], WP2PCS_SITE_DOMAIN) && !strpos($_SERVER['HTTP_REFERER'], 'cybertran.baidu.com')) {
		return;
	}*/
	
	// 获取安装在子目录
	$install_in_subdir = get_blog_install_in_subdir();

	// 由于百度云媒体播放器的安全策略，只有经过允许的域名才能正常播放视频，由于这个安全策略，必须在网站根目录放置crossdomain.xml
	$blog_root = ($install_in_subdir ? str_replace_last($install_in_subdir,'',ABSPATH) : ABSPATH);
	$crossdomain_file = $blog_root.'crossdomain.xml';
	if(!file_exists($crossdomain_file) && is_really_writable($blog_root)){
		copy(dirname(WP2PCS_PLUGIN_NAME).'/asset/crossdomain.xml',$crossdomain_file);
	}

	// 判断路径后缀，如果不是.m3u8，就不往下执行
	if(substr($video_uri,-5) != '.m3u8'){
		return;
	}

	// 去除末尾的.m3u8，然后再判断对应的文件扩展名
	$video_uri = substr($video_uri,0,-5);
	
	// 当采用index.php/video时，大部分主机会跳转，丢失index.php，因此这里要做处理
	if(strpos($video_perfix,'index.php/')===0 && strpos($video_uri,'index.php/')===false){
		$video_perfix = str_replace_first('index.php/','',$video_perfix);
	}

	// 如果URI中根本不包含$video_perfix，那么就不用再往下执行了
	if(strpos($video_uri,$video_perfix)===false){
		return;
	}

	// 处理wordpress安装在子目录的情况
	if($install_in_subdir){
		$video_uri = str_replace_first($install_in_subdir,'',$video_uri);
	}

	// 返回真正有效的URI
	$video_uri = get_outlink_real_uri($video_uri,$video_perfix);

	// 如果URI中根本不包含$video_perfix，那么就不用再往下执行了
	if(strpos($video_uri,'/'.$video_perfix)!==0){
		return;
	}

	// 将前缀也去除，获取文件直接路径
	$video_path = str_replace_first('/'.$video_perfix,'',$video_uri);

	// 如果不存在video_path，也不执行了
	if(!$video_path){
		return;
	}
	//获取视频文件原始路径
	$video_path = str_replace('/','',$video_path);
	$video_path = str_replace('-','+',$video_path);
	$video_path = str_replace('_','/',$video_path);
	$video_path = str_pad($video_path, ceil(strlen($video_path) / 4) * 4, '=');
	$video_path =  base64_decode($video_path);
	$video_ext = strtolower(substr($video_path,strrpos($video_path,'.')+1));
	if(!in_array($video_ext,array('asf','avi','flv','mkv','mov','mp4','wmv','3gp','3g2','mpeg','ts','rm','rmvb'))){
		return;
	}

	// 获取视频路径
	$remote_dir = get_option('wp_storage_to_pcs_remote_dir');
	$video_path = trailing_slash_path($remote_dir).$video_path;
	$video_path = str_replace('//','/',$video_path);

	$video_size = get_option('wp_storage_to_pcs_video_size');
	global $baidupcs;
	$result = $baidupcs->streaming($video_path, ($video_size?$video_size:'MP4_480P'));// M3U8_854_480

	$meta = json_decode($result,true);
	if(isset($meta['error_msg'])){
		echo $meta['error_msg'];
		exit;
	}

	ob_clean();
	echo trim($result);
	exit;
}