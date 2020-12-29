<?php
namespace Wnd\Module;

/**
 *@since 0.9.11
 *
 *插件管理菜单
 */
class Wnd_Menus extends Wnd_Module {

	// 导航Tabs
	protected static function build($args = []): string{
		$defaults = [
			'inside'               => false,
			'expand_default_menus' => true,
		];
		$args = wp_parse_args($args, $defaults);

		$html = '<div class="wnd-menu menu">';
		$html .= '<ul class="menu-list">';

		// 拓展菜单 API
		$default_menus = wnd_is_manager() ? static::build_manager_menus($args['expand_default_menus']) : static::build_user_menus($args['expand_default_menus']);
		$html .= apply_filters('wnd_menus', $default_menus, $args);

		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	// 导航Tabs
	protected static function build_user_menus($expand_default_menus): string{
		$html = '<li>';
		$html .= '<a>' . __('用户中心', 'wnd') . '&nbsp;<i class="fas fa-chevron-down"></i></a>';
		$html .= $expand_default_menus ? '<ul>' : '<ul style="display:none">';

		$html .= '<li><a href="' . static::get_front_page_url() . '#">概览</a></li>';
		$html .= '<li class="wnd_user_posts_panel"><a href="' . static::get_front_page_url() . '#wnd_user_posts_panel">内容</a></li>';
		$html .= '<li class="wnd_user_finance_panel"><a href="' . static::get_front_page_url() . '#wnd_user_finance_panel">财务</a></li>';
		$html .= '<li class="wnd_profile_form"><a href="' . static::get_front_page_url() . '#wnd_profile_form">资料</a></li>';
		$html .= '<li class="wnd_account_form"><a href="' . static::get_front_page_url() . '#wnd_account_form">账户</a></li>';
		$html .= '<li class="wnd_mail_box"><a href="' . static::get_front_page_url() . '#wnd_mail_box">';
		$html .= '<span ' . (wnd_get_mail_count() ? 'data-badge="' . wnd_get_mail_count() . '"' : '') . '>消息</span>';
		$html .= '</a></li>';

		$html .= '</ul>';
		$html .= '</li>';
		return $html;
	}

	protected static function build_manager_menus($expand_default_menus) {
		$html = '<li>';
		$html .= '<a>' . __('用户中心', 'wnd') . '&nbsp;<i class="fas fa-chevron-down"></i></a>';
		$html .= $expand_default_menus ? '<ul>' : '<ul style="display:none">';

		$html .= '<li><a href="' . static::get_front_page_url() . '#">概览</a></li>';
		$html .= '<li class="wnd_admin_finance_panel"><a href="' . static::get_front_page_url() . '#wnd_admin_finance_panel">统计</a></li>';
		$html .= '<li class="wnd_user_posts_panel"><a href="' . static::get_front_page_url() . '#wnd_user_posts_panel">内容</a></li>';
		$html .= '<li class="wnd_user_finance_panel"><a href="' . static::get_front_page_url() . '#wnd_user_finance_panel">财务</a></li>';
		$html .= '<li class="wnd_user_list_table"><a href="' . static::get_front_page_url() . '#wnd_user_list_table">用户</a></li>';
		$html .= '<li class="wnd_profile_form"><a href="' . static::get_front_page_url() . '#wnd_profile_form">资料</a></li>';
		$html .= '<li class="wnd_account_form"><a href="' . static::get_front_page_url() . '#wnd_account_form">账户</a></li>';

		$html .= '</ul>';
		$html .= '</li>';
		return $html;
	}

	/**
	 *前端页面 URL
	 */
	protected static function get_front_page_url(): string{
		$ucenter_page = (int) wnd_get_config('ucenter_page');
		return $ucenter_page ? get_permalink($ucenter_page) : '';
	}
}
