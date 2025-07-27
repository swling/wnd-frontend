<?php
/**
 * Plugin Name: Wnd-Frontend
 * Plugin URI: https://github.com/swling/wnd-frontend
 * Description: Wnd-Frontend 是一套基于 ajax 交互逻辑的 WordPress 前端基础框架。商业用途需购买授权。<a href="https://github.com/swling/wnd-frontend/releases">更新日志</a>
 * Version: 0.9.90
 * Author: swling
 * Author URI: https://wndwp.com
 * Requires PHP: 8.0
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

use Wnd\Wnd_Init;

// 版本
define('WND_VER', '0.9.90');

// 定义插件网址路径
define('WND_URL', plugin_dir_url(__FILE__));

// 定义插件文件路径
define('WND_PATH', __DIR__);

// 定义插件文件夹名称
define('WND_DIR_NAME', basename(__DIR__));

// 定义语言参数
define('WND_LANG_KEY', 'lang');

// 定义推广参数
define('WND_AFF_KEY', 'aff');

// 定义当前插件入口文件名
define('WND_PLUGIN_FILE', __FILE__);

// 本插件定义了修订版本用于审核用途，故此需要彻底禁用 WP 默认版本行为
if (!defined('WP_POST_REVISIONS')) {
	define('WP_POST_REVISIONS', false);
}

// 自动加载器
require WND_PATH . DIRECTORY_SEPARATOR . 'wnd-autoloader.php';

// 初始化
Wnd_Init::get_instance();

/**
 * 加载静态资源
 *
 * @since 初始化
 */
add_action('wp_enqueue_scripts', 'wnd_enqueue_scripts');
function wnd_enqueue_scripts($hook_suffix = '') {
	// api 及语言本地化
	$wnd_data = [
		'dashboard_url'      => wnd_get_dashboard_url(),
		'rest_url'           => get_rest_url(),
		'rest_nonce'         => wp_create_nonce('wp_rest'),
		'disable_rest_nonce' => wnd_get_config('disable_rest_nonce'),
		'user_id'            => get_current_user_id(),
		'module_api'         => 'module',
		'action_api'         => 'action',
		'query_api'          => 'query',
		'posts_api'          => 'query/wnd_posts',
		'users_api'          => 'query/wnd_users',
		'endpoint_api'       => 'endpoint',
		'oss_direct_upload'  => wnd_is_oss_direct_upload(),
		'comment'            => [
			'api'      => 'action/common/wnd_add_comment',
			'order'    => get_option('comment_order'),
			'form_pos' => wnd_get_config('comment_form_pos') ?: 'top',
		],
		'is_admin'           => is_admin(),
		'lang'               => get_locale(),
		'ver'                => WND_VER,
		'color'              => [
			'primary' => wnd_get_config('primary_color'),
			'second'  => wnd_get_config('second_color'),
		],
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
	echo '<script>let wnd = ' . json_encode($wnd_data, JSON_UNESCAPED_UNICODE) . ';</script>' . PHP_EOL;

	// 公共脚本及样式库
	$static_path = WND_URL . 'static/';

	wp_enqueue_style('bulma', $static_path . 'css/bulma.min.css', [], WND_VER);
	wp_enqueue_style('font-awesome', $static_path . 'css/font-awesome-all.min.css', [], WND_VER);
	wp_enqueue_script('axios', $static_path . 'js/lib/axios.min.js', [], WND_VER);
	wp_enqueue_script('vue', $static_path . 'js/lib/vue.global.prod.js', [], WND_VER);
	wp_enqueue_script('wnd-main', $static_path . 'js/main.min.js', ['vue', 'axios'], WND_VER);

	if (is_singular() and comments_open()) {
		wp_enqueue_script('wnd-comment', $static_path . 'js/comment.min.js', ['axios', 'comment-reply'], WND_VER, ['strategy' => 'async']);
	}
}
