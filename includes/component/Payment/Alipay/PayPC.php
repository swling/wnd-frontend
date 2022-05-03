<?php
namespace Wnd\Component\Payment\Alipay;

/**
 * 支付宝PC网页支付
 *
 * @link https://opendocs.alipay.com/apis/api_1/alipay.trade.page.pay
 * @since 2019.03.02 支付宝网页支创建类
 */
class PayPC extends PayBuilder {

	protected $productCode = 'FAST_INSTANT_TRADE_PAY';
	protected $method      = 'alipay.trade.page.pay';

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * 表单字段属性不能使用双引号，因为 value 可能包含 json 字符串
	 * @return string
	 */
	public function buildInterface(): string{
		$sHtml = '<h2>支付宝支付</h2>';
		$sHtml .= "<form id='alipaysubmit' action='" . $this->gatewayUrl . '?charset=' . $this->charset . "' method='POST' target='_blank'>";
		foreach ($this->params as $key => $val) {
			$val = str_replace("'", '&apos;', $val);
			$sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'>";
		}unset($key, $val);
		$sHtml .= '</form>';
		$sHtml .= "<script>document.forms['alipaysubmit'].submit();</script>";
		return $sHtml;
	}
}
