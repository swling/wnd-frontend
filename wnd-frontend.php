<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *Plugin Name: Wnd-Frontend
 *Plugin URI: https://wndwp.com
 *Description: 万能的WordPress是一套基于ajax交互逻辑的WordPress前端基础框架。使用本插件需遵循：署名-非商业性使用-相同方式共享 2.5。以下情况中使用本插件需支付授权费用：①用户主体为商业公司，盈利性组织。②个人用户基于本插件二次开发，且以付费形式出售的产品。
 *Version: 0.20
 *Author: swling
 *Author URI: https://wndwp.com
 *
 *万能的WordPress前端开发基础框架
 *
 *第一版开发日期：2018.04 ~ 2018.08
 *
 *@since 2019.1.6 : git版本控制
 *@since 2019.1.8 ：GitHub开通免费私人仓库，正式托管于GitHub
 */

/**
 *@since 初始化
 *插件基础配置
 */

// 版本
define('WND_VER', '0.23');

// 定义插件网址路径
define('WND_URL', plugin_dir_url(__FILE__));

// 定义插件文件路径
define('WND_PATH', plugin_dir_path(__FILE__));

// 加载核心文件
require WND_PATH . 'inc/index.php';

/**
 *@since 初始化
 *插件安装卸载选项
 *
 */
register_activation_hook(__FILE__, 'wnd_install');
register_deactivation_hook(__FILE__, 'wnd_uninstall');
function wnd_install() {

	// 数据表
	wnd_create_table();

	// 升级
	if (get_option('wnd_var') != WND_VER) {
		wnd_upgrade_02();
	}

	// 默认option数据
	if (!get_option('wnd')) {

		$default_option = array(

			'wnd_form_verify' => 1,
			'wnd_form_nonce_key' => wnd_random('16'),

			'wnd_default_style' => 1,
			'wnd_do_page' => 0,
			'wnd_pay_return_url' => get_option('home'),

			'wnd_disable_admin_panel' => 1,
			'wnd_unset_user_meta' => 1,

			'wnd_sms_appid' => '腾讯短信appid',
			'wnd_sms_appkey' => '腾讯短信appkey',
			'wnd_sms_sign' => get_option('blogname'),
			'wnd_sms_template' => '通用短信模板ID',
			'wnd_sms_template_r' => '注册短信模板ID',
			'wnd_sms_template_v' => '身份验证短信模板ID',
		);

		update_option('wnd', $default_option);

	}

	// 版本
	update_option('wnd_ver', WND_VER);

}
function wnd_uninstall() {
	// delete_option('wnd');
	return;
}

/**
 *@since 初始化
 *加载静态资源
 */
function wnd_scripts() {

	wp_enqueue_script('wnd-frontend', WND_URL . 'static/js/wnd-frontend.js', array('jquery'), WND_VER);

	if (wnd_get_option('wnd', 'wnd_default_style') != 0) {
		wp_enqueue_style('bulma', '//cdn.jsdelivr.net/npm/bulma@0.7.4/css/bulma.min.css', array(), WND_VER);
		wp_enqueue_style('font-awesome', '//cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.7.2/css/all.min.css', array(), WND_VER);
	}

	$wnd_data = array(
		'api_nonce' => wp_create_nonce('wp_rest'),
		'api_url' => site_url('wp-json/wnd/rest-api'),
	);

	wp_localize_script('wnd-frontend', 'wnd', $wnd_data);
}
add_action('wp_enqueue_scripts', 'wnd_scripts');
