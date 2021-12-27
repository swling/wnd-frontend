<?php
namespace Wnd\Module\User;

use Wnd\Module\Wnd_Module_Filter;
use Wnd\View\Wnd_Filter_Ajax;

/**
 * @since 2019.02.18 封装用户财务中心
 */
class Wnd_User_Finance_Panel extends Wnd_Module_Filter {

	protected function structure(): array{
		$user_id        = get_current_user_id();
		$posts_per_page = $this->args['posts_per_page'] ?? get_option('posts_per_page');

		$html = '<div id="user-finance-panel">';
		$html .= '<nav class="level is-mobile">';
		$html .= '
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">' . __('余额', 'wnd') . '</p>
				<p class="title">' . wnd_get_user_balance($user_id, true) . '</p>
			</div>
		</div>';

		$html .= '
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">' . __('消费', 'wnd') . '</p>
				<p class="title">' . wnd_get_user_expense($user_id, true) . '</p>
			</div>
		</div>';

		if (wnd_get_config('commission_rate')) {
			$html .= '
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">' . __('佣金', 'wnd') . '</p>
				<p class="title">' . wnd_get_user_commission($user_id, true) . '</p>
			</div>
		</div>';
		}
		$html .= '</nav>';

		$html .= '<div class="level is-mobile">';
		$html .= '<div class="level-item">' . wnd_modal_button(__('余额充值', 'wnd'), 'common/wnd_user_recharge_form') . '</div>';

		if (is_super_admin()) {
			$html .= '<div class="level-item">' . wnd_modal_button(__('人工充值', 'wnd'), 'admin/wnd_admin_recharge_form') . '</div>';
		}
		$html .= '</div>';

		$filter = new Wnd_Filter_Ajax();
		$filter->add_before_html($html);
		$filter->add_search_form();
		$filter->add_post_type_filter(['order', 'recharge']);
		$filter->add_post_status_filter(['any']);
		$filter->add_query_vars(['author' => get_current_user_id()]);
		$filter->set_posts_per_page($posts_per_page);
		$filter->query();
		return $filter->get_filter();
	}
}
