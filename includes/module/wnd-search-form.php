<?php
namespace Wnd\Module;

/**
 *@since 0.8.64
 *
 *搜索框
 *
 */
class Wnd_Search_Form extends Wnd_Module {

	public static function build() {
		// 色调
		$primary_color = wnd_get_config('primary_color');

		$html = '<form role="search" method="get" id="searchform" action="' . home_url() . '">';
		$html .= '<div class="field has-addons has-addons-right">';
		$html .= '<p class="control">';
		$html .= '<span class="select">';
		$html .= '<select name="type">';
		$html .= ' <option value="any"> - ' . __('全站', 'wnd') . '- </option>';
		foreach (get_post_types(['public' => true, 'has_archive' => true], 'object', 'and') as $post_type) {
			$html .= '<option value="' . $post_type->name . '">' . $post_type->label . '</option>';
		}
		$html .= '</select>';
		$html .= '</span>';
		$html .= '</p>';
		$html .= '<p class="control is-expanded">';
		$html .= '<input class="input" type="text" name="s" placeholder="' . __('关键词', 'wnd') . '" required="required">';
		$html .= '<input type="hidden" name="lang" value="' . ($_GET['lang'] ?? '') . '">';
		$html .= '</p>';
		$html .= '<p class="control">';
		$html .= '<input type="submit" class="button is-' . $primary_color . '" value="' . __('搜索', 'wnd') . '" />';
		$html .= '</p>';
		$html .= '</div>';
		$html .= '</form>';

		return $html;
	}
}
