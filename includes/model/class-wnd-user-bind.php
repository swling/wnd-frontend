<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Auth;

/**
 *@since 2019.11.26
 *用户绑定邮箱或手机
 */
class Wnd_User_Bind {

	protected $user;
	protected $password;
	protected $auth_code;
	protected $email_or_phone;
	protected $bind_type;

	public function __construct() {
		$this->user = wp_get_current_user();
		if (!$this->user->ID) {
			throw new Exception('请登录！');
		}
	}

	/**
	 *设置当前账户密码
	 */
	public function set_password($password) {
		$this->password = $password;
	}

	/**
	 *设置邮件或手机号码
	 */
	public function set_email_or_phone($email_or_phone) {
		$this->email_or_phone = $email_or_phone;
		$this->bind_type      = is_email($this->email_or_phone) ? 'email' : 'phone';
	}

	/**
	 *设置验证码
	 */
	public function set_auth_code($auth_code) {
		$this->auth_code = $auth_code;
	}

	/**
	 *核对验证码并绑定
	 */
	public function bind() {
		// 更改邮箱或手机需要验证当前密码、首次绑定不需要
		$old_bind = ('email' == $this->bind_type) ? $this->user->data->user_email : wnd_get_user_phone($this->user->ID);
		if ($old_bind and !wp_check_password($this->password, $this->user->data->user_pass, $this->user->ID)) {
			throw new Exception('当前密码错误！');
		}

		// 核对验证码并绑定
		try {
			$auth = new Wnd_Auth;
			$auth->set_type('bind');
			$auth->set_auth_code($this->auth_code);
			$auth->set_email_or_phone($this->email_or_phone);

			/**
			 * 通常，正常前端注册的用户，已通过了邮件或短信验证中的一种，已有数据记录，绑定成功后更新对应数据记录，并删除当前验证数据记录
			 * 删除时会验证该条记录是否绑定用户，只删除未绑定用户的记录
			 * 若当前用户没有任何验证绑定记录，删除本条验证记录后，会通过 wnd_update_user_email() / wnd_update_user_phone() 重新新增一条记录
			 */
			$auth->verify(true);

			if ('email' == $this->bind_type) {
				$bind = wnd_update_user_email($this->user->ID, $this->email_or_phone);
			} else {
				$bind = wnd_update_user_phone($this->user->ID, $this->email_or_phone);
			}

			if (!$bind) {
				throw new Exception('未知错误！');
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
}
