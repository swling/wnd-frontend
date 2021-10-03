<?php
namespace Wnd\Model;

use Exception;

/**
 * 用户绑定邮箱或手机
 * @since 2019.11.26
 */
class Wnd_Binder_Email extends Wnd_Binder {

	protected function is_change(): bool {
		return $this->user->data->user_email;
	}

	/**
	 * 核对验证码并绑定
	 */
	protected function bind_object() {
		$bind = wnd_update_user_email($this->user->ID, $this->device);
		if (!$bind) {
			throw new Exception(__('数据库写入失败', 'wnd'));
		}
	}
}
