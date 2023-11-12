<?php
namespace Wnd\Component\Payment\Alipay;

/**
 * 支付宝移动网页支创
 *
 * @link https://opendocs.alipay.com/apis/api_1/alipay.trade.wap.pay
 * @since 2019.03.02
 */
class PayWAP extends PayPC {

	protected $productCode = 'QUICK_WAP_PAY';
	protected $method      = 'alipay.trade.wap.pay';
	protected $target      = '_self';

}
