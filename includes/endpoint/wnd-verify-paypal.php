<?php
namespace Wnd\Endpoint;

use Wnd\Getway\Payment\PayPal;

/**
 *Paypal 支付校验（PayPal 支付流程显著区别于国内的支付宝及微信，故此单独设置 endpoint 处理）
 *@since 0.9.29
 *
 *注意事项：PayPal 只有同步回调，同步回调中执行 capture order 完成扣款，并执行站内业务逻辑
 *@link https://stackoverflow.com/questions/36221146/paypal-rest-api-fulfill-order-payment-on-redirect-url-or-on-webhook-call
 */
class Wnd_Verify_PayPal extends Wnd_Endpoint {
	// 响应类型
	protected $content_type = 'html';

	/**
	 *响应操作
	 */
	protected function do() {
		$token = $_REQUEST['token'] ?? '';
		if (!$token) {
			exit('Get capture token failed');
		}

		/**
		 *验签并处理相关站内业务
		 */
		$payment = new PayPal('PayPal');
		$payment->set_capture_token($token);
		$payment->verify();
		$payment->return();
	}
}
