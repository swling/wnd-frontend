<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *Plugin Name: WndWP
 *Plugin URI: https://wndwp.com
 *Description: 万能的WordPress是一套基于ajax交互逻辑的WordPress前端基础框架。使用本插件需遵循：署名-非商业性使用-相同方式共享 2.5。以下情况中使用本插件需支付授权费用：①用户主体为商业公司，盈利性组织。②个人用户基于本插件二次开发，且以付费形式出售的产品。
 *Version: 0.18
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

$ver = '0.18';

/**
 *@since 初始化
 *插件基础配置
 */
// 定义插件网址路径
define('WNDWP_URL', plugin_dir_url(__FILE__));

// 定义插件文件路径
define('WNDWP_PATH', plugin_dir_path(__FILE__));

// 加载核心文件
require WNDWP_PATH . 'inc/index.php';

/**
 *@since 初始化
 *插件安装卸载选项
 *
 */
register_activation_hook(__FILE__, 'wnd_install');
register_deactivation_hook(__FILE__, 'wnd_uninstall');
function wnd_install() {

	if (is_admin()) {

		// 数据表
		wnd_create_table();

		// 默认option数据
		if (!get_option('wndwp', $default = false)) {

			$default_option = array(

				'wnd_enable_white_list' => 1,
				// 'wnd_allowed_post_field' => 'post_title,post_excerpt,post_content,post_parent',
				'wnd_allowed_post_meta_key' => '',
				'wnd_allowed_wp_post_meta_key' => 'price',
				'wnd_allowed_user_meta_key' => '',
				'wnd_allowed_wp_user_meta_key' => 'description',

				'wnd_default_style' => 1,
				'wnd_do_page' => 0,
				'wnd_pay_return_url' => get_option('home'),

				'wnd_disable_admin_panel' => 1,
				'wnd_unset_user_meta' => 0,

				'wnd_ali_accessKeyId' => '阿里短信KeyId',
				'wnd_ali_accessKeySecret' => '阿里短信KeySecret',
				'wnd_ali_SignName' => get_option('blogname'),
				'wnd_ali_TemplateCode' => 'SMS_76590738',
				'wnd_ali_TemplateCode_R' => 'SMS_76590740',
				'wnd_ali_TemplateCode_V' => 'SMS_76590738',
			);

			update_option('wndwp', $default_option, $autoload = null);

		}
	}

}
function wnd_uninstall() {
	// delete_option('wndwp');
	return;
}

/**
 *@since 初始化
 *加载静态资源
 */
function wnd_scripts() {

	global $ver;
	wp_enqueue_script('wndwp', WNDWP_URL . 'static/js/wndwp.js', array('jquery'), $ver);

	if (wnd_get_option('wndwp', 'wnd_default_style') != 0) {
		wp_enqueue_style('bulma', '//cdn.jsdelivr.net/npm/bulma@0.7.4/css/bulma.min.css', array(), $ver);
		wp_enqueue_style('font-awesome', '//cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.7.2/css/all.min.css', array(), $ver);
	}
}
add_action('wp_enqueue_scripts', 'wnd_scripts');

/**
 *@since 初始化
 * WndWP头部
 */
add_action('wp_head', 'wnd_head');
function wnd_head() {
	// 头部引入WordPress ajaxurl
	?>
<script type="text/javascript" >var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';</script>
<?php

}
