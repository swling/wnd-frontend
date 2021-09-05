<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Auth;

/**
 * 用户绑定
 * @since 2019.11.26
 */
abstract class Wnd_Binder {

	protected $user;
	protected $password;
	protected $auth_code;
	protected $bound_object;

	public function __construct() {
		$this->user = wp_get_current_user();
		if (!$this->user->ID) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}

	public static function get_instance($bound_object) {
		if (is_email($bound_object)) {
			return new Wnd_Binder_Email($bound_object);
		} elseif (wnd_is_mobile($bound_object)) {
			return new Wnd_Binder_Phone($bound_object);
		} else {
			throw new Exception(__('格式不正确', 'wnd'));
		}
	}

	/**
	 * 设置当前账户密码
	 */
	public function set_password($password) {
		$this->password = $password;
	}

	/**
	 * 设置验证码
	 */
	public function set_auth_code($auth_code) {
		$this->auth_code = $auth_code;
	}

	abstract public function bind();

	/**
	 * 核对验证码并绑定
	 *
	 * 可能抛出异常
	 */
	protected function verify_auth_code() {
		$auth = Wnd_Auth::get_instance($this->bound_object);
		$auth->set_type('bind');
		$auth->set_auth_code($this->auth_code);
		$auth->verify();
	}
}
