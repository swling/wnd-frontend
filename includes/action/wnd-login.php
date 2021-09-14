<?php
namespace Wnd\Action;

use Exception;

/**
 * @since 2019.1.13 用户登录
 */
class Wnd_Login extends Wnd_Action {

	public function execute(): array{
		$username    = trim($this->data['_user_user_login']);
		$password    = $this->data['_user_user_pass'];
		$remember    = $this->data['remember'] ?? 0;
		$remember    = 1 == $remember ? true : false;
		$redirect_to = $_REQUEST['redirect_to'] ?? home_url();

		// 可根据邮箱，手机，或用户名查询用户
		$user = wnd_get_user_by($username);
		if (!$user) {
			throw new Exception(__('用户不存在', 'wnd'));
		}

		// 登录过滤挂钩
		$wnd_can_login = apply_filters('wnd_can_login', ['status' => 1, 'msg' => ''], $user);
		if (0 === $wnd_can_login['status']) {
			return $wnd_can_login;
		}

		/**
		 * 校验密码并登录
		 *
		 * - 之所以不采用 wp_signon，是因为我们需要对登录错误信息进行控制，如提示忘记密码的重设链接等
		 * - 由于上述原因，我们从 wp_signon 复制了 do_action('wp_login', $user->user_login, $user);
		 *   用于解决可能存在的钩子兼容问题
		 */
		if (!wp_check_password($password, $user->data->user_pass, $user->ID)) {
			/**
			 * @since 0.8.61
			 *
			 * @param object WP_User
			 */
			do_action('wnd_login_failed', $user);

			throw new Exception(__('密码错误', 'wnd'));
		}

		wp_set_current_user($user->ID);
		wp_set_auth_cookie($user->ID, $remember);

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

		if ($redirect_to) {
			return ['status' => 3, 'msg' => __('登录成功', 'wnd'), 'data' => ['redirect_to' => $redirect_to, 'user_id' => $user->ID]];
		} else {
			return ['status' => 4, 'msg' => __('登录成功', 'wnd'), 'data' => ['user_id' => $user->ID]];
		}
	}
}
