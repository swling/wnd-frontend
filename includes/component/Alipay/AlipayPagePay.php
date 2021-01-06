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
	 * 建立请求，以表单HTML形式构造（默认）
	 * @return 提交表单HTML文本
	 */
	protected function buildInterface(): string{
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->gateway_url . "?charset=" . $this->charset . "' method='POST'>";
		$sHtml .= '<h3>即将跳转到第三方支付平台……</h3>';
		foreach ($this->common_configs as $key => $val) {
			if (false === AlipayService::checkEmpty($val)) {
				$val = str_replace("'", "&apos;", $val);
				$sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'>";
			}
		}unset($key, $val);
		//submit按钮控件请不要含有name属性
		$sHtml = $sHtml . "<input type='submit' value='ok' style='display:none;'></form>";
		$sHtml = $sHtml . "<script>document.forms['alipaysubmit'].submit();</script>";
		return $sHtml;
	}
}
