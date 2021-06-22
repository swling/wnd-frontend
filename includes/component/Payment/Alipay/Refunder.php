<?php
namespace Wnd\Component\Payment\Alipay;

use Wnd\Component\Payment\Alipay\AlipayService;
use Wnd\Component\Payment\RefunderBuilder;
use Wnd\Component\Requests\Requests;

/**
 * 支付宝退款
 * @link https://opendocs.alipay.com/apis/api_1/alipay.trade.refund
 */
class Refunder implements RefunderBuilder {

	protected $gateway_url;

	protected $charset;

	protected $method = 'alipay.trade.refund';

	// 退款金额
	protected $refund_amount;

	// 订单号
	protected $out_trade_no;

	// 标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。
	protected $out_request_no;

	// 支付宝基本配置参数
	protected $alipayConfig;

	/**
	 * @since 0.9.17
	 */
	public function __construct(array $alipayConfig) {
		$this->alipayConfig = $alipayConfig;
		$this->charset      = $alipayConfig['charset'];
		$this->gateway_url  = $alipayConfig['gateway_url'];
	}

	/**
	 * 退款金额
	 */
	public function setRefundAmount(float $refund_amount) {
		$this->refund_amount = $refund_amount;
	}

	/**
	 * 交易订单号
	 */
	public function setOutTradeNo(string $out_trade_no) {
		$this->out_trade_no = $out_trade_no;
	}

	/**
	 * 部分退款：退款请求号
	 */
	public function setOutRequestNo(string $out_request_no) {
		$this->out_request_no = $out_request_no;
	}

	/**
	 * 发起订单
	 * @return array
	 */
	public function doRefund(): array{
		//请求参数
		$biz_content = [
			'out_trade_no'   => $this->out_trade_no,
			'refund_amount'  => $this->refund_amount,
			'out_request_no' => $this->out_request_no,
		];

		//公共参数
		$alipayService  = new AlipayService($this->alipayConfig);
		$common_configs = $alipayService->generateRefundParams($this->method, $biz_content);

		// 发起请求
		$request  = new Requests;
		$response = $request->request($this->gateway_url,
			[
				'method'  => 'POST',
				'timeout' => 60,

				// 支付宝的请求中 header 及 body 必须按此设置
				'body'    => http_build_query($common_configs),
				'headers' => ['Content-type' => "application/x-www-form-urlencoded; charset=$this->charset"],
			]
		);

		return json_decode($response['body'], true);
	}
}
