<?php
namespace Wnd\Action\Admin;

use Wnd\Action\Wnd_Action_Admin;
use Wnd\Getway\Wnd_Refunder;

/**
 * 订单退款
 * @since 2019.10.02
 */
class Wnd_Refund extends Wnd_Action_Admin {

	private $transaction_id;
	private $refund_amount;

	protected function execute(): array{
		$refunder = Wnd_Refunder::get_instance($this->transaction_id);
		$refunder->set_refund_amount($this->refund_amount);
		$refunder->refund();

		return [
			'status' => 1,
			'msg'    => __('退款成功', 'wnd'),
			'data'   => $refunder->get_response(),
		];
	}

	protected function parse_data() {
		$this->transaction_id = (int) ($this->data['transaction_id'] ?? 0);
		$this->refund_amount  = (float) ($this->data['refund_amount'] ?? 0);
	}
}
