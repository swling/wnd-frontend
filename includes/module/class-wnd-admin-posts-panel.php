<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Filter;

/**
 *@since 2019.02.19 封装前端管理员内容审核平台
 *@param $posts_per_page 每页列表数目
 */
class wnd_admin_posts_panel extends Wnd_Module {

	public static function build(int $posts_per_page = 0) {
		if (!is_user_logged_in()) {
			return '<div class="message is-warning"><div class="message-body">请登录！</div></div>';
		}
		$posts_per_page = $posts_per_page ?: get_option('posts_per_page');

		$filter = new Wnd_Filter(true);
		$filter->add_post_type_filter(get_post_types(array('public' => true)), true);
		$filter->add_post_status_filter(array('待审' => 'pending'));
		$filter->set_posts_template('wnd_posts_tpl');
		$filter->set_posts_per_page($posts_per_page);
		$filter->set_ajax_container('#admin-posts-panel');
		$filter->query();
		return $filter->get_tabs() . '<div id="admin-posts-panel">' . $filter->get_results() . '</div>';
	}
}
