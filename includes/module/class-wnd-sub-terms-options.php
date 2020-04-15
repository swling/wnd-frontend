<?php
namespace Wnd\Module;

/**
 *@since 2020.04.14
 *列出term下拉选项
 **/
class Wnd_Sub_Terms_Options extends Wnd_Module {

	public static function build($args = []) {
		$defaults = [
			'taxonomy'   => 'category',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'count',
			'order'      => 'DESC',
		];
		$args  = wp_parse_args($args, $defaults);
		$terms = get_terms($args);

		$tips = $args['tips'];
		$html = '';

		$html .= '<option value="-1">- ' . $args['tips'] . ' -</option>';
		foreach ($terms as $term) {
			$html .= '<option value="' . $term->term_id . '">' . $term->name . '</option>';
		}
		unset($term);

		return $html;
	}
}
