<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Form_Data;

/**
 *@since 初始化
 *用户资料修改：昵称，简介，字段等 修改账户密码、邮箱，请使用：wnd_wpdate_account
 *@param $_POST 	用户资料表单数据
 *
 *@see README.md
 *ajax user POST name规则：
 *user field：_user_{field}
 *user meta：
 *_usermeta_{key} （*自定义数组字段）
 *_wpusermeta_{key} （*WordPress原生字段）
 *
 */
class Wnd_Update_Profile extends Wnd_Action_Ajax {

	public static function execute(): array{
		$user    = wp_get_current_user();
		$user_id = $user->ID;
		if (!$user_id) {
			return array('status' => 0, 'msg' => '获取用户ID失败');
		}

		// 实例化WndWP表单数据处理对象
		try {
			$form_data = new Wnd_Form_Data();
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}
		$user_array         = $form_data->get_user_array();
		$user_array['ID']   = $user_id;
		$user_meta_array    = $form_data->get_user_meta_array();
		$wp_user_meta_array = $form_data->get_wp_user_meta_array();

		// 更新权限过滤挂钩
		$user_can_update_profile = apply_filters('wnd_can_update_profile', array('status' => 1, 'msg' => '默认通过'));
		if ($user_can_update_profile['status'] === 0) {
			return $user_can_update_profile;
		}

		// 更新用户
		$user_id = wp_update_user($user_array);
		if (is_wp_error($user_id)) {
			$msg = $user_id->get_error_message();
			return array('status' => 0, 'msg' => $msg);
		}

		// 更新meta
		if (!empty($user_meta_array)) {
			wnd_update_user_meta_array($user_id, $user_meta_array);
		}

		if (!empty($wp_user_meta_array)) {
			foreach ($wp_user_meta_array as $key => $value) {
				// 下拉菜单默认未选择时，值为 -1 。过滤
				if ($value !== '-1') {
					update_user_meta($user_id, $key, $value);
				}
			}
			unset($key, $value);
		}

		do_action('wnd_update_profile', $user_id);

		// 返回值过滤
		return apply_filters('wnd_update_profile_return', array('status' => 1, 'msg' => '更新成功'), $user_id);
	}
}
