<?php
/**
 * Plugin Name: Wnd-Frontend
 * Plugin URI: https://github.com/swling/wnd-frontend
 * Description: Wnd-Frontend 是一套基于 ajax 交互逻辑的 WordPress 前端基础框架。商业用途需购买授权。<a href="https://github.com/swling/wnd-frontend/releases">更新日志</a>
 * Version: 0.9.58.3
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

use Wnd\Model\Wnd_Init;

// 版本
define('WND_VER', '0.9.58.3');

// 定义插件网址路径
define('WND_URL', plugin_dir_url(__FILE__));

// 定义插件文件路径
define('WND_PATH', __DIR__);

// 定义插件文件夹名称
define('WND_DIR_NAME', basename(__DIR__));

// 定义语言参数
define('WND_LANG_KEY', 'lang');

// 定义当前插件入口文件名
define('WND_PLUGIN_FILE', __FILE__);

// 自动加载器
require WND_PATH . DIRECTORY_SEPARATOR . 'wnd-autoloader.php';

// 初始化
Wnd_Init::get_instance();

/**
 * 加载静态资源
 * @since 初始化
 */
add_action('wp_enqueue_scripts', 'wnd_enqueue_scripts');
function wnd_enqueue_scripts($hook_suffix = '') {
	// 公共脚本及样式库可选本地或 jsdeliver
	$static_host = wnd_get_config('static_host');
	$static_path = WND_URL . 'static/';
	if (!$static_host or 'local' == $static_host or is_admin()) {
		wp_enqueue_style('bulma', $static_path . 'css/bulma.min.css', [], WND_VER);
		wp_enqueue_style('font-awesome', $static_path . 'css/font-awesome-all.min.css', [], WND_VER);
		wp_enqueue_script('axios', $static_path . 'js/lib/axios.min.js', [], WND_VER);
		wp_enqueue_script('vue', $static_path . 'js/lib/vue.global.prod.js', [], WND_VER);
	} elseif ('jsdeliver' == $static_host) {
		wp_enqueue_style('bulma', '//lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/bulma/0.9.3/css/bulma.min.css', [], null);
		wp_enqueue_style('font-awesome', '//lf9-cdn-tos.bytecdntp.com/cdn/expire-1-M/font-awesome/5.15.4/css/all.min.css', [], null);
		wp_enqueue_script('axios', '//lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/axios/0.26.0/axios.min.js', [], null);
		wp_enqueue_script('vue', '//lf9-cdn-tos.bytecdntp.com/cdn/expire-1-M/vue/3.2.31/vue.global.prod.js', [], null);
	}
	wp_enqueue_script('wnd-main', $static_path . 'js/main.min.js', ['vue', 'axios'], WND_VER);
	if (is_singular() and comments_open()) {
		wp_enqueue_script('wnd-comment', $static_path . 'js/comment.min.js', ['axios', 'comment-reply'], WND_VER);
	}

	// api 及语言本地化
	$wnd_data = [
		'rest_url'           => get_rest_url(),
		'rest_nonce'         => wp_create_nonce('wp_rest'),
		'disable_rest_nonce' => wnd_get_config('disable_rest_nonce'),
		'module_api'         => 'module',
		'action_api'         => 'action',
		'posts_api'          => 'posts',
		'users_api'          => 'users',
		'jsonget_api'        => 'jsonget',
		'endpoint_api'       => 'endpoint',
		'comment'            => [
			'api'      => 'comment',
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
