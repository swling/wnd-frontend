<?php
namespace Wnd\Component\Payment\Alipay;

/**
 * 支付宝PC网页支付
 *
 * @link https://opendocs.alipay.com/apis/api_1/alipay.trade.page.pay
 * @since 2019.03.02 支付宝网页支创建类
 */
class PayPC extends PayBuilder {

	protected $product_code = 'FAST_INSTANT_TRADE_PAY';
	protected $method       = 'alipay.trade.page.pay';

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @return 提交表单HTML文本
	 */
	public function buildInterface(): string{
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->gateway_url . '?charset=' . $this->charset . "' method='POST'>";
		$sHtml .= '<h3>即将跳转到第三方支付平台……</h3>';
		foreach ($this->params as $key => $val) {
			$val = str_replace("'", '&apos;', $val);
			$sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'>";
		}unset($key, $val);
		//submit按钮控件请不要含有name属性
		$sHtml = $sHtml . "<input type='submit' value='ok' style='display:none;'></form>";
		$sHtml = $sHtml . "<script>document.forms['alipaysubmit'].submit();</script>";
		return $sHtml;
	}
}
