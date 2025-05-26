<?php
namespace Wnd\Module\Admin;

use Exception;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 0.9.86 独立附件数据表
 */
class Wnd_Attachments extends Wnd_Module_Vue {

	protected static function parse_data(array $args): array {
		$args = array_merge(['user_id' => 'any'], $args);
		$tabs = [
			[
				'label'   => __('状态', 'wnd'),
				'key'     => 'status',
				'options' => [
					__('全部', 'wnd') => 'any',
				],
			],

		];

		return ['param' => $args, 'tabs' => $tabs];
	}

	protected static function check($args) {
		if (!is_super_admin()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}

	protected static function get_file_path(): string {
		return '/includes/module-vue/user/attachments.vue';
	}
}
