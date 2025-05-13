<?php
namespace Wnd\Module\Admin;

use Exception;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 0.9.26 财务统计中心
 */
class Wnd_Finance_Stats extends Wnd_Module_Vue {

	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}

}
