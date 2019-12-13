<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Filter;

/**
 *@since 2019.02.18 封装用户财务中心
 *@param $posts_per_page 每页列表数目
 */
class Wnd_User_Finance_Panel extends Wnd_Module {

	public static function build(int $posts_per_page = 0) {
		if (!is_user_logged_in()) {
			return;
		}
		$user_id        = get_current_user_id();
		$posts_per_page = $posts_per_page ?: get_option('posts_per_page');

		$html = '<div id="user-fin">';
		$html .= '<nav class="level">';
		$html .= '
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">余额</p>
				<p class="title">' . wnd_get_user_money($user_id) . '</p>
			</div>
		</div>';

		$html .= '
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">消费</p>
				<p class="title">' . wnd_get_user_expense($user_id) . '</p>
			</div>
		</div>';

		if (wnd_get_option('wnd', 'wnd_commission_rate')) {
			$html .= '
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">佣金</p>
				<p class="title">' . wnd_get_user_commission($user_id) . '</p>
			</div>
		</div>';
		}
		$html .= '</nav>';

		$html .= '<div class="level">';
		$html .= '
		<div class="level-item">
			<button class="button" onclick="wnd_ajax_modal(\'wnd_user_recharge_form\')">余额充值</button>
		</div>';

		if (is_super_admin()) {
			$html .= '
		<div class="level-item">
			<button class="button" onclick="wnd_ajax_modal(\'wnd_admin_recharge_form\')">管理员充值</button>
		</div>';
		}
		$html .= '</div>';

		$filter = new Wnd_Filter(true);
		$filter->add_post_type_filter(['order', 'recharge']);
		$filter->add_post_status_filter(['any']);
		$filter->add_query(['author' => get_current_user_id()]);
		$filter->set_posts_template('wnd_user_finance_posts_tpl');
		$filter->set_posts_per_page($posts_per_page);
		$filter->set_ajax_container('#admin-fin-panel');
		$filter->query();
		$filter_html = $filter->get_tabs() . '<div id="admin-fin-panel">' . $filter->get_results() . '</div>';

		return $html . $filter_html;
	}
}
