<?php
namespace Wnd\Component\Payment\Alipay;

use Wnd\Component\Payment\Alipay\AlipayConfig;
use Wnd\Component\Payment\Alipay\AlipayService;
use Wnd\Component\Payment\PaymentBuilder;

/**
 * 支付宝支付创建基类
 * @since 2020.07.16
 */
abstract class PayBuilder implements PaymentBuilder {
	// 支付宝接口
	protected $gatewayUrl;
	protected $charset;
	protected $productCode;
	protected $method;

	// 订单基本参数
	protected $totalAmount;
	protected $outTradeNo;
	protected $subject;

	// 支付宝基本配置参数
	protected $alipayConfig;

	// 请求参数
	protected $params;

	/**
	 * @since 0.9.17
	 */
	public function __construct(array $alipayConfig) {
		$this->alipayConfig = $alipayConfig;
		$this->charset      = $alipayConfig['charset'];
		$this->gatewayUrl   = $alipayConfig['gateway_url'];
	}

	/**
	 * 总金额
	 */
	public function setTotalAmount(float $totalAmount) {
		$this->totalAmount = $totalAmount;
	}

	/**
	 * 交易订单号
	 */
	public function setOutTradeNo(string $outTradeNo) {
		$this->outTradeNo = $outTradeNo;
	}

	/**
	 * 订单主题
	 */
	public function setSubject(string $subject) {
		$this->subject = $subject;
	}

	/**
	 * 签名并构造完整的请求参数
	 * @return string
	 */
	public function generateParams() {
		//请求参数
		$bizContent = [
			'out_trade_no' => $this->outTradeNo,
			'product_code' => $this->productCode,
			'total_amount' => $this->totalAmount, //单位 元
			'subject'      => $this->subject, //订单标题
		];

		$alipayService = new AlipayService($this->alipayConfig);
		$this->params  = $alipayService->generatePayParams($this->method, $bizContent);
	}

	/**
	 * 发起客户端支付请求
	 *
	 * @return string 返回构造支付请求的Html，如自动提交的表单或支付二维码
	 */
	abstract public function buildInterface(): string;
}
