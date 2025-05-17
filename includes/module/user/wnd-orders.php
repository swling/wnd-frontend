<?php
namespace Wnd\Module\User;

use Exception;
use Wnd\Model\Wnd_Transaction;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 2019.02.18 封装用户财务中心
 */
class Wnd_Orders extends Wnd_Module_Vue {

	protected static function parse_data(array $args = []): array {
		$args['user_id'] = get_current_user_id();
		$html            = '';
		$html            = static::build_panel();

		// 订单列表
		$tabs = [
			[
				'label'   => __('类型', 'wnd'),
				'key'     => 'type',
				'options' => [
					__('订单', 'wnd') => 'order',
					__('充值', 'wnd') => 'recharge',
				],
			],
			[
				'label'   => __('状态', 'wnd'),
				'key'     => 'status',
				'options' => [
					__('全部', 'wnd')  => 'any',
					__('已完成', 'wnd') => Wnd_Transaction::$completed_status,
					__('待付款', 'wnd') => Wnd_Transaction::$pending_status,
					__('待发货', 'wnd') => Wnd_Transaction::$paid_status,
					__('已发货', 'wnd') => Wnd_Transaction::$shipped_status,
					__('已关闭', 'wnd') => Wnd_Transaction::$closed_status,
					__('已退款', 'wnd') => Wnd_Transaction::$refunded_status,
				],
			],

		];
		return ['param' => $args, 'tabs' => $tabs, 'panel' => $html];
	}

	private static function build_panel(): string {
		$user_id = get_current_user_id();
		$html    = '<div id="user-finance-panel">';
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
		$html .= '<div class="level-item">' . wnd_modal_button(__('余额充值', 'wnd'), 'common/wnd_recharge_form') . '</div>';

		if (is_super_admin()) {
			$html .= '<div class="level-item">' . wnd_modal_button(__('人工充值', 'wnd'), 'admin/wnd_admin_recharge_form') . '</div>';
		}
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	protected static function check($args) {
		if (!is_user_logged_in()) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}

}
