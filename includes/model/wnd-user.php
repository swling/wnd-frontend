<?php
namespace Wnd\Model;

use Wnd\Model\Wnd_Auth;

/**
 * 自定义用户表及其他用户常用方法
 * @since 2019.10.25
 */
abstract class Wnd_User {

	private static $user_cache_group = 'wnd_users';

	/**
	 * 获取自定义用户对象
	 * - Auths 主要数据：user_id、email、phone……
	 * - Users 主要数据：balance、role、attribute、last_login、client_ip
	 * @since 2019.11.06
	 */
	public static function get_wnd_user($user_id): object{
		$user = wp_cache_get($user_id, static::$user_cache_group);
		if ($user) {
			return $user;
		}

		/**
		 * 将用户所有绑定设备集合为一个对象
		 */
		$user          = new \StdClass();
		$user->user_id = $user_id;
		$user_auths    = Wnd_Auth::get_user_auth_records($user_id);
		if ($user_auths) {
			foreach ($user_auths as $data) {
				$type = $data->type;
				if (!$type) {
					continue;
				}

				$user->$type = $data->identifier;
			}
			unset($data);
		}

		/**
		 * 自定义用户数据表
		 * @since 0.9.57
		 */
		$user_data = static::get_db($user_id);
		if ($user_data) {
			unset($user_data->ID, $user_data->user_id);
			foreach ($user_data as $key => $value) {
				$user->$key = $value;
			}unset($key, $value);
		}

		static::update_wnd_user_caches($user);

		return $user;
	}

	/**
	 * 获取指定用户数据表记录
	 * @since 0.9.57
	 */
	public static function get_db(int $user_id) {
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
	public static function insert_db(array $data): bool{
		$user_id = $data['user_id'] ?? 0;
		if (!$user_id) {
			return false;
		}

		global $wpdb;
		$defaults    = ['user_id' => 0, 'balance' => 0, 'role' => '', 'attribute' => '', 'last_login' => '', 'client_ip' => ''];
		$db_records  = ((array) static::get_db($user_id)) ?: $defaults;
		$data        = array_merge($db_records, $data);
		$data_format = ['%d', '%f', '%s', '%s', '%d', '%s'];
		$update_ID   = $data['ID'] ?? 0;
		if ($update_ID) {
			unset($data['ID']);
			$action = $wpdb->update(
				$wpdb->wnd_users,
				$data,
				['ID' => $update_ID],
				$data_format,
				['%d']
			);
		} else {
			$action = $wpdb->insert($wpdb->wnd_users, $data, $data_format);
		}

		/**
		 * - 读取现有 user data
		 * - 将本次数据依次设置到 user data
		 * - 更新对象缓存
		 * （此处不直接清理用户数据缓存，旨在减少一次数据查询）
		 */
		$user = static::get_wnd_user($user_id);
		foreach ($data as $key => $value) {
			$user->$key = $value;
		}unset($key, $value);
		static::update_wnd_user_caches($user);

		return $action;
	}

	/**
	 * 更新数据库
	 * @since 0.9.57
	 */
	public static function update_db(int $user_id, array $data): bool{
		$data['user_id'] = $user_id;
		return static::insert_db($data);
	}

	/**
	 * 删除记录
	 * @since 0.9.57
	 */
	public static function delete_db(int $user_id): bool {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->wnd_users,
			['user_id' => $user_id],
			['%d']
		);
	}

	/**
	 * 更新缓存
	 * @since 2019.11.06
	 *
	 * @param object $user Wnd_user表对象
	 */
	public static function update_wnd_user_caches(object $user_data) {
		$user_id = $user_data->user_id ?? 0;
		if (!$user_id) {
			return false;
		}

		// 按 user id 缓存指定用户所有 auth 数据
		wp_cache_set($user_id, $user_data, static::$user_cache_group);

		// 变量用户 auth 数据（排除 user_id 属性），读取设备 id 并缓存对应 user id
		$user_data = (array) $user_data;
		unset($user_data['user_id']);
		foreach ($user_data as $type => $identifier) {
			wp_cache_set($identifier, $user_id, static::get_auth_cache_group($type));
		}
		unset($type, $identifier);
	}

	/**
	 * 删除缓存
	 * @param int $user_id
	 */
	public static function clean_wnd_user_caches(int $user_id) {
		$user_data = static::get_wnd_user($user_id);
		if (!$user_data) {
			return false;
		}

		// 按 user id 删除对象缓存
		wp_cache_delete($user_id, static::$user_cache_group);

		// 遍历用户 auth 数据，并按值删除对应对象缓存
		$user_data = (array) $user_data;
		foreach ($user_data as $type => $identifier) {
			wp_cache_delete($identifier, static::get_auth_cache_group($type));
		}
		unset($type, $identifier);
	}

	/**
	 * 根据类型构造 AUTH 对象缓存组
	 */
	private static function get_auth_cache_group(string $type): string {
		return 'wnd_auth_' . $type;
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

		$db_records = static::get_wnd_user($user_id);
		$last_login = $db_records->last_login ?? 0;
		if ($last_login) {
			$last_login = date('Y-m-d', $last_login);
			if ($last_login == date('Y-m-d', time())) {
				return false;
			}
		}

		// 未设置登录时间/注册后未登录
		static::update_db($user_id, ['last_login' => time(), 'client_ip' => wnd_get_user_ip()]);
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
}
