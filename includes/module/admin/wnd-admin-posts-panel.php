<?php
namespace Wnd\Module;

use Exception;
use Wnd\View\Wnd_Filter_Ajax;

/**
 * @since 2019.02.19 封装前端管理员内容审核平台
 */
class Wnd_Admin_Posts_Panel extends Wnd_Module_Filter {

	protected function structure(): array{
		$this->args['posts_per_page'] = $this->args['posts_per_page'] ?? get_option('posts_per_page');

		$filter = new Wnd_Filter_Ajax();
		$filter->remove_post_content();
		$filter->add_search_form();
		$filter->add_post_type_filter(wnd_get_user_panel_post_types(), true);
		$filter->add_post_status_filter([__('待审', 'wnd') => 'pending']);
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
