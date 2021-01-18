<?php
namespace Wnd\Action;

/**
 *统一封装付费支付接口
 *可选站内余额支付或在线第三方支付
 *@since 0.8.66
 *
 */
class Wnd_Pay_For_Order extends Wnd_Action {

	/**
	 *本 Action 为中转操作，无需校验人机验证，否则会导致重复验证
	 *
	 */
	protected $validate_captcha = false;

	/**
	 *子类操作会验证签名，此处无需验证签名
	 */
	protected $verify_sign = false;

	public function execute(): array{
		// 余额支付
		if ('internal' == $this->data['payment_gateway']) {
			$order = new Wnd_Create_Order();
			return $order->execute();
		}

		// 在线支付
		$order = new Wnd_Do_Pay();
		return $order->execute();
	}
}
