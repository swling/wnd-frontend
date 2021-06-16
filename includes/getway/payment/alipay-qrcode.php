<?php
namespace Wnd\Getway\Payment;

use Wnd\Component\Payment\Alipay\AlipayQRCodePay;
use Wnd\Getway\Payment\Alipay;

/**
 * 支付宝当面付
 * 问题排查：
 * - @link https://opendocs.alipay.com/open/common/fr9vsk
 * - @link https://opensupport.alipay.com/support/tools/cloudparse
 *
 * @link https://opendocs.alipay.com/apis/api_1/alipay.trade.precreate
 * @since 2020.07.16
 */
class Alipay_QRCode extends Alipay {
	/**
	 * 发起支付
	 *
	 */
	public function build_interface(): string{
		$aliPay = new AlipayQRCodePay(static::getConfig());
		$aliPay->setTotalAmount($this->total_amount);
		$aliPay->setOutTradeNo($this->out_trade_no);
		$aliPay->setSubject($this->subject);
		$aliPay->generateParams();

		/**
		 * 获取响应提取支付链接信息，生成二维码
		 * Ajax定期查询订单是否已经完成支付，以便下一步操作
		 */
		return $aliPay->buildInterface() . static::build_ajax_check_script($this->transaction->get_transaction_id());
	}
}
