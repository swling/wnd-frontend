<?php
namespace Wnd\Action;

use Exception;

/**
 * 注册用户 Ajax 操作基类
 * @since 0.8.66
 */
abstract class Wnd_Action_User extends Wnd_Action {

	/**
	 * 权限检测
	 * @since 0.8.66
	 */
	protected function check() {
		if (!$this->user_id) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}
}
