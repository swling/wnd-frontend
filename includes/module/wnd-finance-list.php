<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Filter_Ajax;

/**
 *@since 0.9.26 订单及充值记录
 */
class Wnd_Finance_List extends Wnd_Module_Filter {

	protected function structure(): array{
		$this->args['posts_per_page'] = $this->args['posts_per_page'] ?? get_option('posts_per_page');

		$filter = new Wnd_Filter_Ajax(wnd_doing_ajax());
		$filter->add_search_form();
		$filter->add_post_type_filter(['order', 'recharge']);
		$filter->add_post_status_filter([__('全部', 'wnd') => 'any', __('已完成', 'wnd') => 'wnd-completed', __('进行中', 'wnd') => 'wnd-processing']);
		$filter->set_posts_per_page($this->args['posts_per_page']);
		$filter->query();
		return $filter->get_filter();
	}
}
