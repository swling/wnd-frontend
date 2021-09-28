<?php
namespace Wnd\Action;

use Exception;
use WP_REST_Request;

/**
 * 超级管理员 Ajax 操作基类
 * @since 0.8.66
 */
abstract class Wnd_Action_Root extends Wnd_Action {

	final public function __construct(WP_REST_Request $wp_rest_request) {
		if (!is_super_admin()) {
			throw new Exception(__('权限不足', 'wnd'));
		}

		parent::__construct($wp_rest_request);
	}
}
