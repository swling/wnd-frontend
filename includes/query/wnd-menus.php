<?php
namespace Wnd\Query;

/**
 * 插件管理菜单
 * @since 0.9.11
 */
class Wnd_Menus extends Wnd_Query {

	protected static function query(array $args = []): array {
		$defaults = [
			'in_side' => false, // 是否为侧边栏
			'expand'  => true, // 是否展开首个子菜单
		];
		$args = wp_parse_args($args, $defaults);

		// 侧边栏 or 用户中心
		if ($args['in_side']) {
			$menus[] = static::get_nav_menus();
		} else {
			$menus[] = static::get_dashboard_menus();
		}

		$menus              = apply_filters('wnd_menus', $menus, $args);
		$menus[0]['expand'] = $args['expand'];
		return $menus;
	}

	protected static function get_dashboard_menus(): array {
		if (!is_user_logged_in()) {
			$menus = [];
		} elseif (wnd_is_manager()) {
			$menus = static::build_manager_dashboard_menus();
		} else {
			$menus = static::build_user_dashboard_menus();
		}

		return $menus;
	}

	protected static function build_user_dashboard_menus(): array {

		$menus = [
			'label'  => __('控制板', 'wnd'),
			'expand' => false, // 是否强制展开
			'items'  => [
				['title' => __('概览', 'wnd'), 'href' => ''],
				['title' => __('内容', 'wnd'), 'href' => 'user/wnd_user_posts_panel'],
				['title' => __('财务', 'wnd'), 'href' => 'user/wnd_finance_list'],
				['title' => __('资料', 'wnd'), 'href' => 'user/wnd_profile_form'],
				['title' => __('账户', 'wnd'), 'href' => 'user/wnd_account_form'],
				['title' => __('消息', 'wnd'), 'href' => 'user/wnd_mail_box'],
				['title' => __('附件', 'wnd'), 'href' => 'user/wnd_attachments'],
			],
		];

		return $menus;
	}

	protected static function build_manager_dashboard_menus(): array {
		$menus = [
			'label'  => __('控制板', 'wnd'),
			'expand' => false, // 是否强制展开
			'items'  => [
				['title' => __('概览', 'wnd'), 'href' => ''],
				['title' => __('审核', 'wnd'), 'href' => 'admin/wnd_admin_posts_panel'],
				['title' => __('统计', 'wnd'), 'href' => 'admin/wnd_finance_stats'],
				['title' => __('订单', 'wnd'), 'href' => 'admin/wnd_finance_list'],
				['title' => __('用户', 'wnd'), 'href' => 'admin/wnd_users_list'],
				['title' => __('内容', 'wnd'), 'href' => 'user/wnd_user_posts_panel'],
				['title' => __('财务', 'wnd'), 'href' => 'user/wnd_finance_list'],
				['title' => __('资料', 'wnd'), 'href' => 'user/wnd_profile_form'],
				['title' => __('账户', 'wnd'), 'href' => 'user/wnd_account_form'],
				['title' => __('消息', 'wnd'), 'href' => 'user/wnd_mail_box'],
				['title' => __('附件', 'wnd'), 'href' => 'user/wnd_attachments'],
			],
		];

		return $menus;
	}

	/**
	 * @since 2019.10.11
	 * 自定义类型顶部导航
	 */
	protected static function get_nav_menus(): array {
		// 获取所有公开的，有存档的自定义类型
		$all_post_types = get_post_types(
			[
				'public'      => true,
				'show_ui'     => true,
				'has_archive' => true,
			],
			'names',
			'and'
		);

		$items = [];
		foreach ($all_post_types as $post_type) {
			$items[] = ['title' => get_post_type_object($post_type)->label, 'href' => get_post_type_archive_link($post_type)];
		}
		unset($post_type);

		$menus = [
			'label'  => __('站点导航', 'wnd'),
			'expand' => false,
			'items'  => $items,
		];

		return $menus;
	}

}
