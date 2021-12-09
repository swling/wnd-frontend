<?php
namespace Wnd\Component\Payment\WeChat;

use Exception;

/**
 * JSAPI 预支付订单（小程序或公众号等微信环境内部支付）
 * - JSAPI 预支付获取微信支付 prepay_id
 * - 基于获得的 prepay_id 按微信规定格式，组合数据并签名，返回给微信客户端
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_1.shtml
 * @link https://developers.weixin.qq.com/miniprogram/dev/api/payment/wx.requestPayment.html
 */
class JSAPI extends PayBuilder {

	protected $gateWay = 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi';

	/**
	 * ## 请求生成 prepay id，正常响应示例：
	 * {
	 * 	"prepay_id": "wx26112221580621e9b071c00d9e093b0000"
	 * }
	 *
	 * ## 按微信规范，组合支付数据并签名
	 */
	public function buildInterface(): string{
		$result = $this->excuteRequest();
		$body   = json_decode($result['body'], true);

		if ($result['headers']['http_code'] != 200) {
			throw new Exception($body['message']);
		}

		// 构造支付参数
		$prepay_id           = $body['prepay_id'];
		$result              = [];
		$result['appId']     = $this->appID;
		$result['timeStamp'] = (string) time();
		$result['nonceStr']  = uniqid();
		$result['package']   = 'prepay_id=' . $prepay_id;
		$result['signType']  = 'RSA';

		// 签名并将其附加的支付参数中
		$message           = $result['appId'] . "\n" . $result['timeStamp'] . "\n" . $result['nonceStr'] . "\n" . $result['package'] . "\n";
		$result['paySign'] = $this->signature->sign($message);

		// 接口约束此处只能返回字符串故 json 编码
		return json_encode($result);
	}
}
