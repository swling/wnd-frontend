<?php
namespace Wnd\Component\Payment\Alipay;

use Exception;
use Wnd\Component\Payment\Alipay\AlipayConfig;
use Wnd\Component\Payment\Alipay\AlipayService;
use Wnd\Component\Payment\Refunder;

/**
 * 支付宝退款
 * @link https://opendocs.alipay.com/apis/api_1/alipay.trade.refund
 */
class AlipayRefunder implements Refunder {

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

		/**
		 * 采用WordPress内置函数发送Post请求
		 */
		$response = wp_remote_post($this->gateway_url,
			[
				'timeout'     => 60,
				'redirection' => 5,
				'httpversion' => '2.0',
				'body'        => $common_configs,

				// 必须设置此项，否则无法解析支付宝的响应json
				'headers'     => ['Content-type' => "application/x-www-form-urlencoded; charset=$this->charset"],
			]
		);

		if (is_wp_error($response)) {
			throw new Exception($response->get_error_message());
		} else {
			return json_decode($response['body'], true);
		}
	}
}
