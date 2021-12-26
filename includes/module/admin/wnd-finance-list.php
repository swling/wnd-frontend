<?php
namespace Wnd\Module\Admin;

use Exception;
use Wnd\Model\Wnd_Transaction;
use Wnd\Module\Wnd_Module_Filter;
use Wnd\View\Wnd_Filter_Ajax;

/**
 * @since 0.9.26 订单及充值记录
 */
class Wnd_Finance_List extends Wnd_Module_Filter {

	protected function structure(): array{
		$this->args['posts_per_page'] = $this->args['posts_per_page'] ?? get_option('posts_per_page');

		$filter = new Wnd_Filter_Ajax();
		$filter->add_search_form();
		$filter->add_post_type_filter(['order', 'recharge']);
		$filter->add_post_status_filter(
			[
				__('全部', 'wnd')  => 'any',
				__('已完成', 'wnd') => Wnd_Transaction::$completed_status,
				__('进行中', 'wnd') => Wnd_Transaction::$processing_status,
				__('已关闭', 'wnd') => Wnd_Transaction::$closed_status,
				__('已退款', 'wnd') => Wnd_Transaction::$refunded_status,
			]
		);
		$filter->set_posts_per_page($this->args['posts_per_page']);
		$filter->query();
		return $filter->get_filter();
	}

	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
