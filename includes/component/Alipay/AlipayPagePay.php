<?php
namespace Wnd\Component\Alipay;

use Wnd\Component\Alipay\AlipayPayBuilder;

/**
 *@since 2019.03.02 支付宝网页支创建类
 */
class AlipayPagePay extends AlipayPayBuilder {

	/**
	 *PC支付和wap支付中：product_code 、method 参数有所不同，详情查阅如下
	 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.page.pay
	 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.wap.pay
	 */
	public function __construct() {
		parent::__construct();

		if (wp_is_mobile()) {
			$this->product_code = 'QUICK_WAP_WAY';
			$this->method       = 'alipay.trade.wap.pay';
		} else {
			$this->product_code = 'FAST_INSTANT_TRADE_PAY';
			$this->method       = 'alipay.trade.page.pay';
		}
	}

	/**
	 * 发起订单
	 * @return string
	 */
	public function do_pay() {
		echo $this->buildRequestForm($this->common_configs);
	}
}
