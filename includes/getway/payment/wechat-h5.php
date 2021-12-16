<?php
namespace Wnd\Getway\Payment;

use Wnd\Component\Payment\WeChat\H5;

/**
 * 微信 H5 支付
 * @since 0.9.38
 */
class WeChat_H5 extends WeChat_Native {

	/**
	 * 发起支付
	 *
	 */
	public function build_interface(): string{
		extract(static::get_config());
		$pay = new H5($mchid, $appid, $apicert_sn, $private_key);

		$pay->setTotalAmount($this->total_amount);
		$pay->setOutTradeNo($this->out_trade_no);
		$pay->setSubject($this->subject);
		$pay->setNotifyUrl(wnd_get_endpoint_url('wnd_verify_wechat'));
		$pay->generateParams();

		// 生成支付界面
		$pay_url           = $pay->buildInterface();
		$payment_interface = '<a href="' . $pay_url . '" target="_blank" class="button">打开微信支付</a>';
		$payment_id        = $this->transaction->get_transaction_id();
		$title             = '微信支付';
		return static::build_payment_interface($payment_id, $payment_interface, $title);
	}
}
