<?php
namespace Wnd\Action\User;

use Exception;
use Wnd\Action\Wnd_Action_User;

/**
 * 用户账户更新：修改密码
 * @since 初始化
 */
class Wnd_Update_Account extends Wnd_Action_User {

	private $user_data;

	protected function execute(): array{
		// 更新用户
		$user_id = wp_update_user($this->user_data);
		if (is_wp_error($user_id)) {
			throw new Exception($user_id->get_error_message());
		}

		// 用户更新成功：更新账户会导致当前账户的wp nonce失效，需刷新页面
		return apply_filters('wnd_update_account_return', ['status' => 4, 'msg' => __('更新成功', 'wnd')], $user_id);
	}

	protected function check() {
		$this->user_data     = ['ID' => $this->user_id];
		$user_pass           = $this->data['_user_user_pass'] ?? '';
		$new_password        = $this->data['_user_new_pass'] ?? '';
		$new_password_repeat = $this->data['_user_new_pass_repeat'] ?? '';

		// 修改密码
		if (!empty($new_password_repeat)) {
			if (strlen($new_password) < 6) {
				throw new Exception(__('密码不能低于6位', 'wnd'));
			}

			if ($new_password_repeat != $new_password) {
				throw new Exception(__('两次输入的新密码不匹配', 'wnd'));
			}

			$this->user_data['user_pass'] = $new_password;
		}

		// 原始密码校验
		if (!wp_check_password($user_pass, $this->user->data->user_pass, $this->user->ID)) {
			throw new Exception(__('密码错误', 'wnd'));
		}

		// 更新权限过滤挂钩
		$user_can_update_account = apply_filters('wnd_can_update_account', ['status' => 1, 'msg' => ''], $this->data);
		if (0 === $user_can_update_account['status']) {
			throw new Exception($user_can_update_account['msg']);
		}
	}
}
