<?php
namespace Wnd\Module\Admin;

use Exception;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 2020.05.06 封装前端用户列表表格
 *
 * @param $number 每页列表数目
 */
class Wnd_Users extends Wnd_Module_Vue {

	protected static function parse_data(array $args): array {
		$orderby_args = [
			'label'   => __('排序', 'wnd'),
			'key'     => 'orderby',
			'options' => [
				__('注册时间', 'wnd') => '',
				__('文章数量', 'wnd') => 'post_count',
				__('登录次数', 'wnd') => 'custom.login_count',
				__('最近登录', 'wnd') => 'custom.last_login',
			],
		];

		$status_args = [
			'label'   => __('状态', 'wnd'),
			'key'     => '_meta_status',
			'options' => [
				__('全部', 'wnd')  => '',
				__('已封禁', 'wnd') => 'banned',
			],
		];

		return [
			'param' => $args,
			'tabs'  => [
				$orderby_args,
				$status_args,
			],
		];
	}

	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
