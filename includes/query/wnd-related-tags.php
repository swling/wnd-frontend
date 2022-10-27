<?php
namespace Wnd\Query;

use Wnd\Model\Wnd_Tag_Under_Category;

/**
 * 列出term下拉选项
 * @since 2020.04.14
 */
class Wnd_Related_Tags extends Wnd_Query {

	protected static function query($args = []): array{
		$defaults = [
			'term_id'  => 0,
			'taxonomy' => '',
		];
		$args = wp_parse_args($args, $defaults);
		extract($args);

		$tag_taxonomy = str_replace('_cat', '_tag', $taxonomy);

		$tags = Wnd_Tag_Under_Category::get_tags($term_id, $tag_taxonomy, $limit = 50);

		$data = [];
		foreach ($tags as $tag) {
			$data[] = get_term($tag->tag_id)->name ?? '';
		}unset($tags, $tag);

		return wnd_array_filter($data);
	}
}
