<?php

namespace Wnd\Query;

use Exception;
use Wnd\Model\Wnd_Post;

/**
 * 获取 Post 编辑数据
 * 若未指定 post id 则返回新增 post 的基础数据
 * @since 0.9.81
 */
class Wnd_Get_Post_Edit extends Wnd_Query {

	protected static function check($args = []) {
		$post_id = (int) ($args['post_id'] ?? 0);
		if (!$post_id) {
			return;
		}

		// 检查是否有权限
		if (!current_user_can('edit_posts', $post_id)) {
			throw new Exception('Permission Denied');
		}
	}

	protected static function query($args = []): array {
		// 获取参数
		$post_id   = (int) ($args['post_id'] ?? 0);
		$post_type = $args['post_type'] ?? 'post';
		$post_id   = $post_id ?: Wnd_Post::get_draft($post_type);
		if ($post_id) {
			$post = get_post($post_id);
			if (!$post) {
				throw new Exception('Invalid Post ID');
			}
			$post_type = $post->post_type;
		}

		// 获取所有注册的分类法
		$taxonomies   = get_object_taxonomies($post_type);
		$terms_by_tax = [];
		$options      = [];
		foreach ($taxonomies as $taxonomy) {
			$options[$taxonomy] = static::get_terms_hierarchy($taxonomy);

			if (!$post_id) {
				continue;
			}

			$terms = get_the_terms($post_id, $taxonomy);
			if (!empty($terms) && !is_wp_error($terms)) {
				$terms_by_tax[$taxonomy] = $terms;
			}
		}

		// 返回数据
		return [
			'terms'        => $terms_by_tax,
			'term_options' => $options,
			'meta'         => static::get_post_meta($post_id),
			'post'         => $post,
		];
	}

	/**
	 * 获取分类法的层级结构
	 *
	 * @param string $taxonomy 分类法名称
	 * @return array 分类法的层级结构
	 *
	 * @copyright ChatGPT
	 */
	private static function get_terms_hierarchy($taxonomy) {
		$terms = get_terms([
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		]);

		if (is_wp_error($terms)) {
			return [];
		}

		// 转换为 ID => term 的映射
		$term_map = [];
		foreach ($terms as $term) {
			$term_map[$term->term_id] = [
				'term_id'  => $term->term_id,
				'name'     => $term->name,
				'slug'     => $term->slug,
				'children' => [],
				'parent'   => $term->parent,
			];
		}

		// 构建层级结构 （&$term 是为了让你构建的树结构共享相同的内存对象，实现嵌套关系，而不是简单地拷贝一份数据。）
		$tree = [];
		foreach ($term_map as $term_id => &$term) {
			if ($term['parent'] && isset($term_map[$term['parent']])) {
				$term_map[$term['parent']]['children'][] = &$term;
			} else {
				$tree[] = &$term;
			}
		}

		return $tree;
	}

	/**
	 * 获取 post meta 的 JSON 格式
	 *
	 * @param int $post_id Post ID
	 * @return array 返回的 meta 数据
	 * @copyright ChatGPT
	 */
	public static function get_post_meta(int $post_id): array {
		// 获取所有的 meta
		$all_meta = get_post_meta($post_id);

		$result = [];
		foreach ($all_meta as $key => $values) {
			// 每个 meta key 可能有多个值（是数组）
			$unserialized_values = array_map(function ($value) {
				// 检查是否是序列化的
				if (is_serialized($value)) {
					return maybe_unserialize($value);
				}
				return $value;
			}, $values);

			// 如果只有一个值，就直接输出单个，否则输出数组
			$result[$key] = count($unserialized_values) === 1 ? $unserialized_values[0] : $unserialized_values;
		}

		return $result;
	}
}
