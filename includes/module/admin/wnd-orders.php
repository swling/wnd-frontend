<?php

namespace Wnd\Module\Admin;

use Exception;
use Wnd\Model\Wnd_Transaction;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 0.9.26 订单记录
 */
class Wnd_Orders extends Wnd_Module_Vue {

	protected static $transaction_type = 'order';

	protected static function parse_data(array $args): array {
		$args['type'] = static::$transaction_type;
		$tabs         = [static::get_status_options()];
		return ['param' => $args, 'tabs' => $tabs];
	}

	protected static function get_status_options(): array {
		return [
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
		];
	}

	protected static function get_file_path(): string {
		return '/includes/module-vue/user/finance.vue';
	}

	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
