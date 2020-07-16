<?php

namespace Wnd\Component\Alipay;

use Exception;
use Wnd\Component\Alipay\AlipayService;

/**
 *@since 2020.07.16 支付宝当面付支创建类
 *
 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.precreate
 */
class AlipayQRCodePay extends AlipayService {

	protected $product_code = 'FACE_TO_FACE_PAYMENT';
	protected $method       = 'alipay.trade.precreate';

	protected $total_amount;
	protected $out_trade_no;
	protected $subject;

	/**
	 *总金额
	 */
	public function set_total_amount(float $total_amount) {
		$this->total_amount = $total_amount;
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
	public function set_subject($subject) {
		$this->subject = $subject;
	}

	/**
	 * 发起订单
	 * @return array
	 */
	public function doPay() {
		//请求参数
		$request_configs = [
			'out_trade_no' => $this->out_trade_no,
			'product_code' => $this->product_code,
			'total_amount' => $this->total_amount, //单位 元
			'subject'      => $this->subject, //订单标题
		];

		//公共参数
		$common_configs = [
			'app_id'      => $this->app_id,
			'method'      => $this->method, //接口名称
			'format'      => 'JSON',
			'return_url'  => $this->return_url,
			'charset'     => $this->charset,
			'sign_type'   => $this->sign_type,
			'timestamp'   => date('Y-m-d H:i:s'),
			'version'     => '1.0',
			'notify_url'  => $this->notify_url,
			'biz_content' => json_encode($request_configs),
		];
		$common_configs["sign"] = $this->generateSign($common_configs, $common_configs['sign_type']);

		/**
		 *采用WordPress内置函数发送Post请求
		 */
		$response = wp_remote_post($this->gateway_url,
			[
				'timeout'     => 60,
				'redirection' => 5,
				'httpversion' => '2.0',
				'body'        => $common_configs,

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
