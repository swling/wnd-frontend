<?php
namespace Wnd\Module\Common;

use Wnd\Module\Wnd_Module_Html;

/**
 * 搜索框
 * @since 0.8.64
 */
class Wnd_Search_Form extends Wnd_Module_Html {

	protected static function build(): string {
		// 色调
		$primary_color = wnd_get_config('primary_color');

		$html = '<form role="search" method="get" id="searchform" action="' . home_url() . '">';
		$html .= '<div class="field has-addons has-addons-right">';
		$html .= '<p class="control is-expanded">';
		$html .= '<input class="input" type="text" name="s" placeholder="' . __('关键词', 'wnd') . '" required="required">';
		$html .= '<input type="hidden" name="lang" value="' . ($_GET[WND_LANG_KEY] ?? '') . '">';
		$html .= '</p>';
		$html .= '<div class="control">';
		$html .= '<input type="submit" class="button is-' . $primary_color . '" value="' . __('搜索', 'wnd') . '" />';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</form>';

		return $html;
	}
}
