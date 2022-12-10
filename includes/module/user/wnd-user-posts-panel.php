<?php
namespace Wnd\Module\User;

use Wnd\Module\Wnd_Module_Filter;
use Wnd\View\Wnd_Filter_Ajax;

/**
 * @since 2019.02.19 封装前端当前用户内容管理面板
 */
class Wnd_User_Posts_Panel extends Wnd_Module_Filter {

	protected function structure(): array{
		$this->args['posts_per_page'] = $this->args['posts_per_page'] ?? get_option('posts_per_page');

		$filter = new Wnd_Filter_Ajax();
		$filter->remove_post_content();
		// $filter->add_search_form();
		$filter->add_post_type_filter(wnd_get_user_panel_post_types());
		$filter->add_post_status_filter([__('发布', 'wnd') => 'publish', __('待审', 'wnd') => 'pending', __('关闭', 'wnd') => 'wnd-closed', __('草稿', 'wnd') => 'draft']);
		$filter->add_category_filter();
		// $filter->add_tags_filter(10);
		$filter->add_query_vars(['author' => get_current_user_id(), 'update_post_term_cache' => false, 'update_post_meta_cache' => false]);
		$filter->set_posts_per_page($this->args['posts_per_page']);
		$filter->query();

		return $filter->get_filter();
	}

}
