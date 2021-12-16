<?php
namespace Wnd\Getway\Payment;

use Wnd\Component\Payment\Alipay\PayQRCode;
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
		$aliPay = new PayQRCode(static::getConfig());
		$aliPay->setTotalAmount($this->total_amount);
		$aliPay->setOutTradeNo($this->out_trade_no);
		$aliPay->setSubject($this->subject);
		$aliPay->generateParams();

		/**
		 * 获取响应提取支付链接信息，生成二维码
		 * Ajax定期查询订单是否已经完成支付，以便下一步操作
		 */
		$payment_id = $this->transaction->get_transaction_id();
		$qr_code    = $aliPay->buildInterface();
		if (wp_is_mobile()) {
			$alipay_app_link   = 'alipayqr://platformapi/startapp?saId=10000007&qrcode=' . urldecode($qr_code);
			$payment_interface = '<script>window.location.href="' . $alipay_app_link . '"</script><a href="' . $alipay_app_link . '" class="button">打开支付宝</a>';
			$title             = '支付宝APP支付';
		} else {
			$payment_interface = '<div id="alipay-qrcode" style="height:250px;"></div><script>wnd_qrcode("#alipay-qrcode", "' . $qr_code . '", 250)</script>';
			$title             = '支付宝扫码支付';
		}

		return static::build_payment_interface($payment_id, $payment_interface, $title);
	}
}
