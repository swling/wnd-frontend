<?php
namespace Wnd\Model;

/**
 * 自定义用户表及其他用户常用方法
 * @since 2019.10.25
 */
abstract class Wnd_User {

	private static $user_cache_group = 'wnd_users';

	/**
	 * 获取自定义用户对象
	 * - Users 主要数据：balance、last_login、client_ip
	 * @since 2019.11.06
	 */
	public static function get_wnd_user(int $user_id): object{
		$user = static::get_wnd_user_cache($user_id);
		if ($user) {
			return $user;
		}

		$user = static::get_db($user_id) ?: new \stdClass;
		static::update_wnd_user_cache($user);

		return $user;
	}

	/**
	 * 更新自定义用户对象
	 * - Users 主要数据：balance、last_login、client_ip
	 * - 此处不直接清理用户数据缓存，旨在减少一次数据查询
	 * @since 2019.11.06
	 */
	public static function update_wnd_user(int $user_id, array $data): bool{
		$defaults   = ['user_id' => 0, 'balance' => 0, 'last_login' => '', 'login_count' => '', 'client_ip' => ''];
		$db_records = ((array) static::get_wnd_user($user_id)) ?: $defaults;
		$data       = array_merge($db_records, $data);

		$action = static::update_db($user_id, $data);
		if ($action) {
			$user = (object) $data;
			static::update_wnd_user_cache($user);
		}

		return $action;
	}

	/**
	 * 删除记录
	 * @since 0.9.57
	 */
	public static function delete_wnd_user(int $user_id): bool{
		$action = static::delete_db($user_id);
		if ($action) {
			static::delete_wnd_user_cache($user_id);
		}

		return $action;
	}

	/**
	 * 获取缓存
	 * @param int $user_id
	 */
	private static function get_wnd_user_cache(int $user_id) {
		return wp_cache_get($user_id, static::$user_cache_group);
	}

	/**
	 * 更新缓存
	 * @since 2019.11.06
	 *
	 * @param object $user Wnd_user表对象
	 */
	private static function update_wnd_user_cache(object $user_data) {
		$user_id = $user_data->user_id ?? 0;
		if (!$user_id) {
			return false;
		}

		// 按 user id 缓存指定用户所有 auth 数据
		wp_cache_set($user_id, $user_data, static::$user_cache_group);
	}

	/**
	 * 删除缓存
	 * @param int $user_id
	 */
	private static function delete_wnd_user_cache(int $user_id) {
		wp_cache_delete($user_id, static::$user_cache_group);
	}

	/**
	 * 记录登录日志
	 * @since 0.9.57
	 */
	public static function write_login_log(): bool{
		$user_id = get_current_user_id();
		if (!$user_id) {
			return false;
		}

		$db_records  = static::get_wnd_user($user_id);
		$last_login  = $db_records->last_login ?? 0;
		$login_count = $db_records->login_count ?? 0;
		if ($last_login) {
			$last_login = date('Y-m-d', $last_login);
			if ($last_login == date('Y-m-d', time())) {
				return false;
			}
		}

		// 未设置登录时间/注册后未登录
		static::update_wnd_user($user_id, ['last_login' => time(), 'login_count' => $login_count + 1, 'client_ip' => wnd_get_user_ip()]);
		return true;
	}

	/**
	 * 获取长期未登录的睡眠账户
	 * @since 0.9.57
	 */
	public static function get_sleep_users(int $day): array{
		global $wpdb;
		$timestamp = time() - (3600 * 24 * $day);
		$results   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->wnd_users WHERE last_login < %d",
				$timestamp
			)
		);

		return $results ?: [];
	}

	/**
	 * 获取指定用户数据表记录
	 * @since 0.9.57
	 */
	private static function get_db(int $user_id) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->wnd_users WHERE user_id = %d",
				$user_id
			)
		);
	}

	/**
	 * 写入 user 数据库
	 * @since 0.9.57
	 */
	private static function insert_db(array $data): bool{
		$user_id = $data['user_id'] ?? 0;
		if (!$user_id) {
			return false;
		}

		global $wpdb;
		$data_format = ['%d', '%f', '%d', '%d', '%s'];
		$update_ID   = $data['ID'] ?? 0;
		if ($update_ID) {
			unset($data['ID']);
			$action = $wpdb->update($wpdb->wnd_users, $data, ['ID' => $update_ID], $data_format, ['%d']);
		} else {
			$action = $wpdb->insert($wpdb->wnd_users, $data, $data_format);
		}

		return $action;
	}

	/**
	 * 更新数据库
	 * @since 0.9.57
	 */
	private static function update_db(int $user_id, array $data): bool{
		$data['user_id'] = $user_id;
		return static::insert_db($data);
	}

	/**
	 * 删除记录
	 * @since 0.9.57
	 */
	private static function delete_db(int $user_id): bool {
		global $wpdb;
		return $wpdb->delete($wpdb->wnd_users, ['user_id' => $user_id], ['%d']);
	}
}
