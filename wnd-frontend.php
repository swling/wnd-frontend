<?php
/**
 *Plugin Name: Wnd-Frontend
 *Plugin URI: https://github.com/swling/wnd-frontend
 *Description: 万能的WordPress是一套基于ajax交互逻辑的WordPress前端基础框架。使用本插件需遵循：署名-非商业性使用-相同方式共享 2.5。以下情况中使用本插件需支付授权费用：①用户主体为商业公司，盈利性组织。②个人用户基于本插件二次开发，且以付费形式出售的产品。
 *Version: 0.9.3
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
define('WND_VER', '0.9.3');

// 定义插件网址路径
define('WND_URL', plugin_dir_url(__FILE__));

// 定义插件文件路径
define('WND_PATH', __DIR__);

// 定义插件文件夹名称
define('WND_DIR_NAME', basename(__DIR__));

// 自动加载器
require WND_PATH . DIRECTORY_SEPARATOR . 'wnd-autoloader.php';

// 初始化
Wnd\Model\Wnd_Init::get_instance();

/**
 *@since 初始化
 *插件安装卸载选项
 */
register_activation_hook(__FILE__, 'Wnd\Model\Wnd_Admin::install');
register_deactivation_hook(__FILE__, 'Wnd\Model\Wnd_Admin::uninstall');

/**
 *@since 2020.08.19
 *访问后台时候，触发升级
 */
add_action('admin_init', 'Wnd\Model\Wnd_Admin::upgrade');

/**
 *插件更新触发升级操作
 *@since 0.9.2
 */
add_action('upgrader_process_complete', function ($upgrader_object, $options) {
	if ($options['action'] != 'update') {
		return false;
	}

	if ($options['type'] != 'plugin') {
		return false;
	}

	$current_plugin_path_name = plugin_basename(__FILE__);
	foreach ($options['plugins'] as $each_plugin) {
		if ($each_plugin == $current_plugin_path_name) {
			Wnd\Model\Wnd_Admin::upgrade();
			break;
		}
	}
}, 10, 2);

/**
 *@since 2019.04.16
 *访问后台时候，触发执行清理动作
 */
add_action('admin_init', 'Wnd\Model\Wnd_Admin::clean_up');

/**
 *@since 初始化
 *加载静态资源
 */
add_action('wp_enqueue_scripts', 'wnd_enqueue_scripts');

/**
 *加载静态资源
 */
function wnd_enqueue_scripts($hook_suffix = '') {
	wp_enqueue_script('wnd-frontend', WND_URL . 'static/js/wnd-frontend.min.js', ['jquery'], WND_VER);

	// bulma框架及fontawesome图标
	$static_host = wnd_get_config('static_host');
	if (!$static_host or 'local' == $static_host) {
		wp_enqueue_style('bulma', WND_URL . 'static/css/bulma.min.css', [], WND_VER);
		wp_enqueue_style('bulma-extensions', WND_URL . 'static/css/bulma-extensions.min.css', [], WND_VER);
		wp_enqueue_style('font-awesome', WND_URL . 'static/css/font-awesome-all.min.css', [], WND_VER);
	} elseif ('jsdeliver' == $static_host) {
		wp_enqueue_style('bulma', '//cdn.jsdelivr.net/npm/bulma@0.9.0/css/bulma.min.css', [], null);
		wp_enqueue_style('bulma-extensions', '//cdn.jsdelivr.net/npm/bulma-extensions@6.2.7/dist/css/bulma-extensions.min.css', [], null);
		wp_enqueue_style('font-awesome', '//cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.13.0/css/all.min.css', [], null);
	}

	// api及语言本地化
	$wnd_data = [
		'rest_url'          => rtrim(strtok(get_rest_url(), '?'), '/') . '/',
		'rest_nonce'        => wp_create_nonce('wp_rest'),
		'safe_action_nonce' => wp_create_nonce('wnd_safe_action'),
		'interface_api'     => 'wnd/interface',
		'action_api'        => 'wnd/handler',
		'posts_api'         => 'wnd/posts',
		'users_api'         => 'wnd/users',
		'jsonget_api'       => 'wnd/jsonget',
		'lang'              => $_GET['lang'] ?? false,
		'msg'               => [
			'required'            => __('必填项为空', 'wnd'),

			'submit_successfully' => __('提交成功', 'wnd'),
			'submit_failed'       => __('提交失败', 'wnd'),

			'upload_successfully' => __('上传成功', 'wnd'),
			'upload_failed'       => __('上传失败', 'wnd'),

			'send_successfully'   => __('发送成功', 'wnd'),
			'send_failed'         => __('发送失败', 'wnd'),

			'confirm'             => __('确定'),
			'deleted'             => __('已删除', 'wnd'),
			'system_error'        => __('系统错误', 'wnd'),
			'waiting'             => __('请稍后', 'wnd'),
			'downloading'         => __('下载中', 'wnd'),
			'try_again'           => __('再试一次', 'wnd'),
			'view'                => __('查看', 'wnd'),
		],
	];
	wp_localize_script('wnd-frontend', 'wnd', $wnd_data);
}
