<?php
namespace Wnd\Query;

use WP_User_Query;

/**
 * 用户查询扩展
 * - 通过 pre_user_query Action 扩展 WP_User_Query，支持基于自定义用户表字段的排序和筛选
 * @since 0.9.93
 * @link https://chatgpt.com/c/69cb2059-8130-8322-96f4-416f7902e9eb
 *
 *  $users = new WP_User_Query([
 * 		// 排序
 *		'orderby' => 'custom.login_count',
 *		'order'   => 'DESC',
 * 		// 备用：指定查询条件
 *		'custom_where' => [
 *			'level' => 3
 *		]
 * ]);
 */
class Wnd_User_Query_Extend {

	private static string $table;
	private static string $alias = 'wnd_users';

	// 允许字段（白名单）
	private static array $allowed_fields = ['login_count', 'last_login'];

	public static function init(): void {
		add_action('pre_user_query', [__CLASS__, 'handle_query']);
	}

	public static function handle_query(WP_User_Query $query): void {
		global $wpdb;
		static::$table = $wpdb->wnd_users;
		$vars          = &$query->query_vars;

		// ========= 1. 是否启用 =========
		if (empty($vars['orderby']) && empty($vars['custom_where'])) {
			return;
		}

		// ========= 2. ORDER BY 处理 =========
		if (!empty($vars['orderby']) && str_starts_with($vars['orderby'], 'custom.')) {
			$field = str_replace('custom.', '', $vars['orderby']);
			if (!in_array($field, static::$allowed_fields)) {
				return;
			}

			$order = strtoupper($vars['order'] ?? 'DESC');
			$order = in_array($order, ['ASC', 'DESC']) ? $order : 'DESC';

			static::join_table($query);
			$query->query_orderby = 'ORDER BY COALESCE(' . static::$alias . ".{$field}, 0) {$order}";
		}

		// ========= 3. WHERE 扩展 =========
		if (!empty($vars['custom_where']) && is_array($vars['custom_where'])) {
			static::join_table($query);
			foreach ($vars['custom_where'] as $field => $value) {
				if (!in_array($field, static::$allowed_fields)) {
					continue;
				}

				$query->query_where .= $wpdb->prepare(' AND ' . static::$alias . ".{$field} = %s", $value);
			}
		}
	}

	private static function join_table(WP_User_Query $query): void {
		global $wpdb;

		// 防止重复 JOIN
		if (str_contains($query->query_from, static::$table)) {
			return;
		}

		$query->query_from .= ' LEFT JOIN ' . static::$table . ' ' . static::$alias . ' ON ' . static::$alias . ".user_id = {$wpdb->users}.ID";
	}
}
