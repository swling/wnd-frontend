<?php
namespace Wnd\Model;

/**
 * Term
 * @since 2020.04.19
 */
abstract class Wnd_Term {

	/**
	 * 获取当前文章已选择terms数组
	 * 分类：返回{[{$slug}=>${term_id}]数组
	 * 标签：返回[{$slug}=>{$name}]数组
	 */
	public static function get_post_terms($post_id, $taxonomy): array{
		if (!$post_id) {
			return [];
		}

		$current_terms      = get_the_terms($post_id, $taxonomy) ?: [];
		$current_terms_data = [];
		foreach ($current_terms as $current_term) {
			$current_terms_data[$current_term->slug] = is_taxonomy_hierarchical($taxonomy) ? $current_term->term_id : $current_term->name;
		}
		unset($current_terms, $current_term);

		return $current_terms_data;
	}

	/**
	 * 获取当前 post 已设定的 terms 并按以层级为 key 值（仅针对包含层级关系的 taxonomy）
	 * 用于编辑内容时，根据当前 Post 之前数据，根据分类层级设定下拉 Selected 值
	 * @since 0.9.27
	 */
	public static function get_post_terms_with_level($post_id, $taxonomy): array{
		if (!is_taxonomy_hierarchical($taxonomy)) {
			return [];
		}

		$terms = static::get_post_terms($post_id, $taxonomy);
		$data  = [];
		foreach ($terms as $term_id) {
			$level        = static::get_term_level($term_id, $taxonomy);
			$data[$level] = $term_id;
		}

		return $data;
	}

	/**
	 * 根据当前 post 已选各层级 terms 查询对应层级的其他选项，并与首层 term 组合返回
	 * 用于编辑内容时，自动载入当前 post 各层级分类法
	 * @since 0.9.27
	 */
	public static function get_post_terms_options_with_level($post_id, $args_or_taxonomy): array{
		$args            = is_array($args_or_taxonomy) ? $args_or_taxonomy : ['taxonomy' => $args_or_taxonomy];
		$taxonomy        = $args['taxonomy'] ?? '';
		$taxonomy_object = get_taxonomy($taxonomy);

		if (!is_taxonomy_hierarchical($taxonomy) or !$taxonomy_object) {
			return [];
		}

		// 默认选项
		$default_option = ['- ' . $taxonomy_object->labels->name . ' -' => ''];

		// 首层选项
		$option_data[0] = array_merge($default_option, static::get_terms_data($args));

		// 查询当前 POST 已选 term 的子类同层级选项
		$current_term_ids = static::get_post_terms_with_level($post_id, $taxonomy);
		for ($i = 0, $j = count($current_term_ids); $i < $j; $i++) {
			$term_id     = $current_term_ids[$i];
			$child_terms = static::get_term_children_by_level($term_id, $taxonomy, 1);

			if ($child_terms) {
				$option_data[$i + 1] = array_merge($default_option, $child_terms);
			}
		}

		return $option_data;
	}

	/**
	 * 获取指定taxonomy下的terms数组键值对：通常用于前端构造html
	 * 分类：[$term->name => $term->term_id,...]
	 * 标签：[$term->name => $term->name,...]
	 *
	 * 		因此，为避免这种意外，请确保同一个taxonomy下，各个分类名称唯一。
	 * @see	注意：根据上述数据结构，我们得知，如果同一个taxonomy中存在多个同名分类，将仅返回一个数据。
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
			$option_data[$name] = is_taxonomy_hierarchical($args['taxonomy']) ? $term->term_id : $term->name;
		}
		unset($term);

		return $option_data;
	}

	/**
	 * 获取当前Term所处分类层级
	 * 顶级分类：0
	 * 子级分类：1
	 * 孙级分类：2
	 * 以此类推
	 *
	 * @param  int    	$term_id
	 * @param  string $taxonomy
	 * @return int    | false
	 */
	public static function get_term_level($term_id, $taxonomy) {
		$ancestors = get_ancestors($term_id, $taxonomy, 'taxonomy');
		$count     = count($ancestors);
		return $count;
	}

	/**
	 * 获取当前term 指定层级子类
	 * @since 2020.04.19
	 *
	 * @return array 指定层级子类的 ids [$term_name => $child_term_id]
	 */
	public static function get_term_children_by_level($term_id, $taxonomy, $child_level) {
		$children    = get_term_children($term_id, $taxonomy);
		$child_level = static::get_term_level($term_id, $taxonomy) + $child_level;

		$this_level = [];
		foreach ($children as $child_term_id) {
			if ($child_level == static::get_term_level($child_term_id, $taxonomy)) {
				$term_name              = get_term($child_term_id, $taxonomy)->name;
				$this_level[$term_name] = $child_term_id;
			}
		}

		return $this_level;
	}
}
