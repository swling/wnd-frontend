<?php

namespace Wnd\Module\User;

use Wnd\Module\Wnd_Module_Html;
use Wnd\Query\Wnd_Menus;

/**
 * 封装前端中心页面
 * Template Name: 用户中心
 *
 * 页面功能：
 * - $_GET['module']    呈现对应 UI 模块
 * - #module=post_form  调用对应内容发布/编辑表单模块
 * - #module=XXXX       默认为用户中心：注册、登录、账户管理，内容管理，财务管理等
 *
 * @since 0.9.0
 */
class Wnd_Front_Page extends Wnd_Module_Html {

	protected static function build(array $args = []): string {
		$user_id        = get_current_user_id();
		$default_module = $user_id ? 'user/wnd_user_overview' : 'user/wnd_user_center';
		$defaults = [
			'menus'   => Wnd_Menus::get(),
			'default' => apply_filters('wnd_user_page_default_module', $default_module),
			'module'  => '',
			'query'   => $_GET,
			'user_id' => $user_id
		];
		$args = wp_parse_args($args, $defaults);

		// 构造页面 HTML
		get_header();
		echo '<main id="user-page-container" class="column">';
		echo '<script>var wnd_dashboard = ' . json_encode($args, JSON_UNESCAPED_UNICODE) . ';</script>';
		$html = file_get_contents(WND_PATH . '/includes/module-vue/user/dashboard.vue');
		echo $html;
		echo '</main>';
		get_footer();

		return '';
	}
}
