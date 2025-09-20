<?php
namespace Wnd\Action\User;

use Wnd\Action\Wnd_Action_User;
use Exception;

/**
 * 用户主动注销账号
 * @since 2019.07.23
 */
class Wnd_Cancel_Account extends Wnd_Action_User {

	protected function execute(): array{
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$action = wp_delete_user($this->user_id);
		if ($action) {
			return ['status' => 1, 'msg' => __('删除成功', 'wnd')];
		} else {
			return ['status' => 0, 'msg' => __('删除失败', 'wnd')];
		}
	}

	protected function check() {
		if (is_super_admin($this->user_id)) {
			throw new Exception(__('无法删除超级管理员', 'wnd'));
		}

		/**
		 * @since 0.8.64
		 */
		$can_delete_user = apply_filters('wnd_can_delete_user', ['status' => 1, 'msg' => ''], $this->user_id);
		if (0 === $can_delete_user['status']) {
			throw new Exception($can_delete_user['msg']);
		}
	}
}
