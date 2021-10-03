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
	protected $device;

	private $password;
	private $auth_code;
	private $is_change;

	public function __construct(string $device) {
		$this->user = wp_get_current_user();
		if (!$this->user->ID) {
			throw new Exception(__('请登录', 'wnd'));
		}

		$this->device    = $device;
		$this->is_change = $this->is_change();
	}

	public static function get_instance($device) {
		if (is_email($device)) {
			return new Wnd_Binder_Email($device);
		} elseif (wnd_is_mobile($device)) {
			return new Wnd_Binder_Phone($device);
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

	/**
	 * 绑定
	 */
	public function bind() {
		$this->check_password();
		$this->verify_auth_code();
		$this->bind_object();
	}

	/**
	 * 更改邮箱或手机需要验证当前密码、首次绑定不需要
	 *
	 */
	private function check_password() {
		if (!$this->is_change) {
			return;
		}

		if (!wp_check_password($this->password, $this->user->data->user_pass, $this->user->ID)) {
			throw new Exception(__('当前密码错误', 'wnd'));
		}
	}

	/**
	 * 核对验证码并绑定
	 * 可能抛出异常
	 */
	private function verify_auth_code() {
		$auth = Wnd_Auth::get_instance($this->device);
		$auth->set_type('bind');
		$auth->set_auth_code($this->auth_code);
		$auth->verify();
	}

	/**
	 * 是否为更改操作
	 * @since 0.9.38
	 */
	abstract protected function is_change(): bool;

	/**
	 * 更新数据库
	 *
	 */
	abstract protected function bind_object();
}
