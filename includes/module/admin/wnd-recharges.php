<?php
namespace Wnd\Module\Admin;

use Wnd\Model\Wnd_Transaction;
use Wnd\Module\Admin\Wnd_Orders;

/**
 * @since 0.9.26 充值记录
 */
class Wnd_Recharges extends Wnd_Orders {

	protected static $transaction_type = 'recharge';

	protected static function get_status_options(): array {
		return [
			'label'   => __('状态', 'wnd'),
			'key'     => 'status',
			'options' => [
				__('全部', 'wnd')  => 'any',
				__('已完成', 'wnd') => Wnd_Transaction::$completed_status,
				__('待付款', 'wnd') => Wnd_Transaction::$pending_status,
				__('已关闭', 'wnd') => Wnd_Transaction::$closed_status,
				__('已退款', 'wnd') => Wnd_Transaction::$refunded_status,
			],
		];
	}

}
