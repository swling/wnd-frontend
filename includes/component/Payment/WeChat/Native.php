<?php
namespace Wnd\Component\Payment\WeChat;

use Exception;

/**
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_4_1.shtml
 */
class Native extends PayBuilder {

	protected $gateWay = 'https://api.mch.weixin.qq.com/v3/pay/transactions/native';

	/**
	 * 发起客户端支付请求
	 *
	 * @return string 返回构造支付请求的Html，如自动提交的表单或支付二维码
	 */
	public function buildInterface(): string{
		$result = $this->excuteRequest();
		$body   = json_decode($result['body'], true);

		if ($result['headers']['http_code'] != 200) {
			throw new Exception($body['code'] . ':' . $body['message']);
		}

		return $body['code_url'];
	}
}
