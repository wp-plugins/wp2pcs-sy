<?php

// true强制采用外链，false则根据后台的设置来，媒体会消耗大量流量，且受网速影响
// 但另外一个问题是，如果媒体文件太大，则外链受到BAE的影响，会泄露token信息，故不建议使用超过10M的外链媒体（直链没有token问题）

// 创建一个函数，用来在wordpress中打印图片地址
function wp2pcs_media_src($media_path = false){
	// media_path是指相对于后台保存的存储目录的路径
	// 例如 $file_path = /test/test.avi
	// 注意最前面加/
	$media_perfix = get_option('wp_storage_to_pcs_media_perfix');
	$media_src = "/$media_perfix/".$media_path;
	$media_src = str_replace('//','/',$media_src);
	return home_url($media_src);
}

// 通过对URI的判断来获得图片远程信息
add_action('init','wp_storage_print_media',-1);
function wp_storage_print_media(){
	// 只用于前台使用媒体
	if(is_admin()){
		return;
	}

	$current_uri = urldecode($_SERVER["REQUEST_URI"]);
	$query_pos = strpos($current_uri,'?');
	// 如果URL中有参数
	if($query_pos !== false){
		$current_uri = substr($current_uri,0,$query_pos);
	}

	$media_perfix = get_option('wp_storage_to_pcs_media_perfix');
	$media_uri = $current_uri;
	$media_path = '';

	// 如果不存在前缀，就不执行了
	if(!$media_perfix){
		return;
	}

	//防盗链
	if(get_option('wp_storage_to_pcs_outlink_protact') && !strpos($_SERVER['HTTP_REFERER'], WP2PCS_SITE_DOMAIN)) {
		return;
	}

	// 当采用index.php/media时，大部分主机会跳转，丢失index.php，因此这里要做处理
	if(strpos($media_perfix,'index.php/')===0 && strpos($media_uri,'index.php/')===false){
		$media_perfix = str_replace_first('index.php/','',$media_perfix);
	}

	// 如果URI中根本不包含$media_perfix，那么就不用再往下执行了
	if(strpos($media_uri,$media_perfix)===false){
		return;
	}

	// 获取安装在子目录
	$install_in_subdir = get_blog_install_in_subdir();
	if($install_in_subdir){
		$media_uri = str_replace_first($install_in_subdir,'',$media_uri);
	}

	// 返回真正有效的URI
	$media_uri = get_outlink_real_uri($media_uri,$media_perfix);

	// 如果URI中根本不包含$media_perfix，那么就不用再往下执行了
	if(strpos($media_uri,'/'.$media_perfix)!==0){
		return;
	}
	
	// 将前缀也去除，获取文件直接路径
	$media_path = str_replace_first('/'.$media_perfix,'',$media_uri);

	// 如果不存在media_path，也不执行了
	if(!$media_path){
		return;
	}

	// 获取媒体路径
	$remote_dir = get_option('wp_storage_to_pcs_remote_dir');
	$media_path = trailing_slash_path($remote_dir).$media_path;
	$media_path = str_replace('//','/',$media_path);

	set_wp2pcs_cache();

	// 将文件强制缓存到本地
	// 记录被访问的次数，这个次数可以用在今后对附件的评估上面
	$file_local_path = trailing_slash_path(WP2PCS_TMP_DIR).str_replace('/','_',$media_path).'.tmp';
	$visit_key = 'WP2PCS_FILETMP_'.strtoupper(md5($file_local_path));

	//如果开启了Memory Object则存储在内存中，访问次数不写入数据库
	//访问次数写入Cache中，如果服务重启或清除Cache后访问次数将丢失
	if(function_exists('wp_cache_init')) {
		$visit_value = wp_cache_get($visit_key);
		if(!$visit_value) {
			wp_cache_add($visit_key, 1);
			$visit_value = 1;
		}
		else
			wp_cache_incr($visit_key, 1);
	}
	else {
		//  没有开启Memory Object，访问次数写入数据库
		$visit_value = get_option($visit_key);
		$visit_value = ($visit_value ? $visit_value : 0);
		$visit_value ++;
		update_option($visit_key, $visit_value);
	}
	$copy_value = get_option('wp_storage_to_pcs_media_copy');

	// 如果存在缓存文件，使用它
	if($copy_value != 0 && $copy_value != '' && file_exists($file_local_path)){
		$file = fopen($file_local_path,"r");
		$result = fread($file,filesize($file_local_path));
		fclose($file);
	}
	// 如果不存在缓存文件，就从PCS获取，并本地化
	else{
		global $baidupcs;
		$result = $baidupcs->downloadStream($media_path);
				
		$meta = json_decode($result,true);
		if(isset($meta['error_msg'])){
			echo $meta['error_msg'];
			exit;
		}
		// 下面本地化文件
		if($copy_value != 0 && $copy_value != '' && $visit_value >= $copy_value){
			$fopen = fopen($file_local_path,"w+");
			if($fopen != false){
				fwrite($fopen,$result);
			}
			fclose($fopen);
		}
	}
	
	header("Content-Type: application/octet-stream");
	header('Content-Disposition:inline;filename="'.basename($media_path).'"');
	header('Accept-Ranges: bytes');

	ob_clean();
	echo $result;
	exit;
}