<?php
namespace Wnd\Module;

use Exception;
use Wnd\View\Wnd_Filter_User;

/**
 * @since 2020.05.06 封装前端用户列表表格
 *
 * @param $number 每页列表数目
 */
class Wnd_Users_List extends Wnd_Module_Filter {

	protected function structure(): array{
		$orderby_args = [
			'label'   => __('排序', 'wnd'),
			'options' => [
				__('注册时间', 'wnd') => 'registered', //常规排序 date title等
				__('文章数量', 'wnd') => 'post_count', //常规排序 date title等
			],
			'order'   => 'DESC',
		];

		$status_args = [
			'label'   => __('状态', 'wnd'),
			'options' => [
				__('全部', 'wnd')  => '',
				__('已封禁', 'wnd') => 'banned',
			],
		];

		$filter = new Wnd_Filter_User();
		$filter->set_number($args['number'] ?? 20);
		// $filter->add_search_form();
		$filter->add_orderby_filter($orderby_args);
		$filter->add_status_filter($status_args);
		$filter->query();

		return $filter->get_filter();
	}

	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
