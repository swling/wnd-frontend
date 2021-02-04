<?php
namespace Wnd\Module;

use Exception;

/**
 *@since 0.8.66
 *用户 UI 模块基类
 */
abstract class Wnd_Module_User extends Wnd_Module {

	/**
	 *权限检测
	 *@since 0.8.66
	 */
	protected static function check($args) {
		if (!is_user_logged_in()) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}
}
