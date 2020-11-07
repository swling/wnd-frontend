<?php
namespace Wnd\Action;

use Wnd\Model\Wnd_Refunder;

/**
 *@since 2019.10.02
 *订单退款
 *@param $_POST['payment_id'] 		required 	订单 ID
 *@param $_POST['refund_amount']	可选		退款金额
 */
class Wnd_Refund extends Wnd_Action_Ajax_Admin {

	public function execute(): array{
		$payment_id    = (int) ($this->data['payment_id'] ?? 0);
		$refund_amount = (float) ($this->data['refund_amount'] ?? 0);

		$refunder = Wnd_Refunder::get_instance($payment_id);
		$refunder->set_refund_amount($refund_amount);
		$refunder->refund();

		return [
			'status' => 1,
			'msg'    => __('退款成功', 'wnd'),
			'data'   => $refunder->get_response(),
		];
	}
}
