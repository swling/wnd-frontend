<?php
namespace Wnd\Component\Alipay;

use Wnd\Component\Alipay\AlipayService;

/**
 *@since 2020.06.08
 *支付宝单笔转账
 *（测试支付宝证书工具生成证书，未成功，本文件代码尚未测试 @2020.06.08）
 *
 *加签模式：使用（资金支出类接口）必须使用【公钥证书】加签
 *@link https://opendocs.alipay.com/open/291/105971
 *
 *参考教程
 *@link https://blog.csdn.net/umufeng/article/details/103824594
 *
 *API文档
 *@link https://opendocs.alipay.com/apis/api_28/alipay.fund.trans.uni.transfer
 */
class AlipayTransfer extends AlipayService {
	// 转账固定值
	protected $method       = 'alipay.fund.trans.uni.transfer';
	protected $product_code = 'TRANS_ACCOUNT_NO_PWD';

	//应用公钥证书地址
	protected $public_key_path;

	//支付宝根证书地址
	protected $alipay_root_cert_path;

	//支付宝公钥证书地址（验签时使用）
	protected $alipay_public_key_path;

	// 转账金额
	protected $trans_amount;

	// 站内唯一交易订单号
	protected $out_biz_no;

	// 交易订单标题
	protected $order_title;

	// 收款方标识类型：ALIPAY_LOGON_ID：支付宝登录号，支持邮箱和手机号格式
	protected $identity_type = 'ALIPAY_LOGON_ID';

	// 收款方账户：支付宝登录号，支持邮箱和手机号格式
	protected $identity;

	// 收款方信息 ['identity_type'=>'ALIPAY_LOGON_ID','identity'=>'xxx@xx.com']
	protected $payee_info = [];

	/**
	 *Construct
	 */
	public function __construct() {
		parent::__construct();

	}

	/**
	 *总金额
	 */
	public function set_trans_amount(float $trans_amount) {
		$this->trans_amount = $trans_amount;
	}

	/**
	 *交易订单号
	 */
	public function set_out_biz_no($out_biz_no) {
		$this->out_biz_no = $out_biz_no;
	}

	/**
	 *订单主题
	 */
	public function set_order_title($order_title) {
		$this->order_title = $order_title;
	}

	/**
	 *设置收款账户
	 */
	public function set_identity($identity) {
		$this->identity = $identity;
	}

	/**
	 * 发起订单
	 * @return array
	 */
	public function doPay() {
		//请求参数
		$request_configs = [
			'out_biz_no'   => $this->out_biz_no,
			'product_code' => $this->product_code,
			'trans_amount' => $this->trans_amount, //单位 元
			'order_title'  => $this->order_title, //订单标题
		];

		//公共参数
		$common_configs = [
			'app_id'              => $this->app_id,
			'method'              => $this->method, //接口名称
			'format'              => 'JSON',
			'return_url'          => $this->return_url,
			'charset'             => $this->charset,
			'sign_type'           => $this->sign_type,
			'timestamp'           => date('Y-m-d H:i:s'),
			'version'             => '1.0',
			'notify_url'          => $this->notify_url,
			'biz_content'         => json_encode($request_configs),

			// 证书加签方式特有参数
			'alipay_root_cert_sn' => $this->getRootCertSN(), //支付宝根证书SN（alipay_root_cert_sn）
			'app_cert_sn'         => $this->getCertSN(), //应用公钥证书SN（app_cert_sn）
		];
		$common_configs["sign"] = $this->generateSign($common_configs, $common_configs['sign_type']);

		return $this->buildRequestForm($common_configs);
	}

	/**
	 * 从证书中提取序列号
	 * @param $cert
	 * @return string
	 */
	public function getCertSN() {
		$cert = file_get_contents($this->public_key_path);
		$ssl  = openssl_x509_parse($cert);
		$SN   = md5($this->array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
		return $SN;
	}

	/**
	 * 提取根证书序列号
	 * @param $cert  根证书
	 * @return string|null
	 */
	public function getRootCertSN() {
		$cert  = file_get_contents($this->alipay_root_cert_path);
		$array = explode("-----END CERTIFICATE-----", $cert);
		$SN    = null;
		for ($i = 0; $i < count($array) - 1; $i++) {
			$ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
			if (strpos($ssl[$i]['serialNumber'], '0x') === 0) {
				$ssl[$i]['serialNumber'] = $this->hex2dec($ssl[$i]['serialNumber']);
			}
			if ($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption") {
				if ($SN == null) {
					$SN = md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
				} else {

					$SN = $SN . "_" . md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
				}
			}
		}
		return $SN;
	}

	/**
	 * 0x转高精度数字
	 * @param $hex
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