<?php
namespace Wnd\Component\Payment;

/**
 * 站外退款接口
 * @since 2020.08.17
 */
interface RefunderBuilder {
	/**
	 * 退款金额
	 */
	public function setRefundAmount(float $refundAmount);

	/**
	 * 交易订单号
	 */
	public function setOutTradeNo(string $outTradeNo);

	/**
	 * 部分退款：退款请求号
	 */
	public function setOutRequestNo(string $outRequestNo);

	/**
	 * 发起退款并获取响应
	 * @return array
	 */
	public function doRefund(): array;
}
