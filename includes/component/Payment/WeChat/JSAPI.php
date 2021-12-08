<?php
namespace Wnd\Component\Payment\WeChat;

use Exception;

/**
 * JSAPI 预支付订单（小程序或公众号等微信环境内部支付）
 * - JSAPI 预支付获取微信支付 prepay_id
 * - 将 prepay_id 交付给微信客户端
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_1.shtml
 */
class JSAPI extends PayBuilder {

	protected $gateWay = 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi';

	/**
	 * 发起客户端支付请求，正常响应示例：
	 * {
	 * 	"prepay_id": "wx26112221580621e9b071c00d9e093b0000"
	 * }
	 */
	public function buildInterface(): string{
		$result = $this->excuteRequest();

		if ($result['headers']['http_code'] != 200) {
			$body = json_decode($result['body'], true);
			throw new Exception($body['message']);
		}

		return $result['body'];
	}
}
