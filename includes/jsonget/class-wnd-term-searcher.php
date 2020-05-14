<?php
namespace Wnd\JsonGet;

use Wnd\Model\Wnd_Term;

/**
 *@since 2020.05.13
 *搜索term
 *
 *@link https://developer.wordpress.org/reference/classes/WP_Term_Query/__construct/
 *@link https://developer.wordpress.org/reference/classes/wp_term_query/get_search_sql/
 *
 */
class Wnd_Term_Searcher extends Wnd_JsonGet {

	public static function get($args = []) {
		$defaults = [
			'taxonomy'   => 'category',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'search'     => '',
			'number'     => 20,
		];
		$args = wp_parse_args($args, $defaults);

		return Wnd_Term::get_terms_data($args);
	}
}
