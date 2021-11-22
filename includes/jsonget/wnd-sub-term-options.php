<?php
namespace Wnd\JsonGet;

use Wnd\Model\Wnd_Term;

/**
 * 列出指定 term 的 child term 下拉选项
 * @since 0.9.27
 */
class Wnd_Sub_Term_Options extends Wnd_JsonGet {

	protected static function query($args = []): array{
		$defaults = [
			'taxonomy'   => '',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'count',
			'order'      => 'DESC',
		];
		$args             = wp_parse_args($args, $defaults);
		$args['taxonomy'] = $args['taxonomy'] ?: get_term($args['parent'])->taxonomy;

		if (!$args['parent']) {
			return [];
		}

		$options = Wnd_Term::get_terms_data($args);
		if (!$options) {
			return [];
		}

		return $options;
	}
}
