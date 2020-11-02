<?php
namespace Wnd\Module;

/**
 *@since 0.9.2
 *
 *插件默认用户页面菜单
 */
class Wnd_User_Menus extends Wnd_Module {

	// 导航Tabs
	public static function build(): string {
		if (wnd_is_manager()) {
			return static::build_manager_menus();
		} else {
			return static::build_user_menus();
		}
	}

	// 导航Tabs
	protected static function build_user_menus(): string{
		$html = '<div id="user-panel-tabs" class="tabs is-fullwidth column is-paddingless">';
		$html .= '<ul>';
		$html .= '<li class="wnd_user_center" class="is-active"><a href="">面板</a></li>';
		$html .= '<li class="wnd_user_posts_panel"><a href="#wnd_user_posts_panel">内容</a></li>';
		$html .= '<li class="wnd_user_finance_panel"><a href="#wnd_user_finance_panel">财务</a></li>';
		$html .= '<li class="wnd_profile_form"><a href="#wnd_profile_form">资料</a></li>';
		$html .= '<li class="wnd_account_form"><a href="#wnd_account_form">账户</a></li>';
		$html .= '<li class="wnd_mail_box"><a href="#wnd_mail_box">';
		$html .= '<span ' . (wnd_get_mail_count() ? 'data-badge="' . wnd_get_mail_count() . '"' : '') . '>消息</span>';
		$html .= '</a></li>';
		$html .= '</ul>';
		$html .= '</div>';
		return $html;
	}

	protected static function build_manager_menus() {
		$html = '<div id="user-panel-tabs" class="tabs is-fullwidth column is-paddingless">';
		$html .= '<ul>';
		$html .= '<li class="wnd_user_center" class="is-active"><a href="">面板</a></li>';
		$html .= '<li class="wnd_admin_posts_panel"><a href="#wnd_admin_posts_panel">审核</a></li>';
		$html .= '<li class="wnd_admin_finance_panel"><a href="#wnd_admin_finance_panel">统计</a></li>';
		$html .= '<li class="wnd_user_posts_panel"><a href="#wnd_user_posts_panel">内容</a></li>';
		$html .= '<li class="wnd_user_finance_panel"><a href="#wnd_user_finance_panel">财务</a></li>';
		$html .= '<li class="wnd_user_list_table"><a href="#wnd_user_list_table">用户</a></li>';
		$html .= '<li class="wnd_profile_form"><a href="#wnd_profile_form">资料</a></li>';
		$html .= '<li class="wnd_account_form"><a href="#wnd_account_form">账户</a></li>';
		$html .= '</ul>';
		$html .= '</div>';
		return $html;
	}
}
