<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Filter;

/**
 *@since 2019.03.14 财务统计中心
 *@param $posts_per_page 每页列表数目
 */
class Wnd_Admin_Finance_Panel extends Wnd_Module {

	public static function build(int $posts_per_page = 0) {
		if (!is_super_admin()) {
			return;
		}
		$posts_per_page = $posts_per_page ?: get_option('posts_per_page');

		$filter = new Wnd_Filter(true);
		$filter->add_post_type_filter(['stats-ex', 'stats-re', 'order', 'recharge']);
		$filter->add_post_status_filter(['全部' => 'any', '已完成' => 'success', '进行中' => 'pending']);
		$filter->set_posts_template('wnd_list_table');
		$filter->set_posts_per_page($posts_per_page);
		$filter->set_ajax_container('#admin-fin-panel');
		$filter->query();
		return $filter->get_tabs() . '<div id="admin-fin-panel">' . $filter->get_results() . '</div>';
	}
}
