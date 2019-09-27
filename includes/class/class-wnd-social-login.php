<?php
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
	 *根据第三方平台用户信息登录或创建账户
	 */
	public function login() {
		$this->get_token();
		$this->get_open_id();
		$this->get_user_info();

		// 根据open id创建或登录账户
		wnd_social_login($this->open_id, $this->display_name, $this->avatar_url);
	}

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
}
