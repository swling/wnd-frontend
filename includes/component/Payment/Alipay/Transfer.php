<?php
namespace Wnd\Component\Payment\Alipay;

use Wnd\Component\Payment\Alipay\AlipayService;

/**
 * 支付宝单笔转账
 *
 * @date 2020.07.21 本地代码测试通过，转账成功，但尚未整合到本插件
 * @date 2021.06.15 重构支付宝后，尚未改造此类
 *
 * 加签模式：使用（资金支出类接口）必须使用【公钥证书】加签 @link https://opendocs.alipay.com/open/291/105971
 * 参考教程 @link https://blog.csdn.net/umufeng/article/details/103824594
 * API文档 @link https://opendocs.alipay.com/apis/api_28/alipay.fund.trans.uni.transfer
 *
 * @since 2020.06.08
 */
class Transfer extends AlipayService {
	// 转账固定值
	protected $method       = 'alipay.fund.trans.uni.transfer';
	protected $product_code = 'TRANS_ACCOUNT_NO_PWD';

	// 应用公钥证书服务器文件路径
	protected $app_public_key_path;

	// 应用RSA私钥。注意：此处为是CSR私钥而非RSA私钥。是生成证书步骤中，产生的包含对应域名的私钥
	protected $alipay_app_private_key;

	// 支付宝根证书服务器文件路径
	protected $alipay_root_cert_path;

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

	/**
	 * Construct
	 */
	public function __construct() {
		parent::__construct();
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
	 * @param string $name     		收款方真实姓名
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
		$request_configs = [
			'out_biz_no'   => $this->out_biz_no,
			'product_code' => $this->product_code,
			'trans_amount' => $this->trans_amount, //单位 元
			'order_title'  => $this->order_title, //订单标题
			'payee_info'   => $this->payee_info,
			'remark'       => $this->remark, //转账备注（选填）
			'biz_scene'    => 'DIRECT_TRANSFER',
		];

		//公共参数
		$common_configs = [
			'app_id'              => $this->app_id,
			'method'              => $this->method, //接口名称
			'format'              => 'JSON',
			'charset'             => $this->charset,
			'sign_type'           => $this->sign_type,
			'timestamp'           => date('Y-m-d H:i:s'),
			'version'             => '1.0',
			'biz_content'         => json_encode($request_configs),

			// 证书加签方式特有参数
			'alipay_root_cert_sn' => $this->getRootCertSN(), //支付宝根证书SN（alipay_root_cert_sn）
			'app_cert_sn'         => $this->getCertSN(), //应用公钥证书SN（app_cert_sn）
		];
		$common_configs['sign'] = $this->generateSign($common_configs, $common_configs['sign_type']);

		return $this->buildRequestForm($common_configs);
	}

	/**
	 * 从证书中提取序列号
	 * @param  $cert
	 * @return string
	 */
	public function getCertSN() {
		$cert = file_get_contents($this->app_public_key_path);
		$ssl  = openssl_x509_parse($cert);
		$SN   = md5($this->array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
		return $SN;
	}

	/**
	 * 提取根证书序列号
	 * @param  $cert         根证书
	 * @return string|null
	 */
	public function getRootCertSN() {
		$cert  = file_get_contents($this->alipay_root_cert_path);
		$array = explode('-----END CERTIFICATE-----', $cert);
		$SN    = null;
		for ($i = 0; $i < count($array) - 1; $i++) {
			$ssl[$i] = openssl_x509_parse($array[$i] . '-----END CERTIFICATE-----');
			if (0 === strpos($ssl[$i]['serialNumber'], '0x')) {
				$ssl[$i]['serialNumber'] = $this->hex2dec($ssl[$i]['serialNumber']);
			}
			if ($ssl[$i]['signatureTypeLN'] == 'sha1WithRSAEncryption' || $ssl[$i]['signatureTypeLN'] == 'sha256WithRSAEncryption') {
				if ($SN == null) {
					$SN = md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
				} else {

					$SN = $SN . '_' . md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
				}
			}
		}
		return $SN;
	}

	/**
	 * 0x转高精度数字
	 * @param  $hex
	 * @return int|string
	 */
	protected function hex2dec($hex) {
		$dec = 0;
		$len = strlen($hex);
		for ($i = 1; $i <= $len; $i++) {
			$dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
		}
		return $dec;
	}

	protected function array2string($array) {
		$string = [];
		if ($array && is_array($array)) {
			foreach ($array as $key => $value) {
				$string[] = $key . '=' . $value;
			}
		}
		return implode(',', $string);
	}

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param  $para_temp               请求参数数组
	 * @return 提交表单HTML文本
	 */
	protected function buildRequestForm($para_temp) {
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->gateway_url . '?charset=' . $this->charset . "' method='POST'>";
		foreach ($para_temp as $key => $val) {
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
}
