<?php
namespace Wnd\Module\User;

use Wnd\Module\Wnd_Module_Filter;
use Wnd\View\Wnd_Filter_Ajax;

/**
 * @since 2019.02.19 封装前端当前用户站内信
 */
class Wnd_Mail_Box extends Wnd_Module_Filter {

	protected function structure(): array{
		$this->args['posts_per_page'] = $this->args['posts_per_page'] ?? get_option('posts_per_page');

		$filter = new Wnd_Filter_Ajax();
		$filter->add_search_form();
		$filter->add_post_type_filter(['mail']);
		$filter->add_post_status_filter([__('全部', 'wnd') => 'any', __('未读', 'wnd') => 'wnd-unread', __('已读', 'wnd') => 'wnd-read']);
		$filter->add_query_vars(['author' => get_current_user_id()]);
		$filter->set_posts_per_page($this->args['posts_per_page']);
		$filter->add_query_vars(['update_post_term_cache' => false, 'update_post_meta_cache' => false]);
		$filter->query();

		return $filter->get_filter();
	}

}
