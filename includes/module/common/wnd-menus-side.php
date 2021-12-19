<?php
namespace Wnd\Module\Common;

use Wnd\Module\Wnd_Module_Html;

/**
 * 侧边栏菜单
 * @since 0.9.11
 */
class Wnd_Menus_Side extends Wnd_Module_Html {

	protected static function build(array $args = []): string{
		$html = '<aside id="menus-side" style="position: fixed;top: 0;height: 100%;z-index: 32;background: #FFF;max-width:100%;min-width:200px;overflow:auto;">';

		$html .= '<div class="columns is-marginless is-mobile">';
		$html .= '<div class="column">' . Wnd_Search_Form::render() . '</div>';
		$html .= '<div class="column is-narrow is-marginless is-paddingless">';
		$html .= '<div class="navbar-burger wnd-side-burger navbar-brand" style="display:block">';
		$html .= '<span></span><span></span><span></span>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		//未登录用户
		if (!is_user_logged_in()) {
			$html .= '<div class="has-text-centered mb-3">';
			$html .= wnd_modal_button('免费注册', 'user/wnd_user_center', [], 'is-black');
			$html .= '&nbsp;';
			$html .= wnd_modal_button('立即登录', 'user/wnd_user_center', ['do' => 'login'], 'is-danger is-outlined');
			$html .= '</div>';
		}

		$html .= apply_filters('wnd_menus_side_before', '');
		$html .= '<div id="wnd-menus-side"></div>';
		$html .= '<script>wnd_render_menus("#wnd-menus-side", false, true)</script>';
		$html .= apply_filters('wnd_menus_side_after', '');

		$html .= '</aside>';
		return $html;
	}
}
