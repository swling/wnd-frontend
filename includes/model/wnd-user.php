<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Auth;
use WP_User;

/**
 * 用户
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
		global $wpdb;
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

		// 设置/更新 对象缓存
		$user_data = (object) $data;
		static::update_wnd_user_caches($user_data);

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
	 * @since 2019.01.26 根据用户id获取号码
	 *
	 * @param  	int          			$user_id
	 * @return 	string|false 	用户手机号或false
	 */
	public static function get_user_phone($user_id) {
		if (!$user_id) {
			return '';
		}

		return static::get_wnd_user($user_id)->phone ?? '';
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

		return static::get_wnd_user($user_id)->$type ?? '';
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
		$user        = static::get_wnd_user($user_id);
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
			static::clean_wnd_user_caches($user);
		}

		return $db ? $user_id : 0;
	}

	/**
	 * 删除用户 open id
	 * @since 0.9.4
	 *
	 * @param  	int    	$user_id
	 * @param  	string 	$type           			第三方账号类型
	 * @return 	int    	$wpdb->delete
	 */
	public static function delete_user_openid($user_id, $type) {
		global $wpdb;
		$type = strtolower($type);

		// 查询
		$user    = static::get_wnd_user($user_id);
		$open_id = $user->$type ?? '';
		$db      = Wnd_Auth::delete_db($type, $open_id);

		// 缓存
		if ($db) {
			static::clean_wnd_user_caches($user);
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
	 * @since 2019.11.06
	 *
	 * @param object $user Wnd_user表对象
	 */
	public static function clean_wnd_user_caches(object $user_data) {
		$user_id = $user_data->user_id ?? 0;
		if (!$user_id) {
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
	 * 用户角色为：管理员或编辑 返回 true
	 * @since 初始化 判断当前用户是否为管理员
	 *
	 * @param  	int    	$user_id
	 * @return 	bool
	 */
	public static function is_manager($user_id = 0) {
		$user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();

		$user_role = $user->roles[0] ?? false;
		if ('administrator' == $user_role or 'editor' == $user_role) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @since 2020.04.30 判断当前用户是否已被锁定：wp user meta：status
	 *
	 * @param  	int    	$user_id
	 * @return 	bool
	 */
	public static function has_been_banned($user_id = 0) {
		$user_id = $user_id ?: get_current_user_id();
		$status  = get_user_meta($user_id, 'status', true);

		return 'banned' == $status ? true : false;
	}

	/**
	 * 用户display name去重
	 * @since 初始化
	 *
	 * @param  	string      		$display_name
	 * @param  	int         		$exclude_id
	 * @return 	int|false
	 */
	public static function is_name_duplicated($display_name, $exclude_id = 0) {
		// 名称为空
		if (empty($display_name)) {
			return false;
		}

		global $wpdb;
		$results = $wpdb->get_var($wpdb->prepare(
			"SELECT ID FROM $wpdb->users WHERE display_name = %s AND  ID != %d  limit 1",
			$display_name,
			$exclude_id
		));

		return $results ?: false;
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

	/**
	 * 获取用户面板允许的post types
	 * @since 2019.06.10
	 *
	 * @return array 	文章类型数组
	 */
	public static function get_user_panel_post_types() {
		$post_types = get_post_types(['public' => true], 'names', 'and');
		// 排除页面/附件/站内信
		unset($post_types['page'], $post_types['attachment'], $post_types['mail']);
		return apply_filters('wnd_user_panel_post_types', $post_types);
	}

	/**
	 * 获取注册后跳转地址
	 * @since 2020.04.11
	 */
	public static function get_reg_redirect_url() {
		return wnd_get_config('reg_redirect_url') ?: home_url();
	}

	/**
	 * 获取用户语言
	 * 该语言不同于WP原生的get_user_locale
	 * WP原生存储与wp user meta；本插件存储与wnd user meta：目的是减少一行数据库记录
	 * @since 2020.04.11
	 */
	public static function get_user_locale($user_id) {
		return wnd_get_user_meta($user_id, 'locale') ?: 'default';
	}
}
