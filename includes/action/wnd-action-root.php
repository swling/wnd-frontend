<?php
namespace Wnd\Action;

use Exception;

/**
 * 超级管理员 Ajax 操作基类
 * @since 0.8.66
 */
abstract class Wnd_Action_Root extends Wnd_Action {

	/**
	 * 权限检测
	 * @since 0.8.66
	 */
	protected function check() {
		if (!is_super_admin()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
