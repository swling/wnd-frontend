<?php
namespace Wnd\Model;

/**
 *@since 2020.04.19
 *Term
 */
class Wnd_Term {

	/**
	 *获取当前文章已选择terms数组
	 *分类：返回ID数组
	 *标签：返回slug数组
	 */
	public static function get_post_current_terms($post_id, $taxonomy): array{
		$current_terms      = get_the_terms($post_id, $taxonomy) ?: [];
		$current_terms_data = [];
		foreach ($current_terms as $current_term) {
			$current_terms_data[] = is_taxonomy_hierarchical($taxonomy) ? $current_term->term_id : $current_term->slug;
		}
		unset($current_terms, $current_term);

		return $current_terms_data;
	}

	/**
	 *获取指定taxonomy下的terms数组键值对：通常用于前端构造html
	 *分类：[$term->name] => $term->term_id
	 *标签：[$term->name] => $term->slug
	 */
	public static function get_terms_data($args_or_taxonomy): array{
		$defaults = [
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'parent'     => 0,
		];

		$args        = is_array($args_or_taxonomy) ? $args_or_taxonomy : ['taxonomy' => $args_or_taxonomy];
		$args        = wp_parse_args($args, $defaults);
		$terms       = get_terms($args) ?: [];
		$option_data = [];
		foreach ($terms as $term) {
			// 如果分类名称为整数，则需要转换，否则数组会出错
			$name               = is_numeric($term->name) ? '(' . $term->name . ')' : $term->name;
			$option_data[$name] = is_taxonomy_hierarchical($args['taxonomy']) ? $term->term_id : $term->slug;
		}
		unset($term);

		return $option_data;
	}

	/**
	 *获取当前Term所处分类层级
	 *
	 *顶级分类：1
	 *子级分类：2
	 *孙级分类：3
	 *
	 *以此类推
	 *
	 *@param int 	$term_id
	 *@param string $taxonomy
	 *@return int | false
	 */
	public static function get_term_level($term_id, $taxonomy) {
		$ancestors = get_ancestors($term_id, $taxonomy, 'taxonomy');
		$count     = count($ancestors);
		return $count + 1;
	}

	/**
	 *@since 2020.04.19
	 *
	 *获取当前term 指定层级子类
	 *
	 *@return array 指定层级子类的 ids
	 */
	public static function get_term_children_by_level($term_id, $taxonomy, $child_level) {
		$children    = get_term_children($term_id, $taxonomy);
		$child_level = self::get_term_level($term_id, $taxonomy) + $child_level;

		$this_level = [];
		foreach ($children as $child_term_id) {
			if ($child_level == self::get_term_level($child_term_id, $taxonomy)) {
				$this_level[] = $child_term_id;
			}
		}

		return $this_level;
	}
}
