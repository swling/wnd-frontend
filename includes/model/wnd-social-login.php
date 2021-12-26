<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_User;
use WP_User;

/**
 * 用户
 * @since 2019.10.25
 */
abstract class Wnd_Social_Login {

	/**
	 * 根据第三方网站获取的用户信息，注册或者登录到WordPress站点
	 * @since 2019.07.23
	 *
	 * @param string $type         	第三方账号类型
	 * @param string $open_id      	第三方账号openID
	 * @param string $display_name 	用户名称
	 * @param string $avatar_url   	用户外链头像
	 */
	public static function login($type, $open_id, $display_name, $avatar_url): WP_User {
		/**
		 * $type, $open_id, $display_name 必须为有效值
		 * @since 0.9.50
		 */
		if (!$type or !$open_id or !$display_name) {
			throw new Exception('Invalid parameter. type:' . $type . '; openid:' . $open_id . '; display_name:' . $display_name);
		}

		//当前用户已登录：新增绑定或同步信息
		if (is_user_logged_in()) {
			$this_user   = wp_get_current_user();
			$may_be_user = Wnd_User::get_user_by_openid($type, $open_id);
			if ($may_be_user and $may_be_user->ID != $this_user->ID) {
				throw new Exception(__('OpenID 已被其他账户占用', 'wnd'));
			}

			if ($avatar_url) {
				wnd_update_user_meta($this_user->ID, 'avatar_url', $avatar_url);
			}
			if ($open_id) {
				Wnd_User::update_user_openid($this_user->ID, $type, $open_id);
			}

			return $this_user;
		}

		//当前用户未登录：注册或者登录
		$user = Wnd_User::get_user_by_openid($type, $open_id);
		if (!$user) {
			$user_login = wnd_generate_login();
			$user_pass  = wp_generate_password();
			$user_data  = ['user_login' => $user_login, 'user_pass' => $user_pass, 'display_name' => $display_name];
			$user_id    = wp_insert_user($user_data);

			if (is_wp_error($user_id)) {
				throw new Exception(__('注册失败', 'wnd'));
			}

			Wnd_User::update_user_openid($user_id, $type, $open_id);
			$user = get_user_by('id', $user_id);
		}

		// 同步头像并登录
		$user_id = $user->ID;
		if ($avatar_url) {
			wnd_update_user_meta($user_id, 'avatar_url', $avatar_url);
		}
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
}
