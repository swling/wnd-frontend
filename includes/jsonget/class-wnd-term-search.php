<?php
namespace Wnd\JsonGet;

use Wnd\Model\Wnd_Term;

/**
 *@since 2020.04.14
 *列出term下拉选项
 **/
class Wnd_term_search extends Wnd_Json {

	public static function get($args = []) {

		return [
			['name' => '测试', 'id' => 10],
		];

		$defaults = [
			'taxonomy'   => 'category',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'count',
			'order'      => 'DESC',
		];
		$args = wp_parse_args($args, $defaults);
		return Wnd_Term::get_terms_data($args);
	}
}
