<?php
namespace Wnd\Action;

use Exception;

/**
 * 删除用户
 * @since 2020.04.30
 */
class Wnd_Delete_User extends Wnd_Action_Root {

	private $target_user_id;

	public function execute(): array{
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$action = wp_delete_user($this->target_user_id);
		if ($action) {
			return ['status' => 1, 'msg' => __('删除成功', 'wnd')];
		} else {
			return ['status' => 0, 'msg' => __('删除失败', 'wnd')];
		}
	}

	protected function check() {
		$this->target_user_id = (int) $this->data['user_id'];
		if (!$this->target_user_id) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		if (is_super_admin($this->target_user_id)) {
			throw new Exception(__('无法删除超级管理员', 'wnd'));
		}

		/**
		 * @since 0.8.64
		 */
		$can_delete_user = apply_filters('wnd_can_delete_user', ['status' => 1, 'msg' => ''], $this->target_user_id);
		if (0 === $can_delete_user['status']) {
			throw new Exception($can_delete_user['msg']);
		}
	}
}
