<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Filter_Ajax;

/**
 *@since 0.9.26 财务统计中心
 */
class Wnd_Finance_Stats extends Wnd_Module_Filter {

	protected function structure(): array{
		$this->args['posts_per_page'] = $this->args['posts_per_page'] ?? get_option('posts_per_page');

		$filter = new Wnd_Filter_Ajax(wnd_doing_ajax());
		$filter->add_search_form();
		$filter->add_post_type_filter(['stats-ex', 'stats-re']);
		$filter->set_posts_per_page($this->args['posts_per_page']);
		$filter->query();
		return $filter->get_filter();
	}
}
