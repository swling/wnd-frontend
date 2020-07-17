<?php
namespace Wnd\Model;

use Wnd\Component\Alipay\AlipayQRCodePay;

/**
 *@since 2020.07.16
 *
 *支付宝当面付
 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.precreate
 *
 *问题排查：
 * - @link https://opendocs.alipay.com/open/common/fr9vsk
 * - @link https://opensupport.alipay.com/support/tools/cloudparse
 */
class Wnd_Payment_Alipay_QRCode extends Wnd_Payment_Alipay {
	/**
	 *发起支付
	 *
	 */
	protected function do_pay(): string{
		$aliPay = new AlipayQRCodePay;
		$aliPay->setTotalAmount($this->get_total_amount());
		$aliPay->setOutTradeNo($this->get_out_trade_no());
		$aliPay->setSubject($this->get_subject());

		// QRCode 支付非跳转，而是采用 Ajax 提交，获取响应提取支付链接信息，用于二维码生成
		return $aliPay->pay();
	}
}
