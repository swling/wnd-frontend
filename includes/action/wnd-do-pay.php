<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Payment;

/**
 *创建支付
 *@since 2020.06.19
 *
 *为适应不同支付接口方式，本 Action 同时接收 GET 类提交或 POST 提交
 */
class Wnd_Do_Pay extends Wnd_Action {

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

			// 此处可能为跳转提交，或 Ajax 提交，Ajax 提交时，需将提交响应返回
			return $payment->pay();
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
}
