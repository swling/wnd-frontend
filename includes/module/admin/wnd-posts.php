<?php

namespace Wnd\Module\Admin;

use Exception;
use Wnd\Module\User\Wnd_User_Posts;

/**
 * @since 0.9.93
 * 从 Wnd_User_Posts 继承，复用前端用户文章列表的查询和数据转换逻辑，仅修改权限检查逻辑为管理员权限
 *
 */
class Wnd_Posts extends Wnd_User_Posts {

	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
