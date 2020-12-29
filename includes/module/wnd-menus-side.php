<?php
namespace Wnd\Module;

use Wnd\Module\Wnd_Menus;
use Wnd\Module\Wnd_Module;
use Wnd\Module\Wnd_Search_Form;

/**
 *@since 0.9.11
 *
 *侧边栏菜单
 */
class Wnd_Menus_Side extends Wnd_Module {

	protected static function build($args = []): string{
		$html = '<aside id="menus-side">';

		$html .= '<div class="columns is-marginless">';
		$html .= '<div class="column">' . Wnd_Search_Form::render() . '</div>';
		$html .= '<div class="column is-narrow is-paddingless">';
		$html .= '<div class="navbar-burger navbar-brand is-pulled-right is-active">';
		$html .= '<span></span><span></span><span></span>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		//未登录用户
		if (!is_user_logged_in()) {
			$html .= '<div class="has-text-centered">';
			$html .= wnd_modal_button('免费注册', 'wnd_user_center', [], 'is-black');
			$html .= '&nbsp;';
			$html .= wnd_modal_button('立即登录', 'wnd_user_center', ['do' => 'login'], 'is-danger is-outlined');
			$html .= '</div>';
		} else {
			$html .= apply_filters('wnd_menus_side_before', '');
			$html .= Wnd_Menus::render(['inside' => true, 'expand_default_menus' => false]);
			$html .= apply_filters('wnd_menus_side_after', '');
		}

		$html .= '</aside>';
		return $html;
	}
}
