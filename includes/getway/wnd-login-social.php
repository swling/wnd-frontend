<?php
namespace Wnd\Getway;

use Exception;
use Wnd\Model\Wnd_Social_Login;

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
	protected $email;

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
		Wnd_Social_Login::login($this->domain, $this->open_id, $this->display_name, $this->avatar_url, $this->email);
		wp_redirect(wnd_get_reg_redirect_url());
		exit();
	}
}
