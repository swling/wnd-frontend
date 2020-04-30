<?php
namespace Wnd\Action;

/**
 *封禁用户
 *@since 2020.04.30
 *@param $_POST['user_id'];
 */
class Wnd_Ban_User extends Wnd_Action_Ajax {

	public static function execute(): array{
		$user_id = $_POST['user_id'] ?? 0;
		$status  = $_POST['status'] ?? false;
		$status  = 'ok' == $status ? '' : 'banned';
		if (!$user_id) {
			return ['status' => 0, 'msg' => __('ID无效', 'wnd')];
		}

		if (!wnd_is_manager()) {
			return ['status' => 0, 'msg' => __('权限不足', 'wnd')];
		}

		// 封禁用户
		$action = wnd_update_user_meta($user_id, 'status', $status);
		if ($action) {
			return ['status' => 1, 'msg' => 'banned' == $status ? __('账户已被封禁', 'wnd') : __('账户已解封', 'wnd')];
		} else {
			return ['status' => 0, 'msg' => __('操作失败', 'wnd')];
		}
	}
}
