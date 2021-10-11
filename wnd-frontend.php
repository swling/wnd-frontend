<?php
/**
 * Plugin Name: Wnd-Frontend
 * Plugin URI: https://github.com/swling/wnd-frontend
 * Description: Wnd-Frontend 是一套基于 ajax 交互逻辑的 WordPress 前端基础框架。商业用途需购买授权。<a href="https://github.com/swling/wnd-frontend/releases">更新日志</a>
 * Version: 0.9.39.6
 * Author: swling
 * Author URI: https://wndwp.com
 * Requires PHP: 7.3
 *
 * ## 万能的WordPress前端开发基础框架
 * - 第一版开发日期：2018.04 ~ 2018.08
 * - 官方网站： https://wndwp.com
 *
 * ## 二开或其他咨询
 * - QQ：245484493
 * - 邮箱：tangfou@gmail.com
 *
 * ## 交流 QQ 群：
 * - 801676983 （验证消息：wndwp）
 *
 * @since 2019.1.6 : git版本控制
 * @since 2019.1.8 ：GitHub开通免费私人仓库，正式托管于GitHub
 */

use Wnd\Model\Wnd_Admin;
use Wnd\Model\Wnd_Init;

// 版本
define('WND_VER', '0.9.39.6');

// 定义插件网址路径
define('WND_URL', plugin_dir_url(__FILE__));

// 定义插件文件路径
define('WND_PATH', __DIR__);

// 定义插件文件夹名称
define('WND_DIR_NAME', basename(__DIR__));

// 定义语言参数
define('WND_LANG_KEY', 'lang');

// 自动加载器
require WND_PATH . DIRECTORY_SEPARATOR . 'wnd-autoloader.php';

// 初始化
Wnd_Init::get_instance();

/**
 * 插件安装卸载选项
 * @since 初始化
 */
register_activation_hook(__FILE__, 'Wnd\Model\Wnd_Admin::install');
register_deactivation_hook(__FILE__, 'Wnd\Model\Wnd_Admin::uninstall');

/**
 * 插件更新触发升级操作
 * @since 0.9.2
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
			Wnd_Admin::upgrade();
			break;
		}
	}
}, 10, 2);

/**
 * 访问后台时候，触发执行升级及清理动作
 * @since 2019.04.16
 */
add_action('admin_init', 'Wnd\Model\Wnd_Admin::upgrade');
add_action('admin_init', 'Wnd\Model\Wnd_Admin::clean_up');

/**
 * 加载静态资源
 * @since 初始化
 */
add_action('wp_enqueue_scripts', 'wnd_enqueue_scripts');
function wnd_enqueue_scripts($hook_suffix = '') {
	// 公共脚本及样式库可选本地或 jsdeliver
	$static_host = wnd_get_config('static_host');
	if (!$static_host or 'local' == $static_host) {
		wp_enqueue_style('bulma', WND_URL . 'static/css/bulma.min.css', [], WND_VER);
		wp_enqueue_style('font-awesome', WND_URL . 'static/css/font-awesome-all.min.css', [], WND_VER);
		wp_enqueue_script('axios', WND_URL . 'static/js/lib/axios.min.js', [], WND_VER);
		wp_enqueue_script('vue', WND_URL . 'static/js/lib/vue.min.js', [], WND_VER);

		wp_enqueue_script('wnd-main', WND_URL . 'static/js/main.min.js', ['vue', 'axios'], WND_VER);
		if (is_singular() and comments_open()) {
			wp_enqueue_script('wnd-comment', WND_URL . 'static/js/comment.min.js', ['axios', 'comment-reply'], WND_VER);
		}
	} elseif ('jsdeliver' == $static_host) {
		wp_enqueue_style('bulma', '//cdn.jsdelivr.net/npm/bulma@0.9.0/css/bulma.min.css', [], null);
		wp_enqueue_style('font-awesome', '//cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.13.0/css/all.min.css', [], null);
		wp_enqueue_script('axios', '//cdn.jsdelivr.net/npm/axios@0.21.1/dist/axios.min.js', [], null);
		wp_enqueue_script('vue', '//cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.min.js', [], null);

		$jsdelivr_base = '//cdn.jsdelivr.net/gh/swling/wnd-frontend@' . WND_VER;
		wp_enqueue_script('wnd-main', $jsdelivr_base . '/static/js/main.min.js', ['vue', 'axios'], null);
		if (is_singular() and comments_open()) {
			wp_enqueue_script('wnd-comment', $jsdelivr_base . '/static/js/comment.min.js', ['axios', 'comment-reply'], null);
		}
	}

	// api 及语言本地化
	$wnd_data = [
		'rest_url'           => get_rest_url(),
		'rest_nonce'         => wp_create_nonce('wp_rest'),
		'disable_rest_nonce' => wnd_get_config('disable_rest_nonce'),
		'module_api'         => 'wnd/module',
		'action_api'         => 'wnd/action',
		'posts_api'          => 'wnd/posts',
		'users_api'          => 'wnd/users',
		'jsonget_api'        => 'wnd/jsonget',
		'endpoint_api'       => 'wnd/endpoint',
		'comment'            => [
			'api'      => 'wnd/comment',
			'order'    => get_option('comment_order'),
			'form_pos' => wnd_get_config('comment_form_pos') ?: 'top',
		],
		'fin_types'          => json_encode(Wnd_Init::get_fin_types()),
		'is_admin'           => is_admin(),
		'lang'               => $_GET[WND_LANG_KEY] ?? false,
		'ver'                => WND_VER,
		'msg'                => [
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

	wp_localize_script('wnd-main', 'wnd', $wnd_data);
}
