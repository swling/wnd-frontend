<?php
namespace Wnd\JsonGet;

use Wnd\Model\Wnd_Term;

/**
 *@since 2020.04.14
 *列出term下拉选项
 **/
class Wnd_Sub_Terms extends Wnd_JsonGet {

	protected static function query($args = []): array{
		$defaults = [
			'taxonomy'   => '',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'count',
			'order'      => 'DESC',
		];
		$args = wp_parse_args($args, $defaults);

		if (!$args['parent']) {
			return [];
		}

		$args['taxonomy'] = $args['taxonomy'] ?: get_term($args['parent'])->taxonomy;

		return Wnd_Term::get_terms_data($args);
	}
}
