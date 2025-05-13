<?php
namespace Wnd\Module\Admin;

use Exception;
use Wnd\Model\Wnd_Transaction;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 0.9.26 订单及充值记录
 */
class Wnd_Finance_List extends Wnd_Module_Vue {

	protected static function parse_data(array $args): array {
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
					__('待发货', 'wnd') => Wnd_Transaction::$processing_status,
					__('已关闭', 'wnd') => Wnd_Transaction::$closed_status,
					__('已退款', 'wnd') => Wnd_Transaction::$refunded_status,
				],
			],

		];
		return ['param' => ['user_id' => 'any'], 'tabs' => $tabs];
	}

	protected static function get_file_path(): string {
		return '/includes/module-vue/user/finance-list.vue';
	}

	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
