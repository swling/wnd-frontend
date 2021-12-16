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

		return $result['qr_code'];
	}
}
