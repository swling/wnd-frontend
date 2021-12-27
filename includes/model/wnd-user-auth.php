<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Auth;
use Wnd\Model\Wnd_User;
use WP_User;

/**
 * 用户绑定设备
 * @since 0.9.57.1
 */
abstract class Wnd_User_Auth {

	/**
	 * @since 2019.01.26 根据用户id获取号码
	 *
	 * @param  	int          			$user_id
	 * @return 	string|false 	用户手机号或false
	 */
	public static function get_user_phone($user_id) {
		return static::get_user_openid($user_id, 'phone');
	}

	/**
	 * @since 2019.11.06	根据用户id获取openid
	 *
	 * @param  	int          			$user_id
	 * @param  	string       			$type                第三方账号类型
	 * @return 	string|false 	用户openid或false
	 */
	public static function get_user_openid($user_id, $type) {
		if (!$user_id) {
			return '';
		}

		// 统一小写类型
		$type = strtolower($type);

		return wnd_get_wnd_user($user_id)->$type ?? '';
	}

	/**
	 * @since 2019.01.28 根据邮箱，手机，或用户名查询用户
	 *
	 * @param  	string                 			$email_or_phone_or_login
	 * @return 	object|false	WordPress user object on success
	 */
	public static function get_user_by($email_or_phone_or_login) {
		if (!$email_or_phone_or_login) {
			return false;
		}

		/**
		 * 邮箱
		 */
		if (is_email($email_or_phone_or_login)) {
			return get_user_by('email', $email_or_phone_or_login);
		}

		/**
		 * 手机或登录名
		 *
		 * 若当前字符匹配手机号码格式，则优先使用手机号查询
		 * 若查询到用户即返回
		 * 最后返回用户名查询结果
		 *
		 * 注意：
		 * 强烈建议禁止用户使用纯数字作为用户名
		 * 否则可能出现手机号码与用户名的混乱，造成同一个登录名，对应过个账户信息的问题
		 *
		 * 本插件已禁用纯数字用户名：@see wnd_ajax_reg()
		 */
		if (wnd_is_mobile($email_or_phone_or_login)) {
			return static::get_user_by_openid('phone', $email_or_phone_or_login);
		}

		return get_user_by('login', $email_or_phone_or_login);
	}

	/**
	 * 根据openID获取WordPress用户，用于第三方账户登录
	 * @since 2019.07.11
	 *
	 * @param  string          $type
	 * @param  string          $openID
	 * @return WP_User|false
	 */
	public static function get_user_by_openid($type, $open_id) {
		$type        = strtolower($type);
		$cache_group = static::get_auth_cache_group($type);

		// 查询对象缓存
		$user_id = wp_cache_get($open_id, $cache_group);
		if (false === $user_id) {
			$auth_record = Wnd_Auth::get_db($type, $open_id);
			$user_id     = $auth_record->user_id ?? 0;
			if ($user_id) {
				wp_cache_set($open_id, $user_id, $cache_group);
			}
		}

		return $user_id ? get_user_by('ID', $user_id) : false;
	}

	/**
	 * 写入用户open id
	 * @since 2019.07.11
	 *
	 * @param  	int    	$user_id
	 * @param  	string 	$type
	 * @param  	string 	$open_id
	 * @return 	int    	$wpdb->insert
	 */
	public static function update_user_openid($user_id, $type, $open_id) {
		if (!$user_id or !get_userdata($user_id)) {
			throw new Exception('Invalid user id ');
		}

		if (!$type) {
			throw new Exception('Invalid user openid type');
		}

		if (!$open_id) {
			throw new Exception('Invalid user openid');
		}

		// 统一将类型转为小写
		$type = strtolower($type);

		// 查询原有用户同类型openid信息，若与当前指定更新的openid相同，则无需操作
		$user        = wnd_get_wnd_user($user_id);
		$old_open_id = $user->$type ?? '';
		if ($old_open_id == $open_id) {
			return $user_id;
		}

		// 更新或写入
		$auth_record = Wnd_Auth::get_db($type, $open_id);
		$ID          = $auth_record->ID ?? 0;
		if ($ID) {
			$db = Wnd_Auth::update_db($ID, $user_id, $type, $open_id);
		} else {
			$db = Wnd_Auth::insert_db($user_id, $type, $open_id);
		}

		// 删除原有同类型openid并更新用户缓存
		if ($db) {
			Wnd_Auth::delete_db($type, $old_open_id);
			Wnd_User::clean_wnd_user_caches($user_id);
		}

		return $db ? $user_id : 0;
	}

	/**
	 * 删除用户 open id
	 * @since 0.9.4
	 *
	 * @param  	int    	$user_id
	 * @param  	string 	$type
	 * @return 	int    	$wpdb->delete
	 */
	public static function delete_user_openid($user_id, $type) {
		$type = strtolower($type);

		// 查询
		$user    = wnd_get_wnd_user($user_id);
		$open_id = $user->$type ?? '';
		$db      = Wnd_Auth::delete_db($type, $open_id);

		// 缓存
		if ($db) {
			Wnd_User::clean_wnd_user_caches($user_id);
		}

		return $db ? $user_id : 0;
	}

	/**
	 * 更新用户电子邮箱 同时更新插件用户数据库email，及WordPress账户email
	 * @since 2019.07.11
	 *
	 * @param  	int    	$user_id
	 * @param  	string 	$email
	 * @return 	int    	$wpdb->insert
	 */
	public static function update_user_email($user_id, $email) {
		$db = static::update_user_openid($user_id, 'email', $email);

		// 更新WordPress账户email
		if ($db) {
			$db = wp_update_user(['ID' => $user_id, 'user_email' => $email]);
		}

		return $db;
	}

	/**
	 * 写入用户手机号码
	 * @since 2019.07.11
	 *
	 * @param  	int    	$user_id
	 * @param  	string 	$phone
	 * @return 	int    	$wpdb->insert
	 */
	public static function update_user_phone($user_id, $phone) {
		return static::update_user_openid($user_id, 'phone', $phone);
	}

	/**
	 * 根据类型构造 AUTH 对象缓存组
	 */
	private static function get_auth_cache_group(string $type): string {
		return 'wnd_auth_' . $type;
	}
}
