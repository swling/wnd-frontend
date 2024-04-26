<?php
namespace Wnd\Module\User;

use Wnd\Model\Wnd_Transaction;
use Wnd\Module\Wnd_Module_Html;

/**
 * @since 2019.02.18 封装用户财务中心
 */
class Wnd_User_Finance_Panel extends Wnd_Module_Html {

	protected static function build($args = []): string {
		$user_id = !is_super_admin() ? get_current_user_id() : ($args['user_id'] ?? get_current_user_id());

		$html = '';
		if ($user_id == get_current_user_id()) {
			$html = static::build_panel($user_id);
		}

		// 订单列表
		$tabs = [
			[
				'label'   => '订单类型',
				'key'     => 'type',
				'options' => [
					__('订单', 'wndt') => 'order',
					__('充值', 'wndt') => 'recharge',
				],
			],
			[
				'label'   => '订单状态',
				'key'     => 'status',
				'options' => [
					__('全部', 'wnd')  => 'any',
					__('已完成', 'wnd') => Wnd_Transaction::$completed_status,
					__('待付款', 'wnd') => Wnd_Transaction::$pending_status,
					__('待发货', 'wnd') => Wnd_Transaction::$processing_status,
					__('已关闭', 'wnd') => Wnd_Transaction::$closed_status,
					__('已退款', 'wnd') => Wnd_Transaction::$refunded_status,
				],
			],

		];
		$param = ['user_id' => $user_id];
		$html .= '<script>var vue_tabs = ' . json_encode($tabs, JSON_UNESCAPED_UNICODE) . '; var vue_param = ' . json_encode($param) . '</script>';

		/**
		 * 采用 vue 文件编写代码，并通过 php 读取文件文本作为字符串使用
		 * 主要目的是便于编辑，避免在 php 文件中混入大量 HTML 源码，难以维护
		 * 虽然的确基于 vue 构建，然而在这里，它并不是标准的 vue 文件，而是 HTML 文件
		 * 之所以使用 .vue 后缀是因为 .HTML 文件在文件夹中将以浏览器图标展示，非常丑陋，毫无科技感
		 * 仅此而已
		 */
		$html .= file_get_contents(WND_PATH . '/includes/module-vue/user/finance-list.vue');
		return $html;
	}

	private static function build_panel(int $user_id): string {
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
		$html .= '</div>';

		return $html;
	}

}
