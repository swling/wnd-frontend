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
		$default_module = $user_id ? 'user/wnd_user_overview' : 'user/wnd_login_form';
		$defaults       = [
			'menus'       => static::get_menus($args),
			'admin_menus' => is_super_admin() ? static::get_admin_menus($args) : [],
			// 'menus'   => static::get_demo_menus(),
			'default'     => apply_filters('wnd_user_page_default_module', $default_module),
			'module'      => '',
			'query'       => $_GET,
			'user_id'     => $user_id,
		];
		$args = wp_parse_args($args, $defaults);

		// 构造页面 HTML
		wp_enqueue_script('dashboard', WND_URL . 'static/js/dashboard.min.js', ['wnd-main'], WND_VER);
		wp_enqueue_script('form-vue', WND_URL . 'static/js/form-vue.min.js', ['wnd-main'], WND_VER);
		get_header();
		$html = '<main id="dashboard-page" class="column is-flex">';
		$html .= '<script>const wnd_dashboard = ' . json_encode($args, JSON_UNESCAPED_UNICODE) . ';</script>';
		$html .= file_get_contents(WND_PATH . '/includes/module-vue/dashboard.vue');
		$html .= '</main>';
		echo $html;
		get_footer();

		return '';
	}

	private static function get_menus($args) {
		$menus = [
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
				'name' => __('订单', 'wnd'),
				'hash' => 'user/wnd_orders',
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
				'icon' => '<i class="fas fa-key"></i>', // 账户设置
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

		// 管理员：移除重复的 “概览” 菜单
		if (is_super_admin()) {
			array_shift($menus);
		}

		return apply_filters('wnd_menus', $menus, $args);
	}

	private static function get_admin_menus($args) {
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
				'name'     => __('运营', 'wnd'),
				'icon'     => '<i class="fas fa-chart-line"></i>',
				'open'     => false,
				'children' => [
					[
						'name' => __('发货', 'wnd'),
						'hash' => 'admin/wnd_order_processor',
						'icon' => '<i class="fas fa-shipping-fast"></i>', // 个人资料
					],
					[
						'name' => __('订单', 'wnd'),
						'hash' => 'admin/wnd_orders',
						'icon' => '<i class="fas fa-shopping-cart"></i>', // 个人资料
					],
					[
						'name' => __('充值', 'wnd'),
						'hash' => 'admin/wnd_recharges',
						'icon' => '<i class="fas fa-coins"></i>', // 账户设置
					],
					[
						'name' => __('统计', 'wnd'),
						'hash' => 'admin/wnd_finance_stats',
						'icon' => '<i class="fas fa-chart-bar"></i>', // 数据统计
					],
					[
						'name' => 'SKU',
						'hash' => 'admin/wnd_sku_keys_editor',
						'icon' => '<i class="fas fa-barcode"></i>', // SKU
					],
				],
			],
			[
				'name' => __('用户', 'wnd'),
				'hash' => 'admin/wnd_users_list',
				'icon' => '<i class="fas fa-users"></i>', // 用户列表
			],
			[
				'name' => __('附件', 'wnd'),
				'hash' => 'admin/wnd_attachments',
				'icon' => '<i class="fas fa-paperclip"></i>', // 附件
			],
			[
				'name' => __('系统', 'wnd'),
				'hash' => 'admin/wnd_system_monitor',
				'icon' => '<i class="fas fa-info-circle"></i>', // 系统信息
			],
		];

		return apply_filters('wnd_admin_menus', $menus, $args);

		return $menus;
	}
}
