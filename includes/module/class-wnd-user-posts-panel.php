<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Filter;

/**
 *@since 2019.02.19 封装前端当前用户内容管理面板
 *@param $posts_per_page 每页列表数目
 */
class Wnd_User_Posts_Panel extends Wnd_Module {

	public static function build(int $posts_per_page = 0) {
		if (!is_user_logged_in()) {
			return self::build_error_message('请登录');
		}
		$posts_per_page = $posts_per_page ?: get_option('posts_per_page');

		$filter = new Wnd_Filter(true);
		$filter->add_post_type_filter(wnd_get_user_panel_post_types());
		$filter->add_post_status_filter(['全部' => 'any', '发布' => 'publish', '待审' => 'pending', '关闭' => 'close', '草稿' => 'draft']);
		$filter->add_taxonomy_filter(['taxonomy' => $filter->category_taxonomy]);
		$filter->add_query(['author' => get_current_user_id()]);
		$filter->set_posts_template('\Wnd\Module\Wnd_List::posts_table_tpl');
		$filter->set_posts_per_page($posts_per_page);
		$filter->set_ajax_container('#user-posts-panel');
		$filter->query();
		return $filter->get_tabs() . '<div id="user-posts-panel">' . $filter->get_results() . '</div>';
	}
}
