<?php
namespace Wnd\Component\Alipay;

use Exception;
use Wnd\Component\Alipay\AlipayService;

/**
 *支付宝退款
 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.refund
 */
class AlipayRefunder extends AlipayService {

	protected $method = 'alipay.trade.refund';
	// protected $product_code;

	// 退款金额
	protected $refund_amount;

	// 订单号
	protected $out_trade_no;

	// 标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。
	protected $out_request_no;

	/**
	 *退款金额
	 */
	public function set_refund_amount($refund_amount) {
		$this->refund_amount = $refund_amount;
	}

	/**
	 *交易订单号
	 */
	public function set_out_trade_no($out_trade_no) {
		$this->out_trade_no = $out_trade_no;
	}

	/**
	 *订单主题
	 */
	public function set_out_request_no($out_request_no) {
		$this->out_request_no = $out_request_no;
	}

	/**
	 * 发起订单
	 * @return array
	 */
	public function do_refund(): array{
		//请求参数
		$requestConfigs = [
			'out_trade_no'   => $this->out_trade_no,
			'refund_amount'  => $this->refund_amount,
			'out_request_no' => $this->out_request_no,
		];

		//公共参数
		$commonConfigs = [
			'app_id'      => $this->app_id,
			'method'      => $this->method, //接口名称
			'format'      => 'JSON',
			'charset'     => $this->charset,
			'sign_type'   => $this->sign_type,
			'timestamp'   => date('Y-m-d H:i:s'),
			'version'     => '1.0',
			'biz_content' => json_encode($requestConfigs),
		];
		$commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);

		/**
		 *采用WordPress内置函数发送Post请求
		 */
		$response = wp_remote_post($this->gateway_url,
			[
				'timeout'     => 60,
				'redirection' => 5,
				'httpversion' => '2.0',
				'body'        => $commonConfigs,

				// 必须设置此项，否则无法解析支付宝的响应json
				'headers'     => array("Content-type" => "application/x-www-form-urlencoded;charset=UTF-8"),
			]
		);

		if (is_wp_error($response)) {
			throw new Exception($response->get_error_message());
		} else {
			return json_decode($response['body'], true);
		}
	}
}
