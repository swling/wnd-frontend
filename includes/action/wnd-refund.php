<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Refunder;

/**
 *@since 2019.10.02
 *订单退款
 *@param $_POST['payment_id'] 		required 	订单 ID
 *@param $_POST['refund_amount']	可选		退款金额
 */
class Wnd_Refund extends Wnd_Action_Ajax {

	public static function execute(): array{
		$form_data     = static::get_form_data();
		$payment_id    = (int) ($form_data['payment_id'] ?? 0);
		$refund_amount = (float) ($form_data['refund_amount'] ?? 0);

		try {
			$refunder = Wnd_Refunder::get_instance($payment_id);
			$refunder->set_refund_amount($refund_amount);
			$refunder->refund();
		} catch (Exception $e) {
			return [
				'status' => 0,
				'msg'    => $e->getMessage(),

				// 判断是否完成实例化
				'data'   => isset($refunder) ? $refunder->get_response() : [],
			];
		}

		return [
			'status' => 1,
			'msg'    => __('退款成功', 'wnd'),

			// 判断是否完成实例化
			'data'   => isset($refunder) ? $refunder->get_response() : [],
		];
	}
}
