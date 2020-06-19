<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Payment;

/**
 *创建支付
 *@since 2020.06.19
 */
class Wnd_Do_Pay {

	public static function execute() {
		$post_id         = $_REQUEST['post_id'] ?? 0;
		$total_amount    = $_REQUEST['total_amount'] ?? 0;
		$payment_gateway = $_REQUEST['payment_gateway'] ?? '';

		if (!$payment_gateway) {
			throw new Exception(__('未定义支付方式', 'wnd'));
		}

		try {
			$payment = Wnd_Payment::get_instance($payment_gateway);
			$payment->set_object_id($post_id);
			$payment->set_total_amount($total_amount);
			$payment->create();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
}
