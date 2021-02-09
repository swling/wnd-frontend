<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Filter_Ajax;

/**
 *@since 2019.03.14 财务统计中心
 *@param $this->args['posts_per_page'] 每页列表数目
 */
class Wnd_Admin_Finance_Panel extends Wnd_Module_Filter {

	protected function structure(): array{
		$this->args['posts_per_page'] = $this->args['posts_per_page'] ?? get_option('posts_per_page');

		$filter = new Wnd_Filter_Ajax(wnd_doing_ajax());
		$filter->add_search_form();
		$filter->add_post_type_filter(['stats-ex', 'stats-re', 'order', 'recharge']);
		$filter->add_post_status_filter([__('全部', 'wnd') => 'any', __('已完成', 'wnd') => 'wnd-completed', __('进行中', 'wnd') => 'wnd-processing']);
		$filter->set_posts_template('wnd_list_table');
		$filter->set_posts_per_page($this->args['posts_per_page']);
		$filter->query();
		return $filter->get_filter();
	}
}
