<?php
namespace Wnd\Action;

use Exception;

/**
 *删除用户
 *@since 2020.04.30
 *@param $_POST['user_id'];
 */
class Wnd_Delete_User extends Wnd_Action_Ajax_Root {

	public function execute(): array{
		$user_id = (int) $this->data['user_id'];
		$confirm = $this->data['confirm'] ?? false;
		if (!$user_id) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		if (is_super_admin($user_id)) {
			throw new Exception(__('无法删除超级管理员', 'wnd'));
		}

		if (!$confirm) {
			throw new Exception(__('请确认操作', 'wnd'));
		}

		/**
		 *@since 0.8.64
		 *
		 *删除用户权限检测过滤
		 */
		$wnd_can_delete_user = apply_filters('wnd_can_delete_user', ['status' => 1, 'msg' => ''], $user_id);
		if (0 === $wnd_can_delete_user['status']) {
			return $wnd_can_delete_user;
		}

		require_once ABSPATH . 'wp-admin/includes/user.php';
		$action = wp_delete_user($user_id);
		if ($action) {
			return ['status' => 1, 'msg' => __('删除成功', 'wnd')];
		} else {
			return ['status' => 1, 'msg' => __('删除失败', 'wnd')];
		}
	}
}
