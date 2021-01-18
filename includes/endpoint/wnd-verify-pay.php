<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Model\Wnd_Payment;
use Wnd\Model\Wnd_Payment_Getway;

/**
 *支付校验
 *@since 2020.06.19
 *
 *注意事项：在异步支付通知中，不得输出任何支付平台规定之外的字符或HTML代码。
 *			故此，调用本类时，相关异常应使用 exit 中止并输出
 */
class Wnd_Verify_Pay extends Wnd_Endpoint {
	/**
	 *响应类型
	 */
	protected $content_type = 'txt';

	/**
	 *响应操作
	 */
	protected function do() {
		/**
		 *根据交易订单解析站内交易ID，并查询记录
		 */
		$out_trade_no    = $_REQUEST['out_trade_no'] ?? '';
		$total_amount    = $_REQUEST['total_amount'] ?? 0;
		$payment_id      = Wnd_Payment::parse_out_trade_no($out_trade_no);
		$payment_gateway = Wnd_Payment_Getway::get_payment_gateway($payment_id);
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
			$payment->return();
		} catch (Exception $e) {
			exit($e->getMessage());
		}
	}
}
