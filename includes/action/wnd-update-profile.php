<?php
namespace Wnd\Action;

use Exception;

/**
 *@since 初始化
 *用户资料修改：昵称，简介，字段等 修改账户密码请使用：wnd_wpdate_account
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
class Wnd_Update_Profile extends Wnd_Action_Ajax_User {

	public function execute(): array{
		// 实例化WndWP表单数据处理对象
		$user_data         = $this->form_data->get_user_data();
		$user_data['ID']   = $this->user_id;
		$user_meta_data    = $this->form_data->get_user_meta_data();
		$wp_user_meta_data = $this->form_data->get_wp_user_meta_data();

		// 更新权限过滤挂钩
		$user_can_update_profile = apply_filters('wnd_can_update_profile', ['status' => 1, 'msg' => '']);
		if (0 === $user_can_update_profile['status']) {
			return $user_can_update_profile;
		}

		// 更新meta
		if (!empty($user_meta_data)) {
			wnd_update_user_meta_array($this->user_id, $user_meta_data);
		}

		if (!empty($wp_user_meta_data)) {
			foreach ($wp_user_meta_data as $key => $value) {
				// 下拉菜单默认未选择时，值为 -1 。过滤
				if ('-1' != $value) {
					update_user_meta($this->user_id, $key, $value);
				}
			}
			unset($key, $value);
		}

		// 更新用户
		$action = wp_update_user($user_data);
		if (is_wp_error($action)) {
			throw new Exception($action->get_error_message());
		}

		// 返回值过滤
		return apply_filters('wnd_update_profile_return', ['status' => 1, 'msg' => __('更新成功', 'wnd')], $this->user_id);
	}
}
