<?php
namespace Wnd\JsonGet;

/**
 *@since 0.9.11
 *
 *插件管理菜单
 */
class Wnd_Menus extends Wnd_JsonGet {

	protected static function query(array $args = []): array{
		$defaults = [
			'in_side' => false, // 是否为侧边栏
			'expand'  => true, // 是否展开首个子菜单
		];
		$args = wp_parse_args($args, $defaults);

		// 拓展菜单 API
		if (!is_user_logged_in()) {
			$default_menus = [];
		} elseif (wnd_is_manager()) {
			$default_menus = static::build_manager_menus();
		} else {
			$default_menus = static::build_user_menus();
		}

		$menus[]            = $default_menus;
		$menus              = apply_filters('wnd_menus', $menus, $args);
		$menus[0]['expand'] = $args['expand'];
		return $menus;
	}

	protected static function build_user_menus(): array{

		$menus = [
			'label'  => __('用户中心', 'wnd'),
			'expand' => false, // 是否强制展开
			'items'  => [
				['title' => '概览', 'href' => static::get_front_page_url() . '#'],
				['title' => '内容', 'href' => static::get_front_page_url() . '#wnd_user_posts_panel'],
				['title' => '财务', 'href' => static::get_front_page_url() . '#wnd_user_finance_panel'],
				['title' => '资料', 'href' => static::get_front_page_url() . '#wnd_profile_form'],
				['title' => '账户', 'href' => static::get_front_page_url() . '#wnd_account_form'],
				['title' => '消息', 'href' => static::get_front_page_url() . '#wnd_mail_box'],
			],
		];

		return $menus;
	}

	protected static function build_manager_menus(): array{
		$menus = [
			'label'  => __('管理中心', 'wnd'),
			'expand' => false, // 是否强制展开
			'items'  => [
				['title' => '概览', 'href' => static::get_front_page_url() . '#'],
				['title' => '审核', 'href' => static::get_front_page_url() . '#wnd_admin_posts_panel'],
				['title' => '统计', 'href' => static::get_front_page_url() . '#wnd_finance_stats'],
				['title' => '订单', 'href' => static::get_front_page_url() . '#wnd_finance_list'],
				['title' => '用户', 'href' => static::get_front_page_url() . '#wnd_users_list'],
				['title' => '内容', 'href' => static::get_front_page_url() . '#wnd_user_posts_panel'],
				['title' => '财务', 'href' => static::get_front_page_url() . '#wnd_user_finance_panel'],
				['title' => '资料', 'href' => static::get_front_page_url() . '#wnd_profile_form'],
				['title' => '账户', 'href' => static::get_front_page_url() . '#wnd_account_form'],
				['title' => '消息', 'href' => static::get_front_page_url() . '#wnd_mail_box'],
			],
		];

		return $menus;
	}

	/**
	 *前端页面 URL
	 */
	protected static function get_front_page_url(): string {
		return wnd_get_front_page_url();
	}
}
