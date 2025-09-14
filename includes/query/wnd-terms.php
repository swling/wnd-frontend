<?php

namespace Wnd\Query;

use Exception;

/**
 * Terms 查询接口
 * @since 0.9.91
 */
class Wnd_Terms extends Wnd_Query {

	protected static function query($args = []): array {
		return static::get_terms($args);
	}

	// 回调函数
	private static function get_terms(array $args): array {
		$taxonomy = $args['taxonomy'] ?? '';
		$orderby  = $args['orderby'] ?? 'count';
		$number   = $args['number'] ?? '';

		if (!taxonomy_exists($taxonomy)) {
			throw new Exception('invalid_taxonomy: ' . $taxonomy);
		}

		$args = [
			'taxonomy'   => $taxonomy,
			'orderby'    => $orderby,
			'order'      => 'DESC',
			'number'     => $number,
			'hide_empty' => false, // 是否隐藏没有文章的 term，可根据需要调整
		];

		$terms = get_terms($args);

		if (is_wp_error($terms)) {
			return $terms;
		}

		// 格式化输出
		$data = [];
		foreach ($terms as $term) {
			$data[] = [
				'id'    => $term->term_id,
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => $term->count,
			];
		}

		return $data;
	}
}
