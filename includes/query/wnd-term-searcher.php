<?php
namespace Wnd\Query;

use Wnd\Model\Wnd_Term;

/**
 * 搜索term
 * 如果需要限制返回结果数目，即设置number参数，必须确保parent参数为 空\false，否则number参数将无效
 *
 * @link https://developer.wordpress.org/reference/classes/WP_Term_Query/__construct/
 * @link https://developer.wordpress.org/reference/classes/wp_term_query/get_search_sql/
 * @see class WP_Term_Query
 * @since 2020.05.13
 */
class Wnd_Term_Searcher extends Wnd_Query {

	protected static function query($args = []): array {
		$defaults = [
			'taxonomy'   => 'post_tag',
			'parent'     => '',
			'hide_empty' => false,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'search'     => '',
			'number'     => 20,
		];
		$args = wp_parse_args($args, $defaults);

		if (!$args['search']) {
			return [];
		}

		// 移除全部钩子：如中文简繁体自动翻译钩子，将导致输出名称与数据库名称不一致
		remove_all_filters('get_term');

		return Wnd_Term::get_terms_data($args);
	}
}
