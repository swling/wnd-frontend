<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Payment;

/**
 *支付校验
 *@since 2020.06.19
 *
 *注意事项：在异步支付通知中，不得输出任何支付平台归档之外的字符或HTML代码
 *			故此，相关异常应使用 exit 中止并输出。不得直接抛出异常
 *
 */
class Wnd_Verify_Pay {

	public static function execute() {
		/**
		 *根据交易订单解析站内交易ID，并查询记录
		 */
		$out_trade_no    = $_REQUEST['out_trade_no'] ?? '';
		$total_amount    = $_REQUEST['total_amount'] ?? 0;
		$payment_id      = Wnd_Payment::parse_out_trade_no($out_trade_no);
		$payment_gateway = Wnd_Payment::get_payment_gateway($payment_id);
		if (!$payment_gateway) {
			exit('error');
		}

		/**
		 *验签并处理相关站内业务
		 */
		try {
			$payment = Wnd_Payment::get_instance($payment_gateway);
			$payment->set_out_trade_no($out_trade_no);
			$payment->set_total_amount($total_amount);
			$payment->verify();
		} catch (Exception $e) {
			exit($e->getMessage());
		}
	}
}
