<?php
namespace Wnd\Module;

use Wnd\Module\Wnd_Menus;
use Wnd\Module\Wnd_Module;

/**
 *@since 0.9.11
 *
 *侧边栏菜单
 */
class Wnd_Menus_Side extends Wnd_Module {

	protected static function build($args = []): string {
		//未登录用户
		if (!is_user_logged_in()) {
			$html = '<aside id="menus-side">';
			$html .= '<div class="navbar-burger navbar-brand is-pulled-right is-active">';
			$html .= '<span></span><span></span><span></span>';
			$html .= '</div>';

			$html .= '<div class="box">' . wndt_search_form() . '</div>';
			$html .= wndt_get_post_type_menu();

			$html .= '<div class="has-text-centered">';
			$html .= wnd_modal_button('免费注册', 'wnd_user_center', [], 'is-black');
			$html .= '&nbsp;';
			$html .= wnd_modal_button('立即登录', 'wnd_user_center', ['do' => 'login'], 'is-danger is-outlined');
			$html .= '</div>';
			$html .= '</aside>';
			return $html;
		}

		$html = '<aside id="menus-side">';
		$html .= '<div class="navbar-burger navbar-brand is-pulled-right is-active">';
		$html .= '<span></span><span></span><span></span>';
		$html .= '</div>';
		$html .= apply_filters('wnd_menus_side_before', '');
		$html .= Wnd_Menus::render();
		$html .= apply_filters('wnd_menus_side_after', '');
		$html .= '</div>';
		return $html;
	}
}
