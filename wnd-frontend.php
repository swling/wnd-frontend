<?php
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
define('WND_VER', '0.60');

// 定义插件网址路径
define('WND_URL', plugin_dir_url(__FILE__));

// 定义插件文件路径
define('WND_PATH', __DIR__);

// 加载核心文件
require WND_PATH . DIRECTORY_SEPARATOR . 'wnd-load.php';

/**
 *@since 初始化
 *插件安装卸载选项
 *
 */
register_activation_hook(__FILE__, 'Wnd\Model\Wnd_Admin::install');
register_deactivation_hook(__FILE__, 'Wnd\Model\Wnd_Admin::uninstall');

/**
 *@since 初始化
 *加载静态资源
 */
add_action('wp_enqueue_scripts', function () {
	wp_enqueue_script('wnd-frontend', WND_URL . 'static/js/wnd-frontend.min.js', array('jquery'), WND_VER);

	// bulma框架及fontawesome图标
	$static_host = wnd_get_option('wnd', 'wnd_static_host');
	if ('local' == $static_host) {
		wp_enqueue_style('bulma', WND_URL . 'static/css/bulma.min.css', [], null);
		wp_enqueue_style('bulma-extensions', WND_URL . 'static/css/bulma-extensions.min.css', [], null);
		wp_enqueue_style('font-awesome', WND_URL . 'static/css/font-awesome-all.min.css', [], null);
	} elseif ('jsdeliver' == $static_host) {
		wp_enqueue_style('bulma', '//cdn.jsdelivr.net/npm/bulma@0.7.5/css/bulma.min.css', [], null);
		wp_enqueue_style('bulma-extensions', '//cdn.jsdelivr.net/npm/bulma-extensions@6.2.7/dist/css/bulma-extensions.min.css', [], null);
		wp_enqueue_style('font-awesome', '//cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.7.2/css/all.min.css', [], null);
	}

	// api
	$wnd_data = array(
		'root_url'      => site_url(),
		'rest_nonce'    => wp_create_nonce('wp_rest'),
		'ajax_nonce'    => wnd_create_nonce('wnd_safe_action'),
		'interface_api' => '/wp-json/wnd/interface',
		'rest_api'      => '/wp-json/wnd/rest-api',
		'filter_api'    => '/wp-json/wnd/filter',
	);
	wp_localize_script('wnd-frontend', 'wnd', $wnd_data);
});
