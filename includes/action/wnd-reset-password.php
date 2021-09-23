<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Auth;

/**
 * 用户找回密码
 * @since 2019.02.10
 */
class Wnd_Reset_Password extends Wnd_Action {

	private $target_user;
	private $new_password;

	public function execute(): array{
		reset_password($this->target_user, $this->new_password);
		return [
			'status' => $this->user_id ? 4 : 1,
			'msg'    => __('密码修改成功', 'wnd') . '&nbsp;' . wnd_modal_link(__('登录', 'wnd'), 'wnd_login_form'),
		];
	}

	protected function check() {
		$email_or_phone      = $this->data['_user_user_email'] ?? $this->data['phone'] ?? '';
		$new_password        = $this->data['_user_new_pass'] ?? '';
		$new_password_repeat = $this->data['_user_new_pass_repeat'] ?? '';
		$auth_code           = $this->data['auth_code'];

		// 验证密码正确性
		if (strlen($new_password) < 6) {
			throw new Exception(__('密码不能低于6位', 'wnd'));

		} elseif ($new_password_repeat != $new_password) {
			throw new Exception(__('两次输入的新密码不匹配', 'wnd'));
		}
		$this->new_password = $new_password;

		//获取用户
		$this->target_user = wnd_get_user_by($email_or_phone);
		if (!$this->target_user) {
			throw new Exception(__('账户未注册', 'wnd'));
		}

		/**
		 *
		 * 获取用户的方法：
		 * 已登录用户则为当前用户
		 * 未登录用户通过邮箱或手机获取
		 */
		$auth = Wnd_Auth::get_instance($email_or_phone);
		$auth->set_type('reset_password');
		$auth->set_auth_code($auth_code);
		$auth->verify();
	}
}
