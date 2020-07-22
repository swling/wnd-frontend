<?php
namespace Wnd\Component\Utility;

/**
 *@since 站外支付接口
 */
interface PaymentBuilder {

	/**
	 *总金额
	 */
	public function setTotalAmount(float $total_amount);

	/**
	 *交易订单号
	 */
	public function setOutTradeNo(string $out_trade_no);

	/**
	 *订单主题
	 */
	public function setSubject(string $subject);

	/**
	 *构造支付请求接口，如自动提交的表单或支付二维码
	 *@return string
	 */
	public function build(): string;
}
