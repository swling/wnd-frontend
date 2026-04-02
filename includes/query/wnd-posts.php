<?php
namespace Wnd\Query;

use Exception;
use Wnd\Query\Wnd_Filter_Query;
use Wnd\Query\Wnd_Query;
use Wnd\WPDB\Wnd_Attachment_DB;
use WP_Post;
use WP_Query;

/**
 * posts 查询接口
 * 附加信息：
 * - 获取文章缩略图链接
 * - 获取文章分类信息
 *
 * @since 2026.04.01
 *
 * @param $request
 */
class Wnd_posts extends Wnd_Query {

	protected static function query($args = []): array {
		// 解析参数并核查权限
		$query_args = array_merge(Wnd_Filter_Query::$defaults, Wnd_Filter_Query::parse_query_vars());
		$query_args = static::enforce_query_permission($query_args);

		// 执行查询
		$query   = new WP_Query($query_args);
		$results = $query->get_posts();

		$post_ids = array_map(fn($post) => $post->ID, $results);
		static::cache_thumbnail($post_ids);

		// 使用 array_map 将 WP_Post 对象转换为包含缩略图链接和分类信息的数组，方便前端使用
		$converted = array_map(function ($post) {
			$post->thumbnail = static::get_thumbnail($post->ID);
			$post->link      = get_permalink($post->ID);
			$post->terms     = static::get_terms($post);
			unset($post->post_content);
			return $post;
		}, $results);

		// 仅管理员可查看 SQL 查询日志
		global $wpdb;
		return [
			'results'    => $converted,
			'number'     => count($converted),
			'sql'        => is_super_admin() ? $wpdb->queries : [],
			'pagination' => static::get_pagination($query),
		];
	}

	protected static function get_thumbnail(int $post_id) {
		$image_id = wnd_get_post_meta($post_id, '_thumbnail_id');
		if (!$image_id) {
			return '';
		}

		return wnd_get_attachment_url($image_id);
	}

	// 缓存 attachments 数据表
	protected static function cache_thumbnail(array $post_ids) {
		$image_ids = [];
		foreach ($post_ids as $post_id) {
			$image_ids[] = wnd_get_post_meta($post_id, '_thumbnail_id');
		}
		if (empty($image_ids)) {
			return;
		}

		Wnd_Attachment_DB::get_instance()->query_by_ids($image_ids);
	}

	// 获取文章的所有分类信息
	protected static function get_terms(WP_Post $post): array {
		// 获取所有注册的分类法
		$taxonomies   = get_object_taxonomies($post->post_type);
		$terms_by_tax = [];
		foreach ($taxonomies as $taxonomy) {
			$terms = get_the_terms($post->ID, $taxonomy);
			if (!empty($terms) && !is_wp_error($terms)) {
				$terms_by_tax[$taxonomy] = $terms;
			} else {
				$terms_by_tax[$taxonomy] = [];
			}
		}

		return $terms_by_tax;
	}

	/**
	 * 分页导航
	 */
	protected static function get_pagination($wp_query): array {
		if (!$wp_query) {
			throw new Exception(__('未执行WP_Query', 'wnd'));
		}

		return [
			'paged'         => $wp_query->query_vars['paged'] ?: 1,
			'max_num_pages' => $wp_query->max_num_pages,
			'per_page'      => $wp_query->query_vars['posts_per_page'],
			'current_count' => $wp_query->post_count,
		];
	}

	/**
	 * 权限检测与查询参数调整
	 * - 管理员：完全放行
	 * - 未登录用户：仅允许查询公开状态的内容，越权则禁止访问
	 * - 已登录用户：允许查询公开状态的内容，越权则降级为仅查询本人内容
	 *
	 * @param array $args WP_Query 参数数组
	 * @return array 调整后的 WP_Query 参数数组
	 * @throws Exception 权限不足时抛出异常
	 */
	private static function enforce_query_permission(array $args): array {
		// 管理员：完全放行
		if (is_super_admin()) {
			return $args;
		}

		$post_status     = $args['post_status'] ?? 'publish';
		$current_user_id = get_current_user_id();
		$post_type       = $args['post_type'] ?? 'post';

		// 获取允许公开查询的状态
		$allowed_status = apply_filters('wnd_allowed_query_status', ['publish', 'wnd-closed'], $post_type, $current_user_id);

		// 状态合法：直接放行
		$post_status = (array) $post_status;
		if (!array_diff($post_status, $allowed_status)) {
			return $args;
		}

		// 状态越权
		if (!$current_user_id) {
			// 未登录：禁止访问
			throw new Exception('Only support querying public status: ' . implode(',', $post_status));
		}

		// 已登录：降级为仅查询本人内容
		$args['author']      = $current_user_id;
		$args['post_status'] = 'any';

		return $args;
	}
}
