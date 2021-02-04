<?php
namespace Wnd\Module;

use Exception;

/**
 *@since 0.8.66
 *管理员 UI 模块基类
 */
abstract class Wnd_Module_Admin extends Wnd_Module {

	/**
	 *权限检测
	 *@since 0.8.66
	 */
	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
