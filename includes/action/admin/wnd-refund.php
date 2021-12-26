<?php
namespace Wnd\Action\Admin;

use Wnd\Action\Wnd_Action_Admin;
use Wnd\Getway\Wnd_Refunder;

/**
 * 订单退款
 * @since 2019.10.02
 */
class Wnd_Refund extends Wnd_Action_Admin {

	protected function execute(): array{
		$transaction_id = (int) ($this->data['transaction_id'] ?? 0);
		$refund_amount  = (float) ($this->data['refund_amount'] ?? 0);

		$refunder = Wnd_Refunder::get_instance($transaction_id);
		$refunder->set_refund_amount($refund_amount);
		$refunder->refund();

		return [
			'status' => 1,
			'msg'    => __('退款成功', 'wnd'),
			'data'   => $refunder->get_response(),
		];
	}
}
