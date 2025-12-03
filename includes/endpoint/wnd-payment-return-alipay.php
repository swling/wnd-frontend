<?php
namespace Wnd\Endpoint;

/**
 * 支付宝同步回调（同步回调不包含支付状态，即使验签通过也不得作为支付验证结果，不得更新订单状态）
 * @link https://opendocs.alipay.com/support/01raw3?pathHash=15693d90
 * @since 0.9.91
 */
class Wnd_Payment_Return_Alipay extends Wnd_Payment_Return {

	/**
	 * 根据交易订单解析站内交易ID
	 */
	public function parse_transaction_id(): string {
		return $_REQUEST['out_trade_no'] ?? '';
	}
}
