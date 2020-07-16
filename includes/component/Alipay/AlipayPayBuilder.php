<?php
namespace Wnd\Component\Alipay;

use Wnd\Component\Alipay\AlipayService;
use Wnd\Component\Utility\PaymentBuilder;

/**
 *@since 2020.07.16
 *
 *支付宝支付创建基类
 */
abstract class AlipayPayBuilder extends AlipayService implements PaymentBuilder {
	// 支付宝接口
	protected $product_code;
	protected $method;

	// 订单基本参数
	protected $total_amount;
	protected $out_trade_no;
	protected $subject;

	// 公共请求参数
	protected $common_configs;

	/**
	 *总金额
	 */
	public function set_total_amount($total_amount) {
		$this->total_amount = $total_amount;
	}

	/**
	 *交易订单号
	 */
	public function set_out_trade_no($out_trade_no) {
		$this->out_trade_no = $out_trade_no;
	}

	/**
	 *订单主题
	 */
	public function set_subject($subject) {
		$this->subject = $subject;
	}

	/**
	 * 构建支付参数，并发起订单
	 * 订单请求通常为表单提交并跳转至支付宝，也可能是Ajax提交获取响应，因此需要返回 do_pay()的值
	 *
	 * @return null|array
	 */
	public function pay() {
		$this->generate_common_configs();
		return $this->do_pay();
	}

	/**
	 *发起客户端支付请求
	 */
	abstract protected function do_pay();

	/**
	 * 签名并构造完整的请求参数
	 * @return string
	 */
	protected function generate_common_configs() {
		//请求参数
		$request_configs = [
			'out_trade_no' => $this->out_trade_no,
			'product_code' => $this->product_code,
			'total_amount' => $this->total_amount, //单位 元
			'subject'      => $this->subject, //订单标题
		];

		//公共参数
		$common_configs = [
			'app_id'      => $this->app_id,
			'method'      => $this->method, //接口名称
			'format'      => 'JSON',
			'return_url'  => $this->return_url,
			'charset'     => $this->charset,
			'sign_type'   => $this->sign_type,
			'timestamp'   => date('Y-m-d H:i:s'),
			'version'     => '1.0',
			'notify_url'  => $this->notify_url,
			'biz_content' => json_encode($request_configs),
		];
		$common_configs["sign"] = $this->generateSign($common_configs, $common_configs['sign_type']);

		$this->common_configs = $common_configs;
		return $common_configs;
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
