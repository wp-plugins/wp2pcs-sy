<?php

// 替换字符串中第一次出现的子串
function str_replace_first($find,$replace,$string){
	$position = strpos($string,$find);
	if($position !== false){
		$length = strlen($find); 
		$string = substr_replace($string,$replace,$position,$length);
		return $string;
	}else{
		return $string;
	}
}

// 替换字符串中最后一次出现的子串
function str_replace_last($find,$replace,$string){
	$position = strrpos($string,$find);
	if($position !== false){
		$length = strlen($find); 
		$string = substr_replace($string,$replace,$position,$length);
		return $string;
	}else{
		return $string;
	}
}

// 创建一个函数，判断wordpress是否安装在子目录中
function get_blog_install_in_subdir(){
	// 获取home_url其中的path部分，以此来判断是否安装在子目录中
	$install_in_sub_dir = parse_url(home_url(),PHP_URL_PATH);
	if($install_in_sub_dir){
		return $install_in_sub_dir;
	}else{
		return false;
	}
}

// 判断wordpress是否安装在win主机，并开启了重写
function get_blog_install_software(){
	$software = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
	if(strpos($software,'IIS') !== false){
		$software = 'IIS';
	}elseif(strpos($software,'Apache') !== false){
		$software = 'Apache';
	}elseif(strpos($software,'NginX') !== false){
		$software = 'NginX';
	}else{
		$software = 'Others';
	}
	// 先判断这个主机的服务器软件
	return $software;
}

// 用下面这个函数判断wordpress是否已经开启重写，并且返回开启重写的方式
function is_wp_rewrited(){
	$is_rewrited = false;
	$software = get_blog_install_software();
	$permalink_structure = get_option('permalink_structure');
	$install_root = ABSPATH;
	$install_in_subdir = get_blog_install_in_subdir();
	if($install_in_subdir){
		$install_root = str_replace_last($install_in_subdir.'/','',$install_root);
	}
	if($permalink_structure){
		$is_rewrited = "$permalink_structure ";
		$install_root = trim($install_root);
		if(file_exists($install_root.'.htaccess'))
			$is_rewrited .= '.htaccess ';
		if(file_exists($install_root.'httpd.conf'))
			$is_rewrited .= 'httpd.conf ';
		if(file_exists($install_root.'app.conf'))
			$is_rewrited .= 'app.conf ';
		if(file_exists($install_root.'config.yaml'))
			$is_rewrited .= 'config.yaml ';
		if(file_exists($install_root.'httpd.ini'))
			$is_rewrited .= 'httpd.ini';
	}
	return $is_rewrited;
}

// 通过一个函数用来计算当前附件的访问的真正有效的uri
function get_outlink_real_uri($uri,$perfix){
	// $uri是home_url后面的URL字串，例如当前的uri是http://yourdomain.com/yourblog/?image/test.jpg，那么$uri==/?image/test.jpg
	// 但在一些特殊情况下，如视频前缀为index.php/video，以及IIS上，$uri就有可能为index.php/?image/test.jpg
	// $perfix是指要处理的附件的前缀，例如可以是get_option('wp_storage_to_pcs_image_perfix');(图片前缀)
	// 你要通过这个函数返回真正有效的uri，如上所述$uri==index.php/?image/test.jpg，你应该尽可能的让函数返回/?image/test.jpg
	// 默认情况下，只处理IIS下开启了重写（修改了固定链接）的情况
	if(get_blog_install_software()=='IIS' && is_wp_rewrited()){
		// 对于perfix中不包含index.php的情况
		if(strpos($perfix,'index.php')!==0 && strpos($uri,'/index.php')===0){
			if(strpos($uri,'/index.php/'.$perfix)===0){
				$uri = str_replace_first('/index.php/','/',$uri);
			}elseif(strpos($uri,'/index.php'.$perfix)===0){
				$uri = str_replace_first('/index.php','/',$uri);
			}
		}
		// 对于perfix中包含index.php的情况
		elseif(strpos($perfix,'index.php')===0 && strpos($uri,'/index.php')===0){
			if(strpos($uri,'/index.php/index.php/')===0){
				$uri = str_replace_first('/index.php/index.php/','/',$uri);
			}elseif(strpos($uri,'/index.php/index.php')===0){
				$uri = str_replace_first('/index.php/index.php','/',$uri);
			}
		}
	}
	return $uri;
}

// 创建一个函数，用来获取当前PHP的执行时间
function get_unix_timestamp(){   
    list($msec,$sec) = explode(' ',microtime());
    return (float)$sec+(float)$msec;
}
// 利用上面的函数，获取php开始执行的时间戳。注意，这是一个全局函数
$php_begin_run_time = get_unix_timestamp();

// 创建一个函数，获取php执行了的时间，以秒为单位（浮点数）
function get_php_run_time(){
	global $php_begin_run_time;
	$php_run_time = get_unix_timestamp() - $php_begin_run_time;
	return $php_run_time;
}

// 创建一个函数，判断插件是否已经激活
function is_wp_to_pcs_active(){
	$refresh_token = get_option('wp_to_pcs_refresh_token');
	$access_token = get_option('wp_to_pcs_access_token');
	$expires_time = get_option('wp_to_pcs_expires_time');
	if(!$refresh_token || !$access_token || !$expires_time){
		return false;
	}
	return true;
}

// 获取当前访问的URL地址
function wp_to_pcs_wp_current_request_url($query = array(),$remove = array()){
	// 获取当前URL
	$current_url = 'http';
	if ($_SERVER["HTTPS"] == "on"){
		$current_url .= "s";
	}
	$current_url .= "://";
	// 部分主机会出现多出端口号的情况，我们把它注释掉，看还会不会出现这种情况。
	//if($_SERVER["SERVER_PORT"] != "80"){
	//	$current_url .= WP2PCS_SITE_DOMAIN.":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	//}else{
		$current_url .= WP2PCS_SITE_DOMAIN.$_SERVER["REQUEST_URI"];
	//}
	// 是否要进行参数处理
	$parse_url = parse_url($current_url);
	if(is_array($query) && !empty($query)){
		parse_str($parse_url['query'],$parse_query);
		$parse_query = array_merge($parse_query,$query);
		if(!empty($remove))foreach($remove as $key){
			if(isset($parse_query[$key]))unset($parse_query[$key]);
		}
		$parse_query = http_build_query($parse_query);
		$current_url = str_replace($parse_url['query'],'?'.$parse_query,$current_url);
	}elseif($query === false){
		$current_url = str_replace('?'.$parse_url['query'],'',$current_url);
	}
	return $current_url;
}

// 判断文件或目录是否真的有可写权限
// http://blog.csdn.net/liushuai_andy/article/details/8611433
function is_really_writable($file){
	$file = trim($file);
	// 是否开启安全模式
	if(DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == FALSE){
		return is_writable($file);
	}
	// 如果是目录的话
	if(is_dir($file)){
		$file = rtrim($file, '/').'/'.md5(mt_rand(1,100).mt_rand(1,100));
		if(($fp = @fopen($file,'w+')) === FALSE){
			return FALSE;  
		}
		fclose($fp);
		@chmod($file,'0755');
		@unlink($file);
		return TRUE;
	}
	// 如果是不是文件，或文件打不开的话
	elseif(!is_file($file) OR ($fp = @fopen($file,'w+')) === FALSE){
		return FALSE;
	}
	fclose($fp);
	return TRUE;
}

function set_wp2pcs_cache(){
	// 考虑到流量问题，必须增加缓存能力
	if(WP2PCS_CACHE){
		set_php_ini('timezone');
		set_php_ini('session_start');		
		header("Cache-Control: private, max-age=10800, pre-check=10800");
		header("Pragma: private");
		header("Expires: " . date(DATE_RFC822,strtotime(" 2 day")));
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
			header('Last-Modified: '.$_SERVER['HTTP_IF_MODIFIED_SINCE'],true,304);
			set_php_ini('session_end');
			exit;
		}
	}
}

function get_by_curl($url,$post = false){
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if($post){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	//下面7个curl_setopt由wishinlife添加
	if(strtolower(substr($url,0,5)) == 'https')
	{
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);// 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_CLOSEPOLICY, CURLCLOSEPOLICY_LEAST_RECENTLY_USED);
	}
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, false);  
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 设置超时限制防止死循环
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);

	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

// 有效解决超过2G大文件的文件大小问题
function get_real_filesize($file) {
    $size = filesize($file);
    if($size < 0)
        if(!(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'))
            $size = trim(`stat -c%s $file`);
        else{
            $fsobj = new COM("Scripting.FileSystemObject");
            $f = $fsobj->GetFile($file);
            $size = $file->Size;
        }
    return $size;
}

function get_real_path ($path) {
	if(DIRECTORY_SEPARATOR == '\\') {
		return str_replace('/','\\',$path);
	}
	else {
		return str_replace('\\','/',$path);
	}
}

// 解决路径最后的slah尾巴，如果没有则加上，而且根据不同的服务器，采用/或者\
function trailing_slash_path($path_string,$is_win = false){
	$trail = substr($path_string,-1);
	if($is_win){
		if($trail != '/' && $trail != '\\'){
			$path_string .= '\\';
		}
	}else{
		if($trail != '/'){
			$path_string .= '/';
		}
	}
	return $path_string;
}

/*
* 加密解密函数
*/
function wp2pcs_encrypt($data, $key) 
{ 
    $key    =   md5($key); 
    $x      =   0; 
    $len    =   strlen($data); 
    $l      =   strlen($key); 
    for ($i = 0; $i < $len; $i++) 
    { 
        if ($x == $l)  
        { 
            $x = 0; 
        } 
        $char .= $key{$x}; 
        $x++; 
    } 
    for ($i = 0; $i < $len; $i++) 
    { 
        $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256); 
    } 
    return base64_encode($str); 
} 
// 解密函数
function wp2pcs_decrypt($data, $key) 
{ 
    $key = md5($key); 
    $x = 0; 
    $data = base64_decode($data); 
    $len = strlen($data); 
    $l = strlen($key); 
    for ($i = 0; $i < $len; $i++) 
    { 
        if ($x == $l)  
        { 
            $x = 0; 
        } 
        $char .= substr($key, $x, 1); 
        $x++; 
    } 
    for ($i = 0; $i < $len; $i++) 
    { 
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) 
        { 
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1))); 
        } 
        else 
        { 
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1))); 
        } 
    } 
    return $str; 
}

// 设置全局参数
function set_php_ini($name){
	if($name == 'session_start'){
		/* 为了兼容性，去掉session，你可以自己打开
		if(defined('WP_TEMP_DIR') && is_really_writable(WP_TEMP_DIR)){
			if(function_exists('ini_set'))ini_set('session.save_path',WP_TEMP_DIR);// 重新规定session的存储位置
		}
		session_start();
		*/
	}
	elseif($name == 'session_end'){
		/* 为了兼容性，去掉session，你可以自己打开
		if(function_exists("session_destroy"))session_destroy();
		*/
	}
	elseif($name == 'limit'){
		/* 为了兼容性，去掉time limit，你可以自己打开*/
		if(function_exists("ini_set")) {
			//ini_set('memory_limit','128M'); // 扩大内存限制，防止备份溢出
			ini_set('max_execution_time', 600);
		}
		if(function_exists('set_time_limit')) set_time_limit(36000); // 延长执行时间，防止备份失败
		if(function_exists('ignore_user_abort')) ignore_user_abort(true);
	}elseif($name == 'timezone'){
		date_default_timezone_set("PRC");// 使用东八区时间，如果你是其他地区的时间，自己修改
	}elseif($name == 'error'){
		// 显示运行错误
		if(function_exists('error_reporting'))error_reporting(E_ALL); 
		if(function_exists('ini_set'))ini_set('display_errors', 1);
	}
}