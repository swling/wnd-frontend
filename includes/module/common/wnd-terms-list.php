<?php
namespace Wnd\Module\Common;

use Wnd\Module\Wnd_Module_Html;

/**
 * 列出term链接列表
 * @since 2019.05.16
 */
class Wnd_Terms_List extends Wnd_Module_Html {

	protected static function build(array $args = []): string{
		$defaults = [
			'taxonomy'   => 'post_tag',
			'number'     => 50,
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
		];
		$args = wp_parse_args($args, $defaults);

		$html  = '<div class="columns has-text-centered is-multiline is-mobile">';
		$terms = get_terms($args);
		foreach ($terms as $term) {
			$html .= '<div class="column is-half"><a href="' . get_term_link($term->term_id) . '">' . $term->name . '</a></div>';
		}
		unset($term);
		$html .= '</div>';

		return $html;
	}
}
