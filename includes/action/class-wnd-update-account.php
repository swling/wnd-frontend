<?php
namespace Wnd\Action;

/**
 *@since 初始化
 *用户账户更新：修改密码
 *@param $_POST['_user_user_pass']
 *@param $_POST['_user_new_pass']
 *@param $_POST['_user_new_pass_repeat']
 */
class Wnd_Update_Account extends Wnd_Action_Ajax {

	public static function execute(): array{
		$user    = wp_get_current_user();
		$user_id = $user->ID;
		if (!$user_id) {
			return ['status' => 0, 'msg' => '获取用户ID失败'];
		}

		$user_array          = ['ID' => $user_id];
		$user_pass           = $_POST['_user_user_pass'] ?? null;
		$new_password        = $_POST['_user_new_pass'] ?? null;
		$new_password_repeat = $_POST['_user_new_pass_repeat'] ?? null;

		// 修改密码
		if (!empty($new_password_repeat)) {
			if (strlen($new_password) < 6) {
				return ['status' => 0, 'msg' => '新密码不能低于6位'];

			} elseif ($new_password_repeat != $new_password) {
				return ['status' => 0, 'msg' => '两次输入的新密码不匹配'];

			} else {
				$user_array['user_pass'] = $new_password;
			}
		}

		// 原始密码校验
		if (!wp_check_password($user_pass, $user->data->user_pass, $user->ID)) {
			return ['status' => 0, 'msg' => '初始密码错误'];
		}

		// 更新权限过滤挂钩
		$user_can_update_account = apply_filters('wnd_can_update_account', ['status' => 1, 'msg' => '默认通过']);
		if ($user_can_update_account['status'] === 0) {
			return $user_can_update_account;
		}

		// 更新用户
		$user_id = wp_update_user($user_array);
		if (is_wp_error($user_id)) {
			return ['status' => 0, 'msg' => $user_id->get_error_message()];
		}

		// 用户更新成功：更新账户会导致当前账户的wp nonce失效，需刷新页面
		return apply_filters('wnd_update_account_return', ['status' => 4, 'msg' => '更新成功'], $user_id);
	}
}