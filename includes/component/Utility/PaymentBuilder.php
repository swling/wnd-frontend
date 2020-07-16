<?php
namespace Wnd\Component\Utility;

/**
 *@since 站外支付接口
 */
interface PaymentBuilder {

	/**
	 *总金额
	 */
	public function set_total_amount($total_amount);

	/**
	 *交易订单号
	 */
	public function set_out_trade_no($out_trade_no);

	/**
	 *订单主题
	 */
	public function set_subject($subject);

	/**
	 * 发起订单
	 * @return string
	 */
	public function Pay();
}
