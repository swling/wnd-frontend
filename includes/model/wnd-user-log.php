<?php
namespace Wnd\Model;

/**
 * 用户
 * @since 0.9.57
 */
abstract class Wnd_User_Log {

	/**
	 * 判断是否为当天首次登录
	 */
	public static function is_daily_login(): bool{
		$user_id = get_current_user_id();
		if (!$user_id) {
			return false;
		}

		$last_login = (int) wnd_get_user_meta($user_id, 'last_login');
		if ($last_login) {
			$last_login = date('Y-m-d', $last_login);
			if ($last_login == date('Y-m-d', time())) {
				return false;
			}
		}

		// 未设置登录时间 注册后未登录
		wnd_update_user_meta($user_id, 'last_login', time());
		return true;
	}

	/**
	 * 写入Auth数据库
	 * @since 0.9.36
	 */
	public static function insert_db(int $user_id, string $identity_type, string $identifier, string $credential = '') {
		global $wpdb;
		return $wpdb->insert(
			$wpdb->wnd_auths,
			['user_id' => $user_id, 'identifier' => $identifier, 'type' => $identity_type, 'credential' => $credential, 'time' => time()],
			['%d', '%s', '%s', '%s', '%d'],
		);
	}

	/**
	 * 更新Auth数据库
	 * @since 0.9.36
	 */
	public static function update_db(int $ID, int $user_id, string $identity_type, string $identifier, string $credential = '') {
		global $wpdb;
		return $wpdb->update(
			$wpdb->wnd_auths,
			['user_id' => $user_id, 'identifier' => $identifier, 'type' => $identity_type, 'credential' => $credential, 'time' => time()],
			['ID' => $ID],
			['%d', '%s', '%s', '%s', '%d'],
			['%d']
		);
	}

	/**
	 * 删除
	 * @since 0.9.36
	 */
	public static function delete_db(string $identity_type, string $identifier) {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->wnd_auths,
			['identifier' => $identifier, 'type' => $identity_type],
			['%s', '%s']
		);
	}
}
