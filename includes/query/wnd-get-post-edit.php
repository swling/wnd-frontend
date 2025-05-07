<?php

namespace Wnd\Query;

use Exception;
use Wnd\Model\Wnd_Post;
use Wnd\Permission\Wnd_PPC;

/**
 * 获取 Post 编辑数据
 * 若未指定 post id 则返回新增 post 的基础数据
 * @since 0.9.81
 */
final class Wnd_Get_Post_Edit extends Wnd_Query {

	protected static $default_post = [
		'ID'                    => 0,
		'post_author'           => 0,
		'post_date'             => '',
		'post_date_gmt'         => '',
		'post_content'          => '',
		'post_title'            => '',
		'post_excerpt'          => '',
		'post_status'           => '',
		'comment_status'        => '',
		'ping_status'           => '',
		'post_password'         => '',
		'post_name'             => '',
		'to_ping'               => '',
		'pinged'                => '',
		'post_modified'         => '',
		'post_modified_gmt'     => '',
		'post_content_filtered' => '',
		'post_parent'           => 0,
		'guid'                  => '',
		'menu_order'            => 0,
		'post_type'             => '',
		'post_mime_type'        => '',
		'comment_count'         => 0,
	];

	protected static function check($args = []) {
		// 获取参数
		$post = static::init_post_data($args);

		// 编辑权限检测
		if ($post->ID) {
			$ppc = Wnd_PPC::get_instance($post->post_type);
			$ppc->set_post_id($post->ID);
			$ppc->check_edit();

			// 创建权限检测
		} else {
			$ppc = Wnd_PPC::get_instance($post->post_type);
			$ppc->check_create();
		}
	}

	protected static function query($args = []): array {
		// 获取参数
		$post      = static::init_post_data($args);
		$post_id   = $post->ID;
		$post_type = $post->post_type;

		// 获取所有注册的分类法
		$taxonomies   = get_object_taxonomies($post_type);
		$terms_by_tax = [];
		$options      = [];
		foreach ($taxonomies as $taxonomy) {
			$options[$taxonomy] = static::get_terms_hierarchy($taxonomy);

			if (!$post_id) {
				$terms_by_tax[$taxonomy] = [];
				continue;
			}

			$terms = get_the_terms($post_id, $taxonomy);
			if (!empty($terms) && !is_wp_error($terms)) {
				$terms_by_tax[$taxonomy] = $terms;
			} else {
				$terms_by_tax[$taxonomy] = [];
			}
		}

		// 缩略图
		$thumbnail_id = wnd_get_post_meta($post_id, '_thumbnail_id');
		if ($thumbnail_id) {
			$thumbnail_url = static::get_attachment_url($thumbnail_id, '_thumbnail_id', $post_id);
		} else {
			$thumbnail_url = '';
		}

		$file_id  = wnd_get_post_meta($post_id, 'file');
		$file_url = $file_id ? static::get_attachment_url($file_id, 'file', $post_id) : '';

		// 返回数据
		$data = [
			'post'         => $post,
			'thumbnail'    => $thumbnail_url,
			'file_id'      => $file_id,
			'file_url'     => $file_url,
			'terms'        => $terms_by_tax,
			'term_options' => $options,
			'meta'         => static::get_post_meta($post_id),
			'templates'    => 'page' == $post_type ? static::get_page_templates() : [],
		];

		return apply_filters('wnd_get_post_edit', $data, $post_id);
	}

	private static function init_post_data(array $args): object {
		// 获取参数
		$post_id   = (int) ($args['post_id'] ?? 0);
		$post_type = $args['post_type'] ?? ($args['type'] ?? 'post');
		$post_id   = $post_id ?: Wnd_Post::get_draft($post_type);
		if ($post_id) {
			$post = get_post($post_id);
			if (!$post) {
				throw new Exception('Invalid Post ID');
			}
		} else {
			$post            = (object) static::$default_post;
			$post->post_type = $post_type;
		}

		return $post;
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

	/**
	 * 获取附件URL
	 * 如果字段存在，但文件已不存在，例如已被后台删除，删除对应meta_key or option
	 * @since 2020.04.13
	 */
	private static function get_attachment_url(int $attachment_id, string $meta_key, int $post_parent): string {
		$attachment_url = $attachment_id ? wnd_get_attachment_url($attachment_id) : false;
		$user_id        = get_current_user_id();

		if ($attachment_id and !$attachment_url) {
			if ($post_parent) {
				wnd_delete_post_meta($post_parent, $meta_key);
			} else {
				wnd_delete_user_meta($user_id, $meta_key);
			}
		}

		return $attachment_url;
	}

	private static function get_page_templates(): array {
		$templates = array_flip(wp_get_theme()->get_page_templates(null, 'page'));

		ksort($templates);
		$options = ['Default' => 'default'];
		foreach (array_keys($templates) as $template) {
			$options[$template] = esc_attr($templates[$template]);
		}

		return $options;
	}
}
