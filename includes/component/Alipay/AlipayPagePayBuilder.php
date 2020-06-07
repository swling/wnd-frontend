<?php
namespace Wnd\Component\Alipay;

use Wnd\Component\Alipay\AlipayService;

/**
 *@since 2019.03.02 支付宝网页支创建类
 */
class AlipayPagePayBuilder extends AlipayService {

	protected $gateway_url;
	protected $app_id;
	protected $return_url;
	protected $notify_url;

	protected $method;
	protected $product_code;

	protected $total_amount;
	protected $out_trade_no;
	protected $subject;

	public function __construct() {
		$this->charset = 'utf-8';
	}

	// 通过外部配置修改内部受保护的类属性
	public function __set($var, $val) {
		$this->$var = $val;
	}

	/**
	 * 发起订单
	 * @return array
	 */
	public function doPay() {
		//请求参数
		$requestConfigs = [
			'out_trade_no' => $this->out_trade_no,
			'product_code' => $this->product_code,
			'total_amount' => $this->total_amount, //单位 元
			'subject'      => $this->subject, //订单标题
		];
		$commonConfigs = [
			//公共参数
			'app_id'      => $this->app_id,
			'method'      => $this->method, //接口名称
			'format'      => 'JSON',
			'return_url'  => $this->return_url,
			'charset'     => $this->charset,
			'sign_type'   => 'RSA2',
			'timestamp'   => date('Y-m-d H:i:s'),
			'version'     => '1.0',
			'notify_url'  => $this->notify_url,
			'biz_content' => json_encode($requestConfigs),
		];
		$commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
		return $this->buildRequestForm($commonConfigs);
	}

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param $para_temp 请求参数数组
	 * @return 提交表单HTML文本
	 */
	protected function buildRequestForm($para_temp) {

		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->gateway_url . "?charset=" . $this->charset . "' method='POST'>";
		foreach ($para_temp as $key => $val) {
			if (false === $this->checkEmpty($val)) {
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
