<?php
namespace Wnd\Module\User;

use Exception;
use Wnd\Model\Wnd_Transaction;
use Wnd\Model\Wnd_Transaction_Anonymous;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 2019.02.18 封装用户财务中心
 * 仅管理员可传参 user id 查询他人信息 @see static::get_user_id($args)
 */
class Wnd_Orders extends Wnd_Module_Vue {

	protected static function parse_data(array $args = []): array {
		$user_id         = static::get_user_id($args);
		$args['user_id'] = $user_id;
		$args['type']    = $args['type'] ?? 'order';
		$html            = static::build_panel($user_id);

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
					__('待付款', 'wnd') => Wnd_Transaction::$pending_status,
					__('待发货', 'wnd') => Wnd_Transaction::$paid_status,
					__('已发货', 'wnd') => Wnd_Transaction::$shipped_status,
					__('已完成', 'wnd') => Wnd_Transaction::$completed_status,
					__('已关闭', 'wnd') => Wnd_Transaction::$closed_status,
					__('已退款', 'wnd') => Wnd_Transaction::$refunded_status,
				],
			],

		];
		return ['param' => $args, 'tabs' => $tabs, 'panel' => $html];
	}

	private static function build_panel($user_id): string {
		// 匿名订单
		if (!$user_id) {
			$html = '<div class="notification is-danger is-light">';
			$html .= '<i class="fas fa-exclamation-circle"></i> ';
			$html .= __('您当前尚未登录，请妥善保存订单信息！', 'wnd');
			$html .= '</div>';
			return $html;
		}

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
		$html .= '<div class="level-item">' . wnd_modal_button(__('余额充值', 'wnd'), 'common/wnd_recharge_form') . '</div>';

		if (is_super_admin()) {
			$html .= '<div class="level-item">' . wnd_modal_button(__('人工充值', 'wnd'), 'admin/wnd_admin_recharge_form') . '</div>';
		}
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	private static function get_user_id(array $args): int {
		$user_id = get_current_user_id();
		return wnd_is_manager() ? ($args['user_id'] ?? $user_id) : $user_id;
	}

	protected static function check($args) {
		if (!is_user_logged_in() and !Wnd_Transaction_Anonymous::get_anon_cookies()) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}

}
