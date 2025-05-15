<?php

namespace Wnd\Module;

use Wnd\Module\Wnd_Module_Html;

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
class Wnd_Dashboard extends Wnd_Module_Html {

	protected static function build(array $args = []): string {
		$user_id        = get_current_user_id();
		$default_module = $user_id ? 'user/wnd_user_overview' : 'user/wnd_user_center';
		$defaults       = [
			'menus'   => is_super_admin() ? static::get_admin_menus(): static::get_menus(),
			// 'menus'   => static::get_demo_menus(),
			'default' => apply_filters('wnd_user_page_default_module', $default_module),
			'module'  => '',
			'query'   => $_GET,
			'user_id' => $user_id,
		];
		$args = wp_parse_args($args, $defaults);

		// 构造页面 HTML
		get_header();
		echo '<main id="dashboard-page" class="column is-flex">';
		echo '<script>var wnd_dashboard = ' . json_encode($args, JSON_UNESCAPED_UNICODE) . ';</script>';
		$html = file_get_contents(WND_PATH . '/includes/module-vue/user/dashboard.vue');
		echo $html;
		echo '</main>';
		get_footer();

		return '';
	}

	private static function get_menus() {
		$menu = [
			[
				'name' => __('概览', 'wnd'),
				'hash' => 'index',
				'icon' => '<i class="fas fa-tachometer-alt"></i>', // 仪表盘/概览
			],
			[
				'name' => __('内容', 'wnd'),
				'hash' => 'user/wnd_user_posts_panel',
				'icon' => '<i class="fas fa-file-alt"></i>', // 文章/内容
			],
			[
				'name' => __('财务', 'wnd'),
				'hash' => 'user/wnd_finance_list',
				'icon' => '<i class="fas fa-wallet"></i>', // 钱包/财务
			],
			[
				'name' => __('资料', 'wnd'),
				'hash' => 'user/wnd_profile_form',
				'icon' => '<i class="fas fa-id-card"></i>', // 个人资料
			],
			[
				'name' => __('账户', 'wnd'),
				'hash' => 'user/wnd_account_form',
				'icon' => '<i class="fas fa-user-cog"></i>', // 账户设置
			],
			[
				'name' => __('消息', 'wnd'),
				'hash' => 'user/wnd_mail_box',
				'icon' => '<i class="fas fa-envelope"></i>', // 邮件/消息
			],
			[
				'name' => __('附件', 'wnd'),
				'hash' => 'user/wnd_attachments',
				'icon' => '<i class="fas fa-paperclip"></i>', // 附件
			],
		];

		return $menu;
	}

	private static function get_admin_menus() {
		$menus = [
			[
				'name' => __('概览', 'wnd'),
				'hash' => 'index',
				'icon' => '<i class="fas fa-tachometer-alt"></i>', // 仪表盘/概览
			],
			[
				'name' => __('审核', 'wnd'),
				'hash' => 'admin/wnd_admin_posts_panel',
				'icon' => '<i class="fas fa-check-square"></i>', // 审核
			],
			[
				'name' => __('统计', 'wnd'),
				'hash' => 'admin/wnd_finance_stats',
				'icon' => '<i class="fas fa-chart-bar"></i>', // 数据统计
			],
			[
				'name' => __('订单', 'wnd'),
				'hash' => 'admin/wnd_finance_list',
				'icon' => '<i class="fas fa-receipt"></i>', // 订单
			],
			[
				'name' => __('用户', 'wnd'),
				'hash' => 'admin/wnd_users_list',
				'icon' => '<i class="fas fa-users"></i>', // 用户列表
			],
			[
				'name' => __('内容', 'wnd'),
				'hash' => 'user/wnd_user_posts_panel',
				'icon' => '<i class="fas fa-file-alt"></i>', // 文章/内容
			],
			[
				'name' => __('财务', 'wnd'),
				'hash' => 'user/wnd_finance_list',
				'icon' => '<i class="fas fa-wallet"></i>', // 钱包/财务
			],
			[
				'name' => __('资料', 'wnd'),
				'hash' => 'user/wnd_profile_form',
				'icon' => '<i class="fas fa-id-card"></i>', // 个人资料
			],
			[
				'name' => __('账户', 'wnd'),
				'hash' => 'user/wnd_account_form',
				'icon' => '<i class="fas fa-user-cog"></i>', // 账户设置
			],
			[
				'name' => __('消息', 'wnd'),
				'hash' => 'user/wnd_mail_box',
				'icon' => '<i class="fas fa-envelope"></i>', // 邮件/消息
			],
			[
				'name' => __('附件', 'wnd'),
				'hash' => 'user/wnd_attachments',
				'icon' => '<i class="fas fa-paperclip"></i>', // 附件
			],
		];

		return $menus;
	}

	private static function get_demo_menus() {
		$menu = [
			[
				'name' => '我的信息',
				'icon' => '<i class="fas fa-user"></i>',
				'hash' => 'profile',
			],
			[
				'name'     => '订单管理',
				'icon'     => '<i class="fas fa-receipt"></i>',
				'open'     => true,
				'children' => [
					[
						'name' => '我的订单',
						'icon' => '<i class="fas fa-list"></i>',
						'hash' => 'user/wnd_finance_list',
					],
					[
						'name' => '发票信息',
						'icon' => '<i class="fas fa-file-invoice"></i>',
						'hash' => 'invoices',
					],
				],
			],
			[
				'name'     => '账户设置',
				'icon'     => '<i class="fas fa-cog"></i>',
				'open'     => true,
				'children' => [
					[
						'name' => '密码修改',
						'icon' => '<i class="fas fa-key"></i>',
						'hash' => 'user/wnd_account_form',
					],
					[
						'name' => '绑定信息',
						'icon' => '<i class="fas fa-link"></i>',
						'hash' => 'bindings',
					],
				],
			],
		];

		return $menu;
	}
}
