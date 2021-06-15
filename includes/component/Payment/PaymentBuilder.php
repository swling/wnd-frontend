<?php
namespace Wnd\Component\Payment;

/**
 * @since 站外支付接口
 */
interface PaymentBuilder {

	/**
	 * 总金额
	 */
	public function setTotalAmount(float $totalAmount);

	/**
	 * 交易订单号
	 */
	public function setOutTradeNo(string $outTradeNO);

	/**
	 * 订单主题
	 */
	public function setSubject(string $subject);

	/**
	 * 构建签名并创建请求参数
	 *
	 */
	public function generateParams(): array;

	/**
	 * 构造支付请求 UI 接口，如自动提交的表单或支付二维码
	 * @return string
	 */
	public function buildInterface(): string;
}
