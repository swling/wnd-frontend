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
	 * 主要数据：user_id、email、phone……
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
		$user_data     = Wnd_Auth::get_user_auth_records($user_id);

		if ($user_data) {
			foreach ($user_data as $data) {
				$type = $data->type;
				if (!$type) {
					continue;
				}

				$user->$type = $data->identifier;
			}
			unset($data);
		}

		static::update_wnd_user_caches($user);

		return $user;
	}

	/**
	 * 根据第三方网站获取的用户信息，注册或者登录到WordPress站点
	 * @since 2019.07.23
	 *
	 * @param string $type         	第三方账号类型
	 * @param string $open_id      	第三方账号openID
	 * @param string $display_name 	用户名称
	 * @param string $avatar_url   	用户外链头像
	 */
	public static function social_login($type, $open_id, $display_name, $avatar_url): WP_User {
		/**
		 * 社交登录必须获取用户昵称
		 * @since 0.8.73
		 */
		if (!$display_name) {
			throw new Exception(__('昵称无效', 'wnd'));
		}

		//当前用户已登录：新增绑定或同步信息
		if (is_user_logged_in()) {
			$this_user   = wp_get_current_user();
			$may_be_user = static::get_user_by_openid($type, $open_id);
			if ($may_be_user and $may_be_user->ID != $this_user->ID) {
				throw new Exception(__('OpenID 已被其他账户占用', 'wnd'));
			}

			if ($avatar_url) {
				wnd_update_user_meta($this_user->ID, 'avatar_url', $avatar_url);
			}
			if ($open_id) {
				static::update_user_openid($this_user->ID, $type, $open_id);
			}

			return $this_user;
		}

		//当前用户未登录：注册或者登录
		$user = static::get_user_by_openid($type, $open_id);
		if (!$user) {
			$user_login = wnd_generate_login();
			$user_pass  = wp_generate_password();
			$user_data  = ['user_login' => $user_login, 'user_pass' => $user_pass, 'display_name' => $display_name];
			$user_id    = wp_insert_user($user_data);

			if (is_wp_error($user_id)) {
				throw new Exception(__('注册失败', 'wnd'));
			}

			static::update_user_openid($user_id, $type, $open_id);
			$user = get_user_by('id', $user_id);
		}

		// 同步头像并登录
		$user_id = $user->ID;
		wnd_update_user_meta($user_id, 'avatar_url', $avatar_url);
		wp_set_auth_cookie($user_id, true);

		/**
		 * @since 0.8.61
		 *
		 * @param object WP_User
		 */
		do_action('wnd_login', $user);

		/**
		 * Fires after the user has successfully logged in.
		 * @see （本代码段从 wp_signon 复制而来)
		 * @since 1.5.0
		 *
		 * @param string  $user_login Username.
		 * @param WP_User $user       WP_User object of the logged-in user.
		 */
		do_action('wp_login', $user->user_login, $user);

		return $user;
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
	 * @param  	string 	$type           			第三方账号类型
	 * @param  	string 	$open_id
	 * @return 	int    	$wpdb->insert
	 */
	public static function update_user_openid($user_id, $type, $open_id) {
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
