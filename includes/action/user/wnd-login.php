<?php
namespace Wnd\Action\User;

use Exception;
use Wnd\Action\Wnd_Action;
use Wnd\Utility\Wnd_JWT_handler;

/**
 * @since 2019.1.13 用户登录
 */
class Wnd_Login extends Wnd_Action {

	private $username;
	private $password;
	private $remember;
	private $redirect_to;
	private $target_user;
	private $type;

	/**
	 * 登录
	 * - 设置当前用户
	 * - App 登录返回 token
	 * - 常规登录设置 cookie 并返回对应数据
	 */
	protected function execute(): array{
		/**
		 * 设置当前用户
		 */
		wp_set_current_user($this->target_user->ID);

		/**
		 * App 登录返回 Token
		 */
		if ('token' == $this->type) {
			$this->do_login_action();
			return ['status' => 1, 'msg' => __('登录成功', 'wnd'), 'data' => static::generate_token($this->target_user->ID)];
		}

		/**
		 * 常规网页登录
		 */
		wp_set_auth_cookie($this->target_user->ID, $this->remember);
		$this->do_login_action();
		if ($this->redirect_to) {
			return ['status' => 3, 'msg' => __('登录成功', 'wnd'), 'data' => ['redirect_to' => $this->redirect_to, 'user_id' => $this->target_user->ID]];
		} else {
			return ['status' => 4, 'msg' => __('登录成功', 'wnd'), 'data' => ['user_id' => $this->target_user->ID]];
		}
	}

	/**
	 * 解析数据
	 * @since 0.9.57.7
	 */
	protected function parse_data() {
		$this->username    = trim($this->data['_user_user_login']);
		$this->password    = $this->data['_user_user_pass'];
		$this->remember    = (bool) ($this->data['remember'] ?? false);
		$this->redirect_to = $_REQUEST['redirect_to'] ?? home_url();
		$this->type        = $this->data['type'] ?? '';
	}

	/**
	 * 核查密码
	 */
	protected function check() {
		// 可根据邮箱，手机，或用户名查询用户
		$this->target_user = wnd_get_user_by($this->username);
		if (!$this->target_user) {
			throw new Exception(__('用户不存在', 'wnd'));
		}

		// 登录过滤挂钩
		$can_login = apply_filters('wnd_can_login', ['status' => 1, 'msg' => ''], $this->target_user);
		if (0 === $can_login['status']) {
			throw new Exception($can_login['msg']);
		}

		if (!wp_check_password($this->password, $this->target_user->data->user_pass, $this->target_user->ID)) {
			/**
			 * @since 0.8.61
			 *
			 * @param object WP_User
			 */
			do_action('wnd_login_failed', $this->target_user);

			throw new Exception(__('密码错误', 'wnd'));
		}
	}

	/**
	 * 生成 Token
	 * @since 0.9.57.5
	 */
	private static function generate_token(int $user_id): array{
		$jwt   = Wnd_JWT_Handler::get_instance();
		$token = $jwt->generate_token($user_id);
		$exp   = $jwt->parse_token($token)['exp'] ?? 0;

		return ['token' => $token, 'exp' => $exp];
	}

	/**
	 * 登录成功后需要执行的 Action
	 * @since 0.9.57.5
	 */
	private function do_login_action() {
		/**
		 * @since 0.8.61
		 *
		 * @param object WP_User
		 */
		do_action('wnd_login', $this->target_user);

		/**
		 * Fires after the user has successfully logged in.
		 * @see （本代码段从 wp_signon 复制而来)
		 * @since 1.5.0
		 *
		 * @param string  $user_login Username.
		 * @param WP_User $user       WP_User object of the logged-in user.
		 */
		do_action('wp_login', $this->target_user->user_login, $this->target_user);
	}
}
