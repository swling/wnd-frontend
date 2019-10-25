<?php
namespace Wnd\Controller;

/**
 *@since 2019.09.27
 *社交登录抽象类
 */
abstract class Wnd_Social_Login {

	protected $user_id;
	protected $app_id;
	protected $app_key;

	protected $token;
	protected $open_id;
	protected $display_name;
	protected $avatar_url;

	public function __construct() {
		$this->user_id = get_current_user_id();
	}

	/**
	 *设置第三方平台接口ID
	 */
	public function set_app_id($app_id) {
		$this->app_id = $app_id;
	}

	/**
	 *设置第三方平台接口ID
	 */
	public function set_app_key($app_key) {
		$this->app_key = $app_key;
	}

	/**
	 *创建授权地址
	 */
	abstract public function build_oauth_url($return_url);

	/**
	 *根据用户授权码获取token
	 */
	abstract protected function get_token();

	/**
	 *获取第三方平台授权open id
	 */
	abstract protected function get_open_id();

	/**
	 *根据token和open id获取用户信息
	 */
	abstract protected function get_user_info();

	/**
	 *根据第三方平台用户信息登录或创建账户
	 */
	public function login() {
		$this->get_token();
		$this->get_open_id();
		$this->get_user_info();

		// 根据open id创建或登录账户
		self::wnd_social_login($this->open_id, $this->display_name, $this->avatar_url);
	}

	/**
	 *@since 2019.07.23
	 *根据第三方网站获取的用户信息，注册或者登录到WordPress站点
	 *@param string $open_id 		第三方账号openID
	 *@param string $display_name 	用户名称
	 *@param string $avatar_url 	用户外链头像
	 *
	 **/
	public static function wnd_social_login($open_id, $display_name = '', $avatar_url = '') {
		//当前用户已登录，同步信息
		if (is_user_logged_in()) {
			$this_user   = wp_get_current_user();
			$may_be_user = wnd_get_user_by_openid($open_id);
			if ($may_be_user and $may_be_user->ID != $this_user->ID) {
				exit('OpenID已被其他账户占用！');
			}

			if ($avatar_url) {
				wnd_update_user_meta($this_user->ID, "avatar_url", $avatar_url);
			}
			if ($open_id) {
				wnd_update_user_openid($this_user->ID, $open_id);
			}
			wp_redirect(wnd_get_option('wnd', 'wnd_reg_redirect_url') ?: home_url());
			exit;
		}

		//当前用户未登录，注册或者登录 检测是否已注册
		$user = wnd_get_user_by_openid($open_id);
		if (!$user) {

			// 自定义随机用户名
			$user_login = wnd_generate_login();
			$user_pass  = wp_generate_password();
			$user_array = array('user_login' => $user_login, 'user_pass' => $user_pass, 'display_name' => $display_name);
			$user_id    = wp_insert_user($user_array);

			// 注册
			if (is_wp_error($user_id)) {
				wp_die($user_id->get_error_message(), get_option('blogname'));
			} else {
				wnd_update_user_openid($user_id, $open_id);
			}
		}

		// 获取用户id
		$user_id = $user ? $user->ID : $user_id;

		wnd_update_user_meta($user_id, "avatar_url", $avatar_url);
		wp_set_auth_cookie($user_id, true);
		wp_redirect(wnd_get_option('wnd', 'wnd_reg_redirect_url') ?: home_url());
		exit();
	}
}
