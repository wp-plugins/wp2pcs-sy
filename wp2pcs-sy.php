<?php

/*
Plugin Name: WP2PCS-SY
Plugin URI: http://www.syncy.cn
Description: 本插件基于WP2PCS插件修改而来，帮助网站站长将网站和百度网盘连接。网站的数据库、日志、网站程序文件（包括wordpress系统文件、主题、插件、上传的附件等）一并上传到百度网盘，站长可以根据自己的习惯定时备份，让你的网站数据不再丢失！可以实现把网盘作为自己的附件存储空间。相比WP2PCS插件，WP2PCS-SY主要改动内容是本地存储百度PCS的Token，不再外部服务器上存储任何Token，同时也取消了原插件中的外链功能，增加防盗链功能。
Version: 1.3.12
Author:   <a href="http://www.syncy.cn/">WishInLife</a> （原插件作者：<a href="http://www.wp2pcs.com/">否子戈</a>）
Author URI: http://www.syncy.cn
*/

/*
 *
 * 初始化数据
 *
 */
// 初始化固定值常量
define('WP2PCS_PLUGIN_NAME',__FILE__);

// 包含一些必备的函数和类，以提供下面使用
require(dirname(__FILE__).'/wp2pcs-setup-functions.php');
require(dirname(__FILE__).'/libs/BaiduPCS.class.php');

// 经过判断或函数运算才能进行定义的常量
define('WP2PCS_APP_KEY',get_option('wp_to_pcs_app_key'));
define('WP2PCS_APP_SECRET',get_option('wp_to_pcs_app_secret'));

define('WP2PCS_SITE_DOMAIN',$_SERVER['HTTP_HOST']);
define('WP2PCS_REMOTE_ROOT','/apps/'.get_option('wp_to_pcs_remote_aplication').'/'.WP2PCS_SITE_DOMAIN.'/');
define('WP2PCS_PLUGIN_VER',str_replace('.','','2015.05.24.10.00'));// 以最新一次更新的时间点（到分钟）作为版本号
define('WP2PCS_IS_WIN',strpos(PHP_OS,'WIN')!==false);
define('WP2PCS_TMP_DIR',get_real_path(ABSPATH.'wp2pcs_tmp'));// WP2PCS暂时性存储目录
define('WP2PCS_IS_WRITABLE',is_really_writable(WP2PCS_TMP_DIR));

// 当你发现自己错过了很多定时任务时，可以帮助你执行没有执行完的定时任务
if(is_admin())define('ALTERNATE_WP_CRON',true);

if(!defined('WP2PCS_CACHE'))define('WP2PCS_CACHE',true);// 附件缓存
//if(!defined('WP2PCS_SYNC'))define('WP2PCS_SYNC',false);// 上传文件时，马上加入到同步列表
if(!defined('VIDEO_SHORTCODE'))define('VIDEO_SHORTCODE',true);// 启用视频短代码
if(!defined('AUDIO_SHORTCODE'))define('AUDIO_SHORTCODE',true);// 启用音乐短代码

// 直接初始化全局变量
$baidupcs = new BaiduPCS(get_option('wp_to_pcs_access_token'));

/*
 *
 * 引入功能文件
 *
 */

//开启调试log输出
//define('WP2PCS_DEBUG',true);
// 开启调试模式
//include(dirname(__FILE__).'/wp2pcs-debug.php');

// 下面是备份功能文件
require(dirname(__FILE__).'/wp-backup-database-functions.php');
require(dirname(__FILE__).'/wp-backup-file-functions.php');
require(dirname(__FILE__).'/wp-backup-to-baidu-pcs.php');
require(dirname(__FILE__).'/wp-diff-to-baidu-pcs.php');

// 下面是存储功能文件
require(dirname(__FILE__).'/wp-storage-image-outlink.php');
require(dirname(__FILE__).'/wp-storage-download-file.php');
require(dirname(__FILE__).'/wp-storage-video-online.php');//播放视频需要ak、sk？？
require(dirname(__FILE__).'/wp-storage-audio-online.php');
require(dirname(__FILE__).'/wp-storage-media-online.php');
require(dirname(__FILE__).'/wp-storage-to-baidu-pcs.php');
require(dirname(__FILE__).'/wp-storage-insert-to-content.php');
require(dirname(__FILE__).'/wp-storage-file-manage.php');


/*
 *
 * 初始化设置
 *
 */

// 添加定时任务--刷新 access token
add_action('wp_to_pcs_corn_task_refresh_token','wp_to_pcs_corn_task_function_refresh_token');
function wp_to_pcs_corn_task_function_refresh_token() {
	//重新设置下一次更新任务时间
	if(wp_next_scheduled('wp_to_pcs_corn_task_refresh_token'))
		wp_clear_scheduled_hook('wp_to_pcs_corn_task_refresh_token');
	$run_time = date('Y-m-d H:i:s',strtotime('+14 day'));
	wp_schedule_event(strtotime($run_time),'biweekly','wp_to_pcs_corn_task_refresh_token');
	if(WP2PCS_APP_KEY && WP2PCS_APP_SECRET)
		$result = get_by_curl("https://openapi.baidu.com/oauth/2.0/token", "grant_type=refresh_token&refresh_token=".get_option('wp_to_pcs_refresh_token')."&client_id=".WP2PCS_APP_KEY."&client_secret=".WP2PCS_APP_SECRET);
	else
		$result = get_by_curl('https://www.syncy.cn/oauth','method=refresh_access_token&sign=wp2pcs-sy&refresh_token='.get_option('wp_to_pcs_refresh_token'));
	$result_array = json_decode($result,true);
	if(isset($result_array['access_token'])){
		update_option('wp_to_pcs_access_token',$result_array['access_token']);
		update_option('wp_to_pcs_refresh_token',$result_array['refresh_token']);
		update_option('wp_to_pcs_expires_time',time() + $result_array['expires_in']);
		global $baidupcs;
		$baidupcs->setAccessToken($result_array['access_token']);
		return true;
	}
	else
	{//刷新失败
		wp_die($result);
		return false;
	}
}

// 提高执行时间
add_filter('http_request_timeout','wp_smushit_filter_timeout_time');
function wp_smushit_filter_timeout_time($time){
	return 25;
}

// 初始化插件默认设置选项
register_activation_hook(WP2PCS_PLUGIN_NAME,'wp_to_pcs_install_options');
function wp_to_pcs_install_options(){
	// 网盘中的应用目录
	update_option('wp_to_pcs_remote_aplication','SyncY');
}
function wp_to_pcs_default_options(){// 授权成功的时候再赋值
	if(!get_option('wp_backup_to_pcs_remote_dir'))update_option('wp_backup_to_pcs_remote_dir',WP2PCS_REMOTE_ROOT.'backup/');
	//if(!get_option('wp_backup_to_pcs_local_paths'))update_option('wp_backup_to_pcs_local_paths',ABSPATH);
	if(!get_option('wp_storage_to_pcs_remote_dir'))update_option('wp_storage_to_pcs_remote_dir',WP2PCS_REMOTE_ROOT.'uploads/');
	if(!get_option('wp_storage_to_pcs_image_perfix'))update_option('wp_storage_to_pcs_image_perfix','index.php/image');
	if(!get_option('wp_storage_to_pcs_download_perfix'))update_option('wp_storage_to_pcs_download_perfix','index.php/dlfile');
	if(!get_option('wp_storage_to_pcs_video_perfix'))update_option('wp_storage_to_pcs_video_perfix','index.php/video');
	if(!get_option('wp_storage_to_pcs_audio_perfix'))update_option('wp_storage_to_pcs_audio_perfix','index.php/mp3');
	if(!get_option('wp_storage_to_pcs_media_perfix'))update_option('wp_storage_to_pcs_media_perfix','index.php/media');
	if(!get_option('wp_storage_to_pcs_outlink_protact'))update_option('wp_storage_to_pcs_outlink_protact','1');//开启防盗链
	if(!get_option('wp_storage_to_pcs_video_size'))update_option('wp_storage_to_pcs_video_size','MP4_480P'); //M3U8_854_480
	//if(!get_option('wp_storage_to_pcs_video_copy'))update_option('wp_storage_to_pcs_video_copy','100');//访问设定次数后缓存至本地
	//if(!get_option('wp_storage_to_pcs_image_copy'))update_option('wp_storage_to_pcs_image_copy','100');//访问设定次数后缓存至本地
	// 初始化按钮
	if(!wp_next_scheduled('wp_diff_to_pcs_corn_task'))delete_option('wp_diff_to_pcs_future');
	if(!wp_next_scheduled('wp_backup_to_pcs_corn_task_database'))delete_option('wp_backup_to_pcs_future');
}

// 停用插件的时候停止定时任务
register_deactivation_hook(WP2PCS_PLUGIN_NAME,'wp_to_pcs_delete_options');
function wp_to_pcs_delete_options(){
	// 删除授权TOKEN
	delete_option('wp2pcs_colose_notice');
	delete_option('wp_to_pcs_app_key');
	delete_option('wp_to_pcs_app_secret');
	delete_option('wp_to_pcs_access_token');
	delete_option('wp_to_pcs_refresh_token');
	delete_option('wp_to_pcs_expires_time');
	delete_option('wp_to_pcs_remote_aplication');
	delete_option('wp_backup_to_pcs_remote_dir');
	delete_option('wp_storage_to_pcs_remote_dir');
	delete_option('wp_storage_to_pcs_image_perfix');
	delete_option('wp_storage_to_pcs_download_perfix');
	delete_option('wp_storage_to_pcs_video_perfix');
	delete_option('wp_storage_to_pcs_audio_perfix');
	delete_option('wp_storage_to_pcs_media_perfix');
	delete_option('wp_storage_to_pcs_video_size');

	delete_option('wp_storage_to_pcs_image_copy');
	//delete_option('wp_storage_to_pcs_video_copy');
	delete_option('wp_storage_to_pcs_audio_copy');
	delete_option('wp_storage_to_pcs_media_copy');
	delete_option('wp_storage_to_pcs_download_copy');
	delete_option('wp_storage_to_pcs_outlink_protact');
	// 关闭定时任务
	if(wp_next_scheduled('wp_backup_to_pcs_corn_task_database'))wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_database');
	if(wp_next_scheduled('wp_backup_to_pcs_corn_task_logs'))wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_logs');
	if(wp_next_scheduled('wp_backup_to_pcs_corn_task_www'))wp_clear_scheduled_hook('wp_backup_to_pcs_corn_task_www');
	if(wp_next_scheduled('wp_diff_to_pcs_corn_task'))wp_clear_scheduled_hook('wp_diff_to_pcs_corn_task');
	if(wp_next_scheduled('wp_to_pcs_corn_task_refresh_token'))wp_clear_scheduled_hook('wp_to_pcs_corn_task_refresh_token');
	// 删除定时备份的按钮信息
	delete_option('wp_backup_to_pcs_future');
	delete_option('wp_diff_to_pcs_future');
}

// 添加菜单，分清楚是否开启多站点功能
if(is_multisite()){
	add_action('network_admin_menu','wp_to_pcs_menu');
	function wp_to_pcs_menu(){
		global $wp2pcs_page, $wp2pcs_page_netdisk;
		$wp2pcs_page = add_options_page('WordPress连接百度网盘','WP2PCS-SY','manage_network','wp2pcs-sy','wp_to_pcs_pannel');
		$wp2pcs_page_netdisk = add_submenu_page('upload.php', 'WordPress连接百度网盘', '百度网盘', 'manage_network', 'wp2pcs-sy-netdisk', 'wp_storage_to_pcs_file_manage');
		add_action('load-'.$wp2pcs_page_netdisk, 'wp_storage_to_pcs_add_help_page_netdisk');
		add_action('load-'.$wp2pcs_page, 'wp_storage_to_pcs_add_help_page');
	}
}else{
	add_action('admin_menu','wp_to_pcs_menu');
	function wp_to_pcs_menu(){
		global $wp2pcs_page, $wp2pcs_page_netdisk;
		$wp2pcs_page = add_options_page('WordPress连接百度网盘','WP2PCS-SY','edit_theme_options','wp2pcs-sy','wp_to_pcs_pannel');
		$wp2pcs_page_netdisk = add_submenu_page('upload.php', 'WordPress连接百度网盘', '百度网盘', 'edit_theme_options', 'wp2pcs-sy-netdisk', 'wp_storage_to_pcs_file_manage');
		add_action('load-'.$wp2pcs_page_netdisk, 'wp_storage_to_pcs_add_help_page_netdisk');
		add_action('load-'.$wp2pcs_page, 'wp_storage_to_pcs_add_help_page');
	}
}

// 添加提交更新动作
add_action('admin_init','wp_to_pcs_action');
function wp_to_pcs_action(){
	// 权限控制
	if(is_multisite() && !current_user_can('manage_network')){
		return;
	}elseif(!current_user_can('edit_theme_options')){
		return;
	}
	// 关闭初始化提示
	if(isset($_GET['wp2pcs_close_notice']) && $_GET['wp2pcs_close_notice']=='true'){
		update_option('wp2pcs_colose_notice',WP2PCS_PLUGIN_VER);
	}
	// 提交授权
	if(!empty($_POST) && isset($_POST['page']) && $_POST['page'] == $_GET['page'] && isset($_POST['action']) && $_POST['action'] == 'wp_to_pcs_access_token'){
		check_admin_referer();
		// API KEY
		$app_key = trim($_POST['wp_to_pcs_app_key']);
		$app_key = $app_key ? $app_key : false;
		update_option('wp_to_pcs_app_key',$app_key);
		//WP2PCS_APP_KEY = $app_key;
		// Secret Key
		$secret_key = trim($_POST['wp_to_pcs_app_secret']);
		$secret_key = $secret_key ? $secret_key : false;
		update_option('wp_to_pcs_app_secret',$secret_key);
		//WP2PCS_APP_SECRET = $secret_key;
		// 远程应用目录
		$remote_aplication = trim($_POST['wp_to_pcs_remote_aplication']);
		$remote_aplication = $remote_aplication ? $remote_aplication : 'SyncY';
		update_option('wp_to_pcs_remote_aplication',$remote_aplication);
		// 回调网址
		$back_url = wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page']; 
		//$back_url = urlencode(wp_nonce_url($back_url));
		$back_url = urlencode($back_url);
		// 如果不存在TOKEN，那么跳转到WP2PCS进行授权
		if(!$access_token){
			if(WP2PCS_APP_KEY && WP2PCS_APP_SECRET) {
				$token_url = 'http://openapi.baidu.com/oauth/2.0/authorize?client_id='.WP2PCS_APP_KEY.'&response_type=code&redirect_uri='.$back_url.'&scope=basic,netdisk&confirm_login=1&state='.base64_encode($back_url);
			}
			else {
				$token_url = 'http://www.syncy.cn/oauth?from='.$back_url.'&method=auth_code&sign=wp2pcs-sy';
			}
			wp_redirect($token_url);
		}
		exit;
	}
	// 授权通过
	if(isset($_GET['code']) && !empty($_GET['code'])) {
		//获取access token和refresh token
		if(WP2PCS_APP_KEY && WP2PCS_APP_SECRET) {
			$back_url = wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page']; 
			$back_url = urlencode($back_url);
			$result = get_by_curl('https://openapi.baidu.com/oauth/2.0/token','grant_type=authorization_code&code='.$_GET['code'].'&client_id='.WP2PCS_APP_KEY.'&client_secret='.WP2PCS_APP_SECRET.'&redirect_uri='.$back_url);
		}else {
			$result = get_by_curl('https://www.syncy.cn/oauth','method=get_access_token&sign=wp2pcs-sy&code='.$_GET['code']);
		}
		$result_array = json_decode($result,true);
		if(isset($result_array['access_token'])){
			global $baidupcs;
			$baidupcs->setAccessToken($result_array['access_token']);
			update_option('wp_to_pcs_access_token',$result_array['access_token']);
			update_option('wp_to_pcs_refresh_token',$result_array['refresh_token']);
			update_option('wp_to_pcs_expires_time',time() + $result_array['expires_in']);
			//调度定时任务刷新 Access Token
			$run_time = date('Y-m-d H:i:s',strtotime('+14 day'));
			wp_schedule_event(strtotime($run_time),'biweekly','wp_to_pcs_corn_task_refresh_token');
		}
		else
		{//授权失败
			wp_die($result);
			exit;
		}

		wp_to_pcs_default_options();// 初始化各个推荐值
		update_option('wp2pcs_colose_notice',WP2PCS_PLUGIN_VER);// 关闭消息提示
		wp_redirect(wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page'].'&a=auth&time='.time());
		exit;
	}
	// 手动刷新 Access Token 或 重新授权
	if(!empty($_POST) && isset($_POST['page']) && $_POST['page'] == $_GET['page'] && isset($_POST['action']) && $_POST['action'] == 'wp_to_pcs_access_token_update') {
		if(isset($_POST['wp_to_pcs_access_token_update']) && $_POST['wp_to_pcs_access_token_update'] == '手动刷新AccessToken'){
			check_admin_referer();
			if(wp_to_pcs_corn_task_function_refresh_token()) {  //手动刷新成功
				wp_redirect(wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page'].'&a=refresh&time='.time());
			}
		}elseif(isset($_POST['wp_to_pcs_reoauth']) && $_POST['wp_to_pcs_reoauth'] == '重新授权') {
			check_admin_referer();
			// 回调网址
			$back_url = wp_to_pcs_wp_current_request_url(false).'?page='.$_GET['page']; 
			$back_url = urlencode(wp_nonce_url($back_url));
			if(WP2PCS_APP_KEY && WP2PCS_APP_SECRET) {
				$token_url = 'http://openapi.baidu.com/oauth/2.0/authorize?client_id='.WP2PCS_APP_KEY.'&response_type=code&redirect_uri='.$back_url.'&scope=basic,netdisk&confirm_login=1&state='.base64_encode($back_url);
			}
			else {
				$token_url = 'http://www.syncy.cn/oauth?from='.$back_url.'&method=auth_code&sign=wp2pcs-sy';
			}
			wp_redirect($token_url);
		}
		exit;
	}
}
// 帮助tab页
function wp_storage_to_pcs_add_help_page() {
	global $wp2pcs_page;
	$screen = get_current_screen();
	if ( $screen->id != $wp2pcs_page )
        return;
	$screen->add_help_tab( array(
		'id'		=> 'wp2pcs-sy',
		'title'		=> 'WP2PCS说明',
		'content'	=> '<p>WP2PCS能做：<ol>
					<li>将WordPress数据库按规定的时间周期备份到网盘</li>
					<li>将指定目录中的文件按规定的时间周期备份到网盘</li>
					<li>把网盘作为网站的存储空间，存放网站附件</li>
					<li>调用网盘中的文件资源，在你的网站中显示</li>
				</ol></p><p>WP2PCS不能做：<ol>
					<li>完全把网盘作为图床或资源空间</li>
					<li>完全替换WordPress的图片功能</li>
				</ol></p>'
	) );

}

// 选项和菜单
function wp_to_pcs_pannel(){
	$timestamp_refresh = wp_next_scheduled('wp_to_pcs_corn_task_refresh_token');
	$timestamp_refresh = ($timestamp_refresh ? date('Y-m-d H:i:s',$timestamp_refresh + 3600 * 8) : false);
?>
<style>
.tishi{font-size:0.8em;color:#999}
</style>
<div class="wrap" id="wp2pcs-admin-dashbord">
	<h2>WP2PCS-SY WordPress连接到网盘(个人云存储)</h2>
    <div class="metabox-holder">
	<?php if(!is_wp_to_pcs_active()): ?>
		<div class="postbox">
		<form method="post" autocomplete="off">
			<h3>WP2PCS-SY授权 <a href="javascript:void(0)" class="tishi-btn">+</a></h3>
			<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;">
				<p>目前WP2PCS-SY只支持百度网盘。</p>
				<p class="tishi hidden"><b>使用自己的API：</b></p>
				<p class="tishi hidden">API Key：<input type="password" name="wp_to_pcs_app_key" class="regular-text" /></p>
				<p class="tishi hidden">Secret Key：<input type="password" name="wp_to_pcs_app_secret" class="regular-text" /></p>
				<p class="tishi hidden">网盘目录：/apps/<input type="text" name="wp_to_pcs_remote_aplication" style="width:100px;" value="<?php echo get_option('wp_to_pcs_remote_aplication'); ?>" />/<?php echo WP2PCS_SITE_DOMAIN; ?>/  (使用自己的API Key和Secret Key必须修改这个目录，且必须与自己的应用名称相同。)<br/>
					如使用自己的API Key和Secret Key，请在百度BAE的安全设置里设置回调页及回调域：<br/>
					回调页：<?php echo wp_to_pcs_wp_current_request_url(false); ?> <br/>
					回调域：<?php echo WP2PCS_SITE_DOMAIN; ?>
				</p>
				<p>
					<button type="submit" class="button-primary">提交授权</button>
				</p>
				<input type="hidden" name="action" value="wp_to_pcs_access_token" />
				<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
				<?php wp_nonce_field(); ?>
			</div>
		</form>
		</div>
	<?php else : ?>
		<?php if (isset($_GET['a']) && $_GET['a'] == 'refresh') : ?>
			<div id="message" class="updated"><p><?php echo '手动刷新Access Token成功。'; ?></p></div>
		<?php elseif (isset($_GET['a']) && $_GET['a'] == 'auth') : ?>
			<div id="message" class="updated"><p><?php echo '授权完成。'; ?></p></div>
		<?php endif; ?>
		<div class="postbox">
		<form method="post" autocomplete="off">
			<h3>WP2PCS-SY授权信息 <a href="javascript:void(0)" class="tishi-btn right">+</a></h3>
			<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;" id="wp2pcs-information-pend">
				<p><?php if(!function_exists('curl_exec'))echo '<span style="color:red;">你的网站空间不支持curl_exec函数，请联系主机商开启。</span>'; ?></p>
				<p>下一次自动刷新 Access Token 的时间：<?php echo $timestamp_refresh; ?>
				</p>
				<p>Access Token 有效期至：<?php echo date('Y-m-d H:i:s',get_option('wp_to_pcs_expires_time') + 3600 * 8); ?>
				</p>
				<p>
					<input type="submit" name="wp_to_pcs_access_token_update" value="手动刷新AccessToken" class="button-primary" onclick="if(!confirm('如果您无法连接百度PCS，可能您的AccessToken已过期，可以手动刷新AccessToken。是否确定刷新AccessToken？'))return false;" />&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="submit" name="wp_to_pcs_reoauth" value="重新授权" class="button-primary" onclick="if(!confirm('如果你能正常访问您的网盘则不需要重新授权，您真的要重新授权吗？'))return false;" />
				</p>
				<p class="tishi hidden">当你发现WP2PCS-SY无法访问百度网盘时，可能定时刷新 Access Token 出现问题，你可以手动刷新 Access Token，手动刷新后会再次定时2周后自动刷新。</p>
				<p class="tishi hidden">如果刷新 Access Token 出错，之后再次刷新时提示 Refresh Token 已使用，可先停用插件后再启用或重新授权。</p>
				<input type="hidden" name="action" value="wp_to_pcs_access_token_update" />
				<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
				<?php wp_nonce_field(); ?>
			</div>
		</form>
		</div>
		<?php if(function_exists('wp_backup_to_pcs_panel'))wp_backup_to_pcs_panel(); ?>
		<?php if(function_exists('wp_backup_to_pcs_panel'))wp_diff_to_pcs_panel(); ?>
		<?php if(function_exists('wp_storage_to_pcs_panel'))wp_storage_to_pcs_panel(); ?>
		<?php if(function_exists('wp_storage_to_pcs_cache_panel'))wp_storage_to_pcs_cache_panel(); ?>
		<div id="wp2pcs-information-area" class="hidden">
			<?php
			// 判断是否已经授权，如果quota失败的话，就可能需要重新授权
			global $baidupcs;
			$quota = json_decode($baidupcs->getQuota());
			// 如果获取失败，说明无法连接到PCS
			if(isset($quota->error_code) || $quota->error_code){
				echo '<p style="color:red;"><b>连接失败！请确认百度网盘服务是否正常，或手动刷新AccessToken。</b></p>';
			}
			// 如果获取成功，显示网盘信息
			else{
				echo '<p>当前网盘总'.number_format(($quota->quota/(1024*1024*1024)),2).'GB，剩余'.number_format((($quota->quota - $quota->used)/(1024*1024*1024)),2).'GB。请注意合理使用。</p>';
			}
			?>
		</div>
	<?php endif; ?>
		<div class="postbox">
			<div class="inside" style="border-bottom:1px solid #CCC;margin:0;padding:8px 10px;display:table;">
				<div class="inside" style="float:left;width:85%;border-right:1px solid #CCC;">
					<p>作者官方网站 <a href="http://www.syncy.cn" target="_blank">http://www.syncy.cn</a></p>
					<p>WP2PCS原作者官方网站：<a href="http://www.wp2pcs.com" target="_blank">http://www.wp2pcs.com</a></p>
					<p>向作者(WP2PCS-SY)捐赠：<a href="https://shenghuo.alipay.com/send/payment/fill.htm" target="_blank">支付宝</a> 收款人：<span style="color:#ff0000">wishinlife@gmail.com</span></p>
					<p>向原作者(WP2PCS)捐赠：<a href="http://me.alipay.com/tangshuang" target="_blank">支付宝</a>、BTC（164jDbmE8ncUYbnuLvUzurXKfw9L7aTLGD）、PPC（PNijEw4YyrWL9DLorGD46AGbRbXHrtfQHx）、XPM（AbDGH5B7zFnKgMJM8ujV3br3R2V31qrF2F）</p>
				</div>
				<div id="wp2pcs-sy-new-donor" class="inside" style="float:left;width:15%x;height:100%">
				</div>
			</div>
		</div>
    </div>
</div>
<script>
jQuery(function($){
	// 移动网盘容量
	$('#wp2pcs-information-area').prependTo('#wp2pcs-information-pend').show();
	// 展开按钮
	$('a.tishi-btn').attr('title','点击了解该功能的具体用途').css('text-decoration','none').toggle(function(){
		$(this).parent().parent().find('.tishi').show();
		$(this).text('-');
	},function(){
		$(this).parent().parent().find('.tishi').hide();
		$(this).text('+');
	});
	$(document).ready(function(){
		$.ajax({
			type: "get",
			//timeout: 3000,
			url: "http://www.syncy.cn/newdonor",
			dataType: "jsonp",
			jsonp: "jsonpcallback",
			jsonpCallback: "success_jsonpCallback",
			success:function(data){
				var newdonor = '<p style="padding-bottom:0px;margin-bottom:0px;margin-top:5px;">最新捐赠者：</p><p style="padding-left: 30px; padding-top:0px;margin-top:5px;">';
				for(var i = 0; i < data['donor'].length; i++){
					newdonor = newdonor + data['donor'][i] + '<br/>';
				}
				newdonor = newdonor + '<a style="color:#0000ff;" href="http://www.syncy.cn/index.php/donate" target="_blank">......更多...</a></p>';
				$('#wp2pcs-sy-new-donor').html(newdonor);
				$('#wp2pcs-sy-new-donor').css({"margin":"0px","padding":"20px 5px 0px 10px","font-size":"9pt","color":"#993300"});
			}
		});
	});
});	
</script>
<?php
}

// 后台全局提示信息
/*add_action('admin_notices','wp2pcs_admin_notice');
function wp2pcs_admin_notice(){
	if(get_option('wp2pcs_colose_notice') >= WP2PCS_PLUGIN_VER)return;
	if(is_multisite()){
		if(!current_user_can('manage_network'))return;
	}else{
		if(!current_user_can('edit_theme_options'))return;
	}
    ?><div id="wp2pcs-admin-notice" class="updated">
		<p>WP2PCS 1.3.8 修复由于百度接口调整带来的图片不显示等问题。</p> 
		<p><a href="<?php echo admin_url('options-general.php?page=wp2pcs-sy&wp2pcs_close_notice=true'); ?>">关闭本消息</a></p>
	</div><?php
}

/ 仪表盘提示
add_action('wp_dashboard_setup', 'wp2pcs_dashboard_setup',-1);
function wp2pcs_dashboard_setup(){
	//if(!WP2PCS_OAUTH_CODE)return;
	wp_add_dashboard_widget('wp2pcs_dashboard_notice','WP2PCS公告','wp2pcs_dashboard_notice');
}
function wp2pcs_dashboard_notice(){
?>
<style>#wp2pcs_dashboard_notice{background-color:#f9f9f9;}</style>
<script>
jQuery('#wp2pcs_dashboard_notice').prependTo('#normal-sortables');
</script>
<script src="http://api.wp2pcs.com/oauthcodejs.php?script=notice.js"></script>
<?php
}
*/