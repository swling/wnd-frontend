<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Payment;

/**
 *Ajax创建支付
 *@since 2020.06.19
 *
 */
class Wnd_Do_Pay extends Wnd_Action_Ajax {

	public static function execute(): array{
		$post_id         = (int) ($_POST['post_id'] ?? 0);
		$total_amount    = (float) ($_POST['total_amount'] ?? 0);
		$payment_gateway = $_POST['payment_gateway'] ?? '';

		if (!$payment_gateway) {
			throw new Exception(__('未定义支付方式', 'wnd'));
		}

		try {
			$payment = Wnd_Payment::get_instance($payment_gateway);
			$payment->set_object_id($post_id);
			$payment->set_total_amount($total_amount);

			// Ajax 提交时，需将提交响应返回，并替换用户UI界面，故需设置 ['status' => 7];
			$response = $payment->pay();
			return ['status' => 7, 'data' => '<div class="has-text-centered">' . $response . '</div>'];
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
}
