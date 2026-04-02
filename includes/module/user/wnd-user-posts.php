<?php
namespace Wnd\Module\User;

use Exception;
use Wnd\Module\Wnd_Module_Vue;

/**
 * 用户文章列表模块
 *
 */
class Wnd_User_Posts extends Wnd_Module_Vue {

	protected static function parse_data(array $args): array {
		$orderby_args = [
			'label'   => __('排序', 'wnd'),
			'key'     => 'orderby',
			'options' => [
				__('时间', 'wnd') => '',
				__('浏览', 'wnd') => 'total_views',
			],
		];

		// 动态获取所有可用 post status
		$statuses       = get_post_stati(['show_in_admin_all_list' => true], 'objects');
		$status_options = [__('全部', 'wnd') => 'any'];
		foreach ($statuses as $status) {
			$status_options[$status->label] = $status->name;
		}
		$status_args = [
			'label'   => __('状态', 'wnd'),
			'key'     => 'status',
			'options' => $status_options,
		];

		// 动态获取所有可用 post type（排除 attachment 类型）
		$post_types        = get_post_types(['public' => true], 'objects');
		$post_type_options = [__('全部', 'wnd') => ''];
		foreach ($post_types as $type) {
			$post_type_options[$type->label] = $type->name;
		}
		$post_type_args = [
			'label'   => __('类型', 'wnd'),
			'key'     => 'post_type',
			'options' => $post_type_options,
		];

		return [
			'param' => $args,
			'tabs'  => [
				$orderby_args,
				$status_args,
				$post_type_args,
			],
		];
	}

	protected static function check($args) {
		if (!is_user_logged_in()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}

	protected static function get_file_path(): string {
		return '/includes/module-vue/common/posts.vue';
	}
}
