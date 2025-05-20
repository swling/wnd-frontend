<?php

namespace Wnd\Query;

use Exception;
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

	protected static function check() {
		if (!is_user_logged_in()) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}

	protected static function query($args = []): array {
		$type    = $args['type'] ?? 'any';
		$status  = $args['status'] ?? 'any';
		$paged   = $args['paged'] ?? 1;
		$number  = $args['number'] ?? get_option('posts_per_page');
		$user_id = !is_super_admin() ? get_current_user_id() : ($args['user_id'] ?? 'any');

		$where = [
			'type'    => $type,
			'status'  => $status,
			'user_id' => $user_id,
		];

		$results = Wnd_Transaction_DB::get_instance()->get_results($where, ['limit' => $number, 'offset' => ($paged - 1) * $number]);

		// 缓存产品订单：产品缩略图等信息
		if ('order' == $type) {
			$post_ids = array_map(fn($item) => $item->object_id, $results);
			static::cache_thumbnail($post_ids);
		}

		// 使用 array_map 对每个对象处理 props 字段
		$converted = array_map(function ($item) {
			$item->props     = json_decode($item->props);
			$item->thumbnail = static::get_thumbnail($item->object_id);
			return $item;
		}, $results);

		global $wpdb;
		return ['results' => $converted, 'number' => count($converted), 'sql' => $wpdb->queries];
	}

	private static function cache_thumbnail(array $post_ids) {
		// 去重
		$post_ids = array_unique($post_ids);

		// 缓存 meta
		update_meta_cache('post', $post_ids);

		// 缓存 attachments 数据表
		$image_ids = [];
		foreach ($post_ids as $post_id) {
			$image_ids[] = wnd_get_post_meta($post_id, '_thumbnail_id');
		}
		$image_ids = array_unique($image_ids);
		Wnd_Attachment_DB::get_instance()->query_by_ids($image_ids);
	}

	private static function get_thumbnail(int $post_id) {
		$image_id = wnd_get_post_meta($post_id, '_thumbnail_id');
		if (!$image_id) {
			return '';
		}

		return wnd_get_attachment_url($image_id);
	}

	// 统一查询对应的 posts，而非在 foreach 中逐个查询，后者会逐次执行多条 sql
	private static function get_posts(array $ids) {
		$posts = get_posts([
			'post__in'               => $ids,
			'orderby'                => 'post__in', // 保持传入顺序
			'post_type'              => 'any',
			'numberposts'            => -1, // 获取所有
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		]);
	}
}
