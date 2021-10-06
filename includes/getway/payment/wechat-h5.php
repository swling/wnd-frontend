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
		$pay = new H5(static::$mchid, static::$appid, static::$privateKey, static::$serialNumber);

		$pay->setTotalAmount($this->total_amount);
		$pay->setOutTradeNo($this->out_trade_no);
		$pay->setSubject($this->subject);
		$pay->setNotifyUrl('https://wndwp.com/wp-json');
		$pay->generateParams();

		return $pay->buildInterface();
	}
}
