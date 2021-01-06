<?php

namespace Wnd\Component\Alipay;

use Exception;

/**
 *@since 2020.07.16 支付宝当面付支创建类
 *
 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.precreate
 */
class AlipayQRCodePay extends AlipayPayBuilder {

	protected $product_code = 'FACE_TO_FACE_PAYMENT';
	protected $method       = 'alipay.trade.precreate';

	/**
	 * 发起请求并生产二维码
	 * @return array
	 */
	protected function buildInterface(): string{
		/**
		 *采用WordPress内置函数发送Post请求
		 */
		$response = wp_remote_post($this->gateway_url,
			[
				'timeout'     => 60,
				'redirection' => 5,
				'httpversion' => '2.0',
				'body'        => $this->common_configs,

				// 必须设置此项，否则无法解析支付宝的响应json
				'headers'     => array("Content-type" => "application/x-www-form-urlencoded;charset=$this->charset"),
			]
		);

		/**
		 *返回请求结果
		 */
		if (is_wp_error($response)) {
			throw new Exception($response->get_error_message());
		}

		$result = json_decode($response['body'], true);
		$result = $result['alipay_trade_precreate_response'];

		if ('10000' != $result['code']) {
			throw new Exception($result['code'] . ' - ' . $result['msg'] . ' - ' . $result['sub_msg']);
		}

		if (wp_is_mobile()) {
			$alipay_app_link = 'alipayqr://platformapi/startapp?saId=10000007&qrcode=' . urldecode($result['qr_code']);
			return '<script>window.location.href="' . $alipay_app_link . '"</script><a href="' . $alipay_app_link . '" class="button">打开支付宝支付</a>';
		} else {
			return '<img src="' . wnd_generate_qrcode($result['qr_code']) . '"><h3>支付宝扫码支付</h3>';
		}
	}
}
