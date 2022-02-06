<?php
namespace Wnd\Action\User;

use Wnd\Action\Wnd_Action_User;
use Wnd\Model\Wnd_Binder;

/**
 * 已登录用户绑定邮箱或手机
 * @since 2019.07.23
 */
class Wnd_Bind_Account extends Wnd_Action_User {

	private $email_or_phone;
	private $auth_code;
	private $password;

	protected function execute(): array{
		$bind = Wnd_Binder::get_instance($this->email_or_phone);
		$bind->set_password($this->password);
		$bind->set_auth_code($this->auth_code);
		$bind->bind();
		return ['status' => 4, 'msg' => __('绑定成功', 'wnd')];
	}

	protected function parse_data() {
		$this->email_or_phone = $this->data['_user_user_email'] ?? ($this->data['phone'] ?? '');
		$this->auth_code      = $this->data['auth_code'] ?? '';
		$this->password       = $this->data['_user_user_pass'] ?? '';
	}
}
