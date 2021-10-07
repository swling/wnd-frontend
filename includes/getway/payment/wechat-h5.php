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

		return $pay->buildInterface();
	}
}
