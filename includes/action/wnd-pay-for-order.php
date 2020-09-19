<?php
namespace Wnd\Action;

/**
 *统一封装付费支付接口
 *可选站内余额支付或在线第三方支付
 *@since 0.8.66
 *
 */
class Wnd_Pay_For_Order extends Wnd_Action_Ajax {

	/**
	 *本 Action 为中转操作，无需解析数据。且，如果包含人机验证，重复解析数据会导致重复验证，引发操作失败
	 *
	 */
	protected $parse_data = false;

	public function execute(): array{
		// 余额支付
		if ('internal' == $_POST['payment_gateway']) {
			$order = new Wnd_Create_Order();
			return $order->execute();
		}

		// 在线支付
		$order = new Wnd_Do_Pay();
		return $order->execute();
	}
}
