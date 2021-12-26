<?php
namespace Wnd\Action\User;

use Exception;
use Wnd\Action\Wnd_Action_User;

/**
 * 用户资料修改
 * - 修改昵称，简介，字段等
 * - 修改账户密码请使用：wnd_wpdate_account
 *
 * @since 初始化
 */
class Wnd_Update_Profile extends Wnd_Action_User {

	private $user_data;
	private $user_meta_data;
	private $wp_user_meta_data;

	protected function execute(): array{
		// 更新meta
		if ($this->user_meta_data) {
			wnd_update_user_meta_array($this->user_id, $this->user_meta_data);
		}

		if ($this->wp_user_meta_data) {
			foreach ($this->wp_user_meta_data as $key => $value) {
				update_user_meta($this->user_id, $key, $value);
			}
			unset($key, $value);
		}

		// 更新用户
		$action = wp_update_user($this->user_data);
		if (is_wp_error($action)) {
			throw new Exception($action->get_error_message());
		}

		// 返回值过滤
		return apply_filters('wnd_update_profile_return', ['status' => 1, 'msg' => __('更新成功', 'wnd')], $this->user_id);
	}

	protected function check() {
		$this->user_data         = $this->request->get_user_data();
		$this->user_data['ID']   = $this->user_id;
		$this->user_meta_data    = $this->request->get_user_meta_data();
		$this->wp_user_meta_data = $this->request->get_wp_user_meta_data();

		// 更新权限过滤挂钩
		$can = apply_filters('wnd_can_update_profile', ['status' => 1, 'msg' => ''], $this->data);
		if (0 === $can['status']) {
			throw new Exception($can['msg']);
		}
	}
}
