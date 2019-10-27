<?php
namespace Wnd\Controller;

use Exception;
use Wnd\Controller\Wnd_Form_Handler;

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
class Wnd_Update_Profile extends Wnd_Controller_Ajax {

	public static function execute(): array{
		if (empty($_POST)) {
			return array('status' => 0, 'msg' => '获取用户数据失败！');
		}

		// 实例化WndWP表单数据处理对象
		try {
			$form_data = new Wnd_Form_Handler();
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}
		$user_array         = $form_data->get_user_array();
		$user_meta_array    = $form_data->get_user_meta_array();
		$wp_user_meta_array = $form_data->get_wp_user_meta_array();

		// ################### 组成用户数据
		$user    = wp_get_current_user();
		$user_id = $user->ID;
		if (!$user_id) {
			return array('status' => 0, 'msg' => '获取用户ID失败！');
		}
		$user_array = array('ID' => $user_id, 'display_name' => $user_array['display_name'], 'user_url' => $user_array['user_url']);

		// 更新权限过滤挂钩
		$user_can_update_profile = apply_filters('wnd_can_update_profile', array('status' => 1, 'msg' => '默认通过'));
		if ($user_can_update_profile['status'] === 0) {
			return $user_can_update_profile;
		}

		//################### 没有错误 更新用户
		$user_id = wp_update_user($user_array);
		if (is_wp_error($user_id)) {
			$msg = $user_id->get_error_message();
			return array('status' => 0, 'msg' => $msg);
		}

		//################### 用户更新成功，写入meta 及profile数据
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
		$return_array = apply_filters('wnd_update_profile_return', array('status' => 1, 'msg' => '更新成功！'), $user_id);
		return $return_array;

	}
}
