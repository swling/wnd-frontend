<?php
namespace Wnd\Controller;

/**
 *@since 初始化
 *用户账户更新：修改密码，邮箱
 *@param $_POST['_user_user_pass']
 *@param $_POST['_user_new_pass']
 *@param $_POST['_user_new_pass_repeat']
 *@param $_POST['_user_user_email']
 */
class Wnd_Update_Account extends Wnd_Controller_Ajax {

	public static function execute(): array{
		$user    = wp_get_current_user();
		$user_id = $user->ID;
		if (!$user_id) {
			return array('status' => 0, 'msg' => '获取用户ID失败！');
		}

		$user_array          = array('ID' => $user_id);
		$user_pass           = $_POST['_user_user_pass'] ?? null;
		$new_password        = $_POST['_user_new_pass'] ?? null;
		$new_password_repeat = $_POST['_user_new_pass_repeat'] ?? null;
		$new_email           = $_POST['_user_user_email'] ?? null;

		// 修改密码
		if (!empty($new_password_repeat)) {
			if (strlen($new_password) < 6) {
				return array('status' => 0, 'msg' => '新密码不能低于6位！');

			} elseif ($new_password_repeat != $new_password) {
				return array('status' => 0, 'msg' => '两次输入的新密码不匹配！');

			} else {
				$user_array['user_pass'] = $new_password;
			}
		}

		// 修改邮箱
		if ($new_email and $new_email != $user->user_email) {
			if (!is_email($new_email)) {
				return array('status' => 0, 'msg' => '邮件格式错误！');
			} else {
				$user_array['user_email'] = $new_email;
			}
		}

		// 原始密码校验
		if (!wp_check_password($user_pass, $user->data->user_pass, $user->ID)) {
			return array('status' => 0, 'msg' => '初始密码错误！');
		}

		// 更新权限过滤挂钩
		$user_can_update_account = apply_filters('wnd_can_update_account', array('status' => 1, 'msg' => '默认通过'));
		if ($user_can_update_account['status'] === 0) {
			return $user_can_update_account;
		}

		// 更新用户
		$user_id = wp_update_user($user_array);

		// 更新失败，返回错误信息
		if (is_wp_error($user_id)) {
			return array('status' => 0, 'msg' => $user_id->get_error_message());
		}

		// 用户更新成功：更新账户会导致当前账户的wp nonce失效，需刷新页面
		$return_array = apply_filters('wnd_update_account_return', array('status' => 4, 'msg' => '更新成功'), $user_id);
		return $return_array;
	}
}
