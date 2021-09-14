<?php
namespace Wnd\Action;

use Wnd\Model\Wnd_Binder;

/**
 * 已登录用户绑定邮箱或手机
 * @since 2019.07.23
 */
class Wnd_Bind_Account extends Wnd_Action_User {

	public function execute(): array{
		$email_or_phone = $this->data['_user_user_email'] ?? ($this->data['phone'] ?? '');
		$auth_code      = $this->data['auth_code'] ?? '';
		$password       = $this->data['_user_user_pass'] ?? '';

		// 绑定
		$bind = Wnd_Binder::get_instance($email_or_phone);
		$bind->set_password($password);
		$bind->set_auth_code($auth_code);
		$bind->bind();
		return ['status' => 4, 'msg' => __('绑定成功', 'wnd')];
	}
}
