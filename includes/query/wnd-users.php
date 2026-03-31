<?php
namespace Wnd\Query;

use Exception;
use Wnd\Query\Wnd_Filter_Query;
use Wnd\WPDB\Wnd_User_DB;
use WP_User_Query;

/**
 * User 筛选 API
 * @since 2020.05.05
 * @since 0.9.59.1 从独立 rest api 接口移植入 Wnd_Query
 *
 * @param $request
 */
class Wnd_Users extends Wnd_Query {

	protected static function check() {
		if (!is_super_admin()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}

	protected static function query($args = []): array {
		// -------- 参数解析 --------
		$args = static::parse_query($args);

		// -------- 查询 --------
		$query = new WP_User_Query($args);
		$users = $query->get_results();

		// 缓存查询结果中涉及的用户数据，以提升后续单个用户查询性能
		$user_ids = array_map(fn($user) => $user->ID, $users);
		static::cache_wnd_users($user_ids);

		// 使用 array_map 将 WP_User 对象转换为包含 Wnd_User 对象的数组，方便前端使用
		$converted = array_map(function ($user) {
			$user->wnd_user = wnd_get_wnd_user($user->ID); // 将 Wnd_User 对象附加到 WP_User 对象上，方便后续访问
			unset($user->allcaps);
			return $user;
		}, $users);

		global $wpdb;
		return [
			'results' => $converted,
			'number'  => count($users),
			'sql'     => $wpdb->queries,
		];
	}

	private static function parse_query(array $params): array {
		// -------- 参数默认值 --------
		$defaults = [
			'paged'   => 1,
			'number'  => 20,
			'role'    => '',
			'search'  => '',
			'orderby' => 'ID',
			'order'   => 'DESC',
			'include' => [],
			'exclude' => [],
		];

		$params   = array_merge($defaults, $params);
		$paged    = max(1, intval($params['paged']));
		$per_page = min(100, max(1, intval($params['number'])));

		// -------- 构建查询 --------
		$args = [
			'number'      => $per_page,
			'paged'       => $paged,
			'orderby'     => $params['orderby'],
			'order'       => strtoupper($params['order']),
			'count_total' => false, //不需要总数时设置为 false 可提升性能
		];

		if (!empty($params['role'])) {
			$args['role'] = sanitize_text_field($params['role']);
		}

		if (!empty($params['s'])) {
			$args['search']         = '*' . esc_attr($params['s']) . '*';
			$args['search_columns'] = ['user_login', 'user_email', 'display_name'];
		}

		if (!empty($params['include'])) {
			$args['include'] = array_map('intval', (array) $params['include']);
		}

		if (!empty($params['exclude'])) {
			$args['exclude'] = array_map('intval', (array) $params['exclude']);
		}

		// 解析 URL 其他常规查询参数并合并到查询参数中
		$query = Wnd_Filter_Query::parse_query_vars();
		return array_merge($args, $query);
	}

	// 缓存插件自定义的 用户 数据表
	protected static function cache_wnd_users(array $user_ids) {
		Wnd_User_DB::get_instance()->query_by_field_ids('user_id', $user_ids);
	}
}
