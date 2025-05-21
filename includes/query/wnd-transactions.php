<?php

namespace Wnd\Query;

use Exception;
use Wnd\Model\Wnd_Transaction_Anonymous;
use Wnd\WPDB\Wnd_Attachment_DB;
use Wnd\WPDB\Wnd_Transaction_DB;

/**
 * posts 查询接口
 * @since 0.9.59.1 从独立 rest api 接口移植入 Wnd_Query
 *
 * 多重筛选 API
 * 常规情况下，controller 应将用户请求转为操作命令并调用 model 处理，但 Wnd\View\Wnd_Filter 是一个完全独立的功能类
 * Wnd\View\Wnd_Filter 既包含了生成筛选链接的视图功能，也包含了根据请求参数执行对应 WP_Query 并返回查询结果的功能，且两者紧密相关不宜分割
 * 可以理解为，Wnd\View\Wnd_Filter 是通过生成一个筛选视图，发送用户请求，最终根据用户请求，生成新的视图的特殊类：视图<->控制<->视图
 * @since 2019.07.31
 * @since 2019.10.07 OOP改造
 *
 * @param $request
 */
class Wnd_Transactions extends Wnd_Query {

	protected static function check($args = []) {
		if (!is_user_logged_in() and !isset($args['slug']) and !Wnd_Transaction_Anonymous::get_anon_cookies()) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}

	protected static function query($args = []): array {
		$type   = $args['type'] ?? 'any';
		$paged  = $args['paged'] ?? 1;
		$number = $args['number'] ?? get_option('posts_per_page');
		$where  = static::get_where($args);

		$results = Wnd_Transaction_DB::get_instance()->get_results($where, ['limit' => $number, 'offset' => ($paged - 1) * $number]);

		// 缓存产品订单：产品缩略图等信息
		if ('order' == $type) {
			$post_ids = array_map(fn($item) => $item->object_id, $results);
			$post_ids = array_unique($post_ids);
			static::cache_posts($post_ids);
			static::cache_thumbnail($post_ids);
		}

		// 使用 array_map 对每个对象处理 props 字段
		$converted = array_map(function ($item) {
			$item->props      = json_decode($item->props);
			$item->thumbnail  = static::get_thumbnail($item->object_id);
			$item->object_url = get_permalink($item->object_id);
			return $item;
		}, $results);

		global $wpdb;
		return ['results' => $converted, 'number' => count($converted), 'sql' => $wpdb->queries];
	}

	private static function get_thumbnail(int $post_id) {
		$image_id = wnd_get_post_meta($post_id, '_thumbnail_id');
		if (!$image_id) {
			return '';
		}

		return wnd_get_attachment_url($image_id);
	}

	private static function get_where($args) {
		// 匿名：仅支持按 slug 查询，且限定为 匿名订单（优先从 $_GET，其次读取 cookies）
		if (!is_user_logged_in()) {
			if (isset($args['slug'])) {
				$slugs = (string) $args['slug'];
			} else {
				$orders = Wnd_Transaction_Anonymous::get_anon_cookies();
				$slugs  = $orders ? array_column($orders, 'value') : [];
			}

			// 约束 any
			if (!$slugs or 'any' == $slugs) {
				throw new Exception('Illegal slug: ' . $slugs);
			}

			return [
				'slug'    => $slugs,
				'type'    => (string) ($args['type'] ?? 'any'),
				'status'  => (string) ($args['status'] ?? 'any'),
				'user_id' => 0,
			];
		}

		// 登录用户：查询自己的；管理员：查询任意用户
		return [
			'type'    => (string) ($args['type'] ?? 'any'),
			'status'  => (string) ($args['status'] ?? 'any'),
			'user_id' => static::get_user_id($args),
		];
	}

	private static function get_user_id(array $args) {
		$current_user_id = get_current_user_id();
		if (!wnd_is_manager()) {
			return $current_user_id;
		}

		return $args['user_id'] ?? 'any';
	}

	// 统一查询对应的 posts，而非在 foreach 中逐个查询，后者会逐次执行多条 sql
	private static function cache_posts(array $ids) {
		return get_posts([
			'post__in'               => $ids,
			'orderby'                => 'post__in', // 保持传入顺序
			'post_type'              => 'any',
			'numberposts'            => -1, // 获取所有
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		]);
	}

	// 缓存 attachments 数据表
	private static function cache_thumbnail(array $post_ids) {
		$image_ids = [];
		foreach ($post_ids as $post_id) {
			$image_ids[] = wnd_get_post_meta($post_id, '_thumbnail_id');
		}
		$image_ids = array_unique($image_ids);
		Wnd_Attachment_DB::get_instance()->query_by_ids($image_ids);
	}

}
