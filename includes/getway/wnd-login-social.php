<?php
namespace Wnd\Getway;

use Exception;
use Wnd\Model\Wnd_User;
use WP_User;

/**
 * 社交登录抽象类
 * @since 2019.09.27
 */
abstract class Wnd_Login_Social {

	protected $user_id;
	protected $app_id;
	protected $app_key;

	protected $domain;
	protected $token;
	protected $open_id;
	protected $display_name;
	protected $avatar_url;

	protected $redirect_url;

	public function __construct() {
		$this->user_id      = get_current_user_id();
		$this->redirect_url = wnd_get_endpoint_url('wnd_social_login');
	}

	/**
	 * 根据$domain自动选择子类
	 */
	public static function get_instance($domain) {
		$class_name = '\Wnd\Getway\Login\\' . $domain;
		if (class_exists($class_name)) {
			return new $class_name();
		} else {
			throw new Exception(__('指定社交登录类未定义', 'wnd'));
		}
	}

	/**
	 * 创建授权地址
	 */
	abstract public function build_oauth_url();

	/**
	 * 创建自定义state
	 */
	protected static function build_state($domain) {
		return $domain . '|' . wp_create_nonce('social_login') . '|' . get_locale();
	}

	/**
	 * 解析自定义state
	 */
	public static function parse_state($state) {
		$state_array = explode('|', $state);
		return [
			'domain'     => $state_array[0] ?? false,
			'nonce'      => $state_array[1] ?? false,
			WND_LANG_KEY => $state_array[2] ?? false,
		];
	}

	/**
	 * 校验自定义state nonce
	 */
	protected static function check_state_nonce($state) {
		$nonce = static::parse_state($state)['nonce'];
		if (!wp_verify_nonce($nonce, 'social_login')) {
			throw new Exception(__('验证失败，请返回页面并刷新重试', 'wnd'));
		}
	}

	/**
	 * 根据用户授权码获取token
	 */
	abstract protected function get_token();

	/**
	 * 根据token和open id获取用户信息
	 */
	abstract protected function get_user_info();

	/**
	 * 根据第三方平台用户信息登录或创建账户
	 */
	public function login() {
		$this->get_token();
		$this->get_user_info();

		// 根据open id创建或登录账户
		static::login_social($this->domain, $this->open_id, $this->display_name, $this->avatar_url);
		wp_redirect(Wnd_User::get_reg_redirect_url());
		exit();
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
	public static function login_social($type, $open_id, $display_name, $avatar_url): WP_User {
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
