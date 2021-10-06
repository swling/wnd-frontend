<?php
namespace Wnd\Component\Payment\WeChat;

/**
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_3_1.shtml
 */
class H5 extends PayBuilder {

	protected $gateWay = 'https://api.mch.weixin.qq.com/v3/pay/transactions/h5';

	/**
	 * 发起客户端支付请求
	 *
	 * @return string 返回构造支付请求的Html，如自动提交的表单或支付二维码
	 */
	public function buildInterface(): string{
		$result = static::excuteRequest();
		$body   = json_decode($result['body'], true);

		if ($result['headers']['http_code'] != 200) {
			throw new \Exception($body['code']);
		}

		$payUrl = $body['h5_url'];
		return '<script>window.location.href="' . $payUrl . '"</script><a href="' . $payUrl . '" class="button">微信支付</a>';
	}
}
