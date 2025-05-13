<?php
namespace Wnd\Module\User;

use Exception;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 0.9.86 独立附件数据表
 */
class Wnd_Attachments extends Wnd_Module_Vue {

	protected static function parse_data(array $args): array {
		$tabs = [
			[
				'label'   => __('状态', 'wnd'),
				'key'     => 'status',
				'options' => [
					__('全部', 'wnd') => 'any',
				],
			],

		];
		return ['param' => ['user_id' => get_current_user_id()], 'tabs' => $tabs];
	}

	protected static function check($args) {
		if (!is_user_logged_in()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
