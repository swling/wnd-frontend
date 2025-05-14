<?php
namespace Wnd\Module\User;

use Exception;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 0.9.73
 * 站内信箱
 */
class Wnd_Mail_Box extends Wnd_Module_Vue {

	protected static function parse_data(array $args = []): array {
		$tabs = [
			[
				'label'   => '',
				'key'     => 'status',
				'options' => [
					__('全部', 'wnd') => 'any',
					__('未读', 'wnd') => 'unread',
					__('已读', 'wnd') => 'read',
				],
			],

		];
		return ['param' => $args, 'tabs' => $tabs];
	}

	protected static function check($args) {
		if (!is_user_logged_in()) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}
}
