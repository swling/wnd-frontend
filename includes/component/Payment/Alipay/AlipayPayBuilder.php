<?php
namespace Wnd\Component\Payment\Alipay;

use Wnd\Component\Payment\Alipay\AlipayConfig;
use Wnd\Component\Payment\Alipay\AlipayService;
use Wnd\Component\Payment\PaymentBuilder;

/**
 *@since 2020.07.16
 *
 *支付宝支付创建基类
 */
abstract class AlipayPayBuilder implements PaymentBuilder {
	// 支付宝接口
	protected $gateway_url;
	protected $charset;
	protected $product_code;
	protected $method;

	// 订单基本参数
	protected $total_amount;
	protected $out_trade_no;
	protected $subject;

	// 公共请求参数
	protected $common_configs;

	/**
	 *@since 0.9.17
	 */
	public function __construct() {
		$AlipayConfig      = AlipayConfig::getConfig();
		$this->charset     = $AlipayConfig['charset'];
		$this->gateway_url = $AlipayConfig['gateway_url'];
	}

	/**
	 *总金额
	 */
	public function setTotalAmount(float $total_amount) {
		$this->total_amount = $total_amount;
	}

	/**
	 *交易订单号
	 */
	public function setOutTradeNo(string $out_trade_no) {
		$this->out_trade_no = $out_trade_no;
	}

	/**
	 *订单主题
	 */
	public function setSubject(string $subject) {
		$this->subject = $subject;
	}

	/**
	 * 构建支付参数，并发起订单
	 * 订单请求通常为表单提交并跳转至支付宝，也可能是Ajax提交获取响应，因此需要返回 doPay()的值
	 *
	 *@return string 返回构造支付请求的Html，如自动提交的表单或支付二维码
	 */
	public function build(): string{
		$this->generateCommonConfigs();
		return $this->buildInterface();
	}

	/**
	 *发起客户端支付请求
	 *
	 *@return string 返回构造支付请求的Html，如自动提交的表单或支付二维码
	 */
	abstract protected function buildInterface(): string;

	/**
	 * 签名并构造完整的请求参数
	 * @return string
	 */
	protected function generateCommonConfigs() {
		//请求参数
		$biz_content = [
			'out_trade_no' => $this->out_trade_no,
			'product_code' => $this->product_code,
			'total_amount' => $this->total_amount, //单位 元
			'subject'      => $this->subject, //订单标题
		];

		$alipayService        = new AlipayService;
		$this->common_configs = $alipayService->generatePaymentConfigs($this->method, $biz_content);
	}
}
