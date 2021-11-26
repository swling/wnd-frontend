<?php
namespace Wnd\Component\Payment\Alipay;

use Exception;
use Wnd\Component\Requests\Requests;

/**
 * @link https://opendocs.alipay.com/apis/api_1/alipay.trade.precreate
 * @since 2020.07.16 支付宝当面付支创建类
 */
class PayQRCode extends PayBuilder {

	protected $product_code = 'FACE_TO_FACE_PAYMENT';
	protected $method       = 'alipay.trade.precreate';

	/**
	 * 发起请求并生产二维码
	 * @return array
	 */
	public function buildInterface(): string{
		$request  = new Requests;
		$response = $request->request($this->gateway_url,
			[
				'method'  => 'POST',
				'timeout' => 60,

				// 支付宝的请求中 header 及 body 必须按此设置
				'body'    => http_build_query($this->params),
				'headers' => ['Content-type' => "application/x-www-form-urlencoded; charset=$this->charset"],
			]
		);

		/**
		 * 返回请求结果
		 */
		$result = json_decode($response['body'], true);
		$result = $result['alipay_trade_precreate_response'];

		if ('10000' != $result['code']) {
			throw new Exception($result['code'] . ' - ' . $result['msg'] . ' - ' . $result['sub_msg']);
		}

		if (static::is_mobile()) {
			$alipay_app_link = 'alipayqr://platformapi/startapp?saId=10000007&qrcode=' . urldecode($result['qr_code']);
			return '<script>window.location.href="' . $alipay_app_link . '"</script><a href="' . $alipay_app_link . '" class="button">打开支付宝支付</a>';
		} else {
			return '<div id="alipay-qrcode"></div><script>wnd_qrcode("#alipay-qrcode", "' . $result['qr_code'] . '", 250)</script><h3>支付宝扫码支付</h3>';
		}
	}

	/**
	 * Test if the current browser runs on a mobile device (smart phone, tablet, etc.)
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	private static function is_mobile(): bool {
		if (empty($_SERVER['HTTP_USER_AGENT'])) {
			$is_mobile = false;
		} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
			|| strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false) {
			$is_mobile = true;
		} else {
			$is_mobile = false;
		}

		return $is_mobile;
	}
}
