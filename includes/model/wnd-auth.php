<?php
namespace Wnd\Model;

use Exception;
use WP_User;

/**
 * 对应 Wnd_Auths 数据表
 * - 定义常用操作方法
 * - 设置对象缓存
 * @since 0.9.57.1
 */
abstract class Wnd_Auth {

	private static $auths_cache_group = 'wnd_auths';

	/**
	 * 获取用户所有 auth 合集对象
	 */
	public static function get_user_auths(int $user_id): object{
		$auths = wp_cache_get($user_id, static::$auths_cache_group);
		if ($auths) {
			return $auths;
		}

		$auths          = new \StdClass();
		$auths->user_id = $user_id;
		$user_auths     = static::get_auth_records($user_id);
		if (!$user_auths) {
			return $auths;
		}

		/**
		 * 将用户所有绑定设备集合为一个对象
		 */
		foreach ($user_auths as $data) {
			$type = $data->type;
			if (!$type) {
				continue;
			}

			$auths->$type = $data->identifier;

			// 设置单个 openid 与 user_id 对应关系的缓存：必须设置 $sync_caches = false，否则产生死循环
			static::update_auth_cache($user_id, $type, $data->identifier, false);
		}
		unset($data);

		// 设置用户 auths 合集缓存
		wp_cache_set($user_id, $auths, static::$auths_cache_group);

		return $auths;
	}

	/**
	 * 删除指定用户全部 auth 记录
	 */
	public static function delete_user_auths(int $user_id) {
		global $wpdb;
		$wpdb->delete($wpdb->wnd_auths, ['user_id' => $user_id]);
		static::clean_auth_caches($user_id);
	}

	/**
	 * 获取指定用户的所有 auth 数据表记录
	 * @since 0.9.36
	 */
	private static function get_auth_records(int $user_id) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->wnd_auths WHERE user_id = %d",
				$user_id
			)
		);
	}

	/**
	 * 删除用户所有 auths 对象缓存
	 * @since 0.9.57.1
	 */
	private static function clean_auth_caches(int $user_id) {
		// 遍历用户 auth 数据，并按值删除对应对象缓存
		$auths = static::get_user_auths($user_id);
		foreach ($auths as $type => $identifier) {
			static::delete_auth_cache($user_id, $type);
		}
		unset($type, $identifier);

		// 用户对象集合
		wp_cache_delete($user_id, static::$auths_cache_group);
	}

	/**
	 * 写入或更新用户open id
	 * - 更新同类型不同 openid 时，需要写入新纪录，然后删除原有同类型记录。其原因在于更换邮箱、手机时，需要先写入验证码记录，待验证后才能正式更换。
	 * @since 2019.07.11
	 *
	 * @param  	int    	$user_id
	 * @param  	string 	$type
	 * @param  	string 	$open_id
	 * @return 	int    	$wpdb->insert
	 */
	public static function update_user_openid(int $user_id, string $type, string $open_id): int {
		if (!$user_id or !get_userdata($user_id)) {
			throw new Exception('Invalid user id ');
		}
		if (!$type) {
			throw new Exception('Invalid user openid type');
		}
		if (!$open_id) {
			throw new Exception('Invalid user openid');
		}

		// 查询原有用户同类型openid信息，若与当前指定更新的openid相同，则无需操作
		$user        = static::get_user_auths($user_id);
		$old_open_id = $user->$type ?? '';
		if ($old_open_id == $open_id) {
			return $user_id;
		}

		// 更新或写入（{$type, $open_id} 为复合唯一索引）
		$auth_record = static::get_db($type, $open_id);
		$ID          = $auth_record->ID ?? 0;
		if ($ID) {
			$action = static::update_db($ID, $user_id, $type, $open_id);
		} else {
			$action = static::insert_db($user_id, $type, $open_id);
		}

		/**
		 * 数据更新成功
		 * - 删除可能存在的原有同类型 openid
		 * - 设置缓存
		 */
		if ($action) {
			if ($old_open_id) {
				static::delete_db($type, $old_open_id);
			}

			static::update_auth_cache($user_id, $type, $open_id);
		}

		return $action ? $user_id : 0;
	}

	/**
	 * 根据用户 id 获取指定类型 openid
	 * @since 2019.11.06
	 *
	 * @param  	int    	$user_id
	 * @param  	string 	$type
	 * @return 	string 	用户openid或false
	 */
	public static function get_user_openid(int $user_id, string $type): string {
		if (!$user_id) {
			return '';
		}

		return static::get_user_auths($user_id)->$type ?? '';
	}

	/**
	 * 删除单个用户 open id
	 * @since 0.9.4
	 *
	 * @param  	int    	$user_id
	 * @param  	string 	$type
	 * @return 	int    	$wpdb->delete
	 */
	public static function delete_user_openid(int $user_id, string $type): int{
		$user    = static::get_user_auths($user_id);
		$open_id = $user->$type ?? '';
		$action  = static::delete_db($type, $open_id);

		// 缓存
		if ($action) {
			static::delete_auth_cache($user_id, $type);
		}

		return $action ? $user_id : 0;
	}

	/**
	 * 根据openID获取WordPress用户，用于第三方账户登录
	 * @since 2019.07.11
	 *
	 * @param  string          $type
	 * @param  string          $openID
	 * @return WP_User|false
	 */
	public static function get_user_by_openid(string $type, string $open_id) {
		// 查询对象缓存
		$user_id = static::get_auth_cache($type, $open_id);
		if (false === $user_id) {
			$auth_record = static::get_db($type, $open_id);
			$user_id     = $auth_record->user_id ?? 0;
			if ($user_id) {
				static::update_auth_cache($user_id, $type, $open_id);
			}
		}

		return $user_id ? get_user_by('ID', $user_id) : false;
	}

	/**
	 * 更新用户单个 openid 对象缓存
	 * - 设置单个绑定 与 user_id 对应关系缓存
	 * - 用户对象集合缓存（如果 $sync_caches 为真，该选项是为了防止在 static::get_user_auths() 产生死循环）
	 * @since 0.9.57.1
	 */
	private static function update_auth_cache(int $user_id, string $type, string $open_id, bool $sync_caches = true) {
		/**
		 * - 单个绑定 与 user_id 对应关系
		 * - 用户对象集合
		 */
		$cache_group = static::get_auth_cache_group($type);
		wp_cache_set($open_id, $user_id, $cache_group);

		if ($sync_caches) {
			$auths        = static::get_user_auths($user_id);
			$auths->$type = $open_id;
			wp_cache_set($user_id, $auths, static::$auths_cache_group);
		}
	}

	/**
	 * 获取单个 openid 对应的 user_id 缓存
	 */
	private static function get_auth_cache(string $type, string $open_id): int{
		$cache_group = static::get_auth_cache_group($type);
		$user_id     = wp_cache_get($open_id, $cache_group);
		return $user_id ?: 0;
	}

	/**
	 * 删除单个 openid 对应的 user_id 缓存
	 */
	private static function delete_auth_cache(int $user_id, string $type) {
		$cache_group    = static::get_auth_cache_group($type);
		$auths          = static::get_user_auths($user_id);
		$invalid_openid = $auths->$type ?? '';

		// 更新用户 auth 对象集合缓存
		unset($auths->$type);
		wp_cache_set($user_id, $auths, static::$auths_cache_group);

		// 删除单个绑定 与 user_id 对应关系缓存
		wp_cache_delete($invalid_openid, $cache_group);
	}

	/**
	 * 根据类型构造 AUTH 对象缓存组
	 * - 缓存单个 openid 与 user_id 的对应关系
	 */
	private static function get_auth_cache_group(string $type): string {
		return 'wnd_auth_' . $type;
	}

	/**
	 * @since 0.9.36
	 */
	public static function get_db(string $identity_type, string $identifier) {
		global $wpdb;
		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->wnd_auths WHERE identifier = %s AND type = %s",
				$identifier, $identity_type
			)
		);

		return $data;
	}

	/**
	 * 写入Auth数据库
	 * - 不可在操作数据库时，直接设置对象缓存，因为写入的数据可能是待验证的数据
	 * @since 0.9.36
	 */
	public static function insert_db(int $user_id, string $identity_type, string $identifier, string $credential = ''): bool {
		global $wpdb;
		return $wpdb->insert(
			$wpdb->wnd_auths,
			['user_id' => $user_id, 'identifier' => $identifier, 'type' => $identity_type, 'credential' => $credential, 'time' => time()],
			['%d', '%s', '%s', '%s', '%d'],
		);
	}

	/**
	 * 更新Auth数据库
	 * - 不可在操作数据库时，直接设置对象缓存，因为写入的数据可能是待验证的数据
	 * @since 0.9.36
	 */
	public static function update_db(int $ID, int $user_id, string $identity_type, string $identifier, string $credential = ''): bool {
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
	public static function delete_db(string $identity_type, string $identifier): bool {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->wnd_auths,
			['identifier' => $identifier, 'type' => $identity_type],
			['%s', '%s']
		);
	}
}
