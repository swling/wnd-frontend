<?php

namespace Wnd\Module\Admin;

use Exception;
use Wnd\Model\Wnd_Transaction;
use Wnd\Module\Wnd_Module_Vue;

/**
 * @since 0.9.87 订单处理器
 */
class Wnd_Order_Processor extends Wnd_Module_Vue {

	protected static $transaction_type = 'order';

	protected static function parse_data(array $args): array {
		$args['type']   = static::$transaction_type;
		$args['status'] = $args['status'] ?? Wnd_Transaction::$paid_status;
		return ['param' => $args, 'is_manager' => 1, 'tabs' => [static::get_status_options()]];
	}

	protected static function get_status_options(): array {
		return [
			'label'   => __('状态', 'wnd'),
			'key'     => 'status',
			'options' => [
				__('待发货', 'wnd') => Wnd_Transaction::$paid_status,
				__('已发货', 'wnd') => Wnd_Transaction::$shipped_status,
			],
		];
	}

	protected static function get_file_path(): string {
		return '/includes/module-vue/user/orders.vue';
	}

	protected static function check($args) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}
	}
}
