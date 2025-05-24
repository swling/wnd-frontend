<?php
namespace Wnd\Module\Admin;

use Exception;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 0.9.89 系统信息面板
 */
class Wnd_System_Monitor extends Wnd_Module_Vue {

	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}

}
