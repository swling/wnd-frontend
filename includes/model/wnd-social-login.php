<?php
namespace Wnd\Model;

use Exception;
use WP_User;

/**
 * 社交登录
 * @since 0.9.57.6
 */
class Wnd_Social_Login {

	private $type;
	private $open_id;
	private $display_name;
	private $avatar_url;
	private $current_user;

	/**
	 * 对外封装一个静态方法
	 */
	public static function login(string $type, string $open_id, string $display_name, string $avatar_url): WP_User{
		$login = new static($type, $open_id, $display_name, $avatar_url);
		return is_user_logged_in() ? $login->update_user_social_login() : $login->handle_login();
	}

	/**
	 * 根据第三方网站获取的用户信息，注册或者登录到WordPress站点
	 *
	 * @param string $type         	第三方账号类型
	 * @param string $open_id      	第三方账号openID
	 * @param string $display_name 	用户名称
	 * @param string $avatar_url   	用户外链头像
	 */
	public function __construct(string $type, string $open_id, string $display_name, string $avatar_url) {
		if (!$type or !$open_id) {
			throw new Exception('Invalid parameter. type:' . $type . '; openid:' . $open_id . '; display_name:' . $display_name);
		}

		$this->type         = strtolower($type);
		$this->open_id      = $open_id;
		$this->display_name = $display_name;
		$this->avatar_url   = $avatar_url;
		$this->current_user = wp_get_current_user();
	}

	private function handle_login(): WP_User{
		// 根据openid登录或注册新用户
		$user = wnd_get_user_by_openid($this->type, $this->open_id);
		if (!$user) {
			$user = $this->register_user();
		}

		// Cookie
		wp_set_auth_cookie($user->ID, true);

		// 同步头像并登录
		if ($this->avatar_url) {
			wnd_update_user_meta($user->ID, 'avatar_url', $this->avatar_url);
		}

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
	 * 注册新用户并绑定当前 openid
	 */
	private function register_user(): WP_User {
		if (!$this->display_name) {
			throw new Exception('display_name is empty');
		}

		$user_login = wnd_generate_login();
		$user_pass  = wp_generate_password();
		$user_data  = ['user_login' => $user_login, 'user_pass' => $user_pass, 'display_name' => $this->display_name];
		$user_id    = wp_insert_user($user_data);

		if (is_wp_error($user_id)) {
			throw new Exception(__('注册失败', 'wnd'));
		}

		wnd_update_user_openid($user_id, $this->type, $this->open_id);
		return get_user_by('id', $user_id);
	}

	/**
	 * 更新当前用户社交登录
	 */
	private function update_user_social_login(): WP_User{
		wnd_update_user_openid($this->current_user->ID, $this->type, $this->open_id);

		if ($this->avatar_url) {
			wnd_update_user_meta($this->current_user->ID, 'avatar_url', $this->avatar_url);
		}

		return $this->current_user;
	}
}
