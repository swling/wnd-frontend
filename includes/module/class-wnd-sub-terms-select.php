<?php
namespace Wnd\Module;

/**
 *@since 2020.04.14
 *列出term下拉子类
 **/
class Wnd_Sub_Terms_Select extends Wnd_Module {

	public static function build($args = []) {
		$defaults = [
			'taxonomy'   => 'category',
			'number'     => 50,
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'count',
			'order'      => 'DESC',
		];
		$args        = wp_parse_args($args, $defaults);
		$name        = '_term_' . $args['taxonomy'];
		$child_level = $args['child_level'] + 1;
		$tips        = $args['tips'];
		$required    = ('true' == $args['required']) ? ' required="required"' : '';

		$html  = '<select name="' . $name . '" data-child_level="' . $child_level . '" data-tips="' . $tips . '"' . $required . '>';
		$terms = get_terms($args);
		$html .= '<option value="-1">- ' . $args['tips'] . ' -</option>';
		foreach ($terms as $term) {
			$html .= '<option value="' . $term->term_id . '">' . $term->name . '</option>';
		}
		unset($term);
		$html .= '</select>';

		return $html;
	}
}
