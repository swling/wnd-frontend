<?php
namespace Wnd\Component\Payment\Alipay;

use Wnd\Component\Payment\Alipay\AlipayService;

/**
 * 支付宝单笔转账
 *
 * 参考教程 @link https://blog.csdn.net/umufeng/article/details/103824594
 * API文档 @link https://opendocs.alipay.com/apis/api_28/alipay.fund.trans.uni.transfer
 *
 * @since 2020.06.08
 */
class Transfer {
	// 转账固定值
	protected $method      = 'alipay.fund.trans.uni.transfer';
	protected $productCode = 'TRANS_ACCOUNT_NO_PWD';

	// 转账金额
	protected $trans_amount;

	// 站内唯一交易订单号
	protected $out_biz_no;

	// 交易订单标题
	protected $order_title;

	// 收款方信息 ['identity_type'=>'ALIPAY_LOGON_ID', 'identity'=>'xxx@xx.com', 'name'=>'xxx']
	protected $payee_info = [];

	// 备注
	protected $remark;

	// 请求参数
	protected $params;

	// 支付宝基本配置参数
	protected $alipayConfig;

	/**
	 * @since 0.9.17
	 */
	public function __construct(array $alipayConfig) {
		$this->alipayConfig = array_merge(AlipayService::$defaultConfig, $alipayConfig);
		$this->charset      = $alipayConfig['charset'];
		$this->gatewayUrl   = $alipayConfig['gateway_url'];
	}

	/**
	 * 总金额
	 */
	public function setTransAmount(float $trans_amount) {
		$this->trans_amount = $trans_amount;
	}

	/**
	 * 交易订单号
	 */
	public function setOutBizNo($out_biz_no) {
		$this->out_biz_no = $out_biz_no;
	}

	/**
	 * 订单主题
	 */
	public function setOrderTitle($order_title) {
		$this->order_title = $order_title;
	}

	/**
	 * 设置收款账户
	 *
	 * @param string $identity 	收款方支付宝账号
	 * @param string $name     	收款方真实姓名
	 */
	public function setPayeeInfo($identity, $name) {
		$this->payee_info = [
			'identity'      => $identity,
			'identity_type' => 'ALIPAY_LOGON_ID',
			'name'          => $name,
		];
	}

	/**
	 * 发起订单
	 * @return array
	 */
	public function doTransfer() {
		//请求参数
		$bizContent = [
			'out_biz_no'   => $this->out_biz_no,
			'product_code' => $this->productCode,
			'trans_amount' => $this->trans_amount, //单位 元
			'order_title'  => $this->order_title, //订单标题
			'payee_info'   => $this->payee_info,
			'remark'       => $this->remark, //转账备注（选填）
			'biz_scene'    => 'DIRECT_TRANSFER',
		];

		$alipayService = new AlipayService($this->alipayConfig);
		$this->params  = $alipayService->generatePayParams($this->method, $bizContent);

		return $this->buildRequestForm();
	}

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param  $para_temp               请求参数数组
	 * @return 提交表单HTML文本
	 */
	protected function buildRequestForm() {
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->gatewayUrl . '?charset=' . $this->charset . "' method='POST'>";
		foreach ($this->params as $key => $val) {
			if (false === $this->checkEmpty($val)) {
				$val = str_replace("'", '&apos;', $val);
				$sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'>";
			}
		}unset($key, $val);
		//submit按钮控件请不要含有name属性
		$sHtml = $sHtml . "<input type='submit' value='ok' style='display:none;'></form>";
		$sHtml = $sHtml . "<script>document.forms['alipaysubmit'].submit();</script>";
		return $sHtml;
	}

	/**
	 * 校验$value是否非空
	 * if not set ,return true;
	 * if is null , return true;
	 */
	private static function checkEmpty(string $value): bool {
		if (!isset($value)) {
			return true;
		}

		if ($value === null) {
			return true;
		}

		if (trim($value) === '') {
			return true;
		}

		return false;
	}
}
