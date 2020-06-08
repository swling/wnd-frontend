<?php
namespace Wnd\Component\Alipay;

use Wnd\Component\Alipay\AlipayConfig;

/**
 *@since 2019.03.02
 *支付宝签名及验签
 */
class AlipayService {

	// 支付宝公钥（验签时使用）
	protected $alipay_public_key;

	// 应用私钥（签名时使用）
	protected $app_private_key;

	protected $gateway_url;

	protected $app_id;

	protected $return_url;

	protected $notify_url;

	protected $sign_type;

	protected $charset;

	public function __construct() {
		$config = AlipayConfig::getConfig();
		foreach ($config as $key => $value) {
			$this->$key = $value;
		}
		unset($key, $value);
	}

	/**
	 * 读取配置参数并生成支付宝sign
	 */
	public function generateSign($params, $signType = 'RSA') {
		return $this->sign($this->getSignContent($params), $signType);
	}

	/**
	 * 生成sign
	 */
	protected function sign($data, $signType = 'RSA') {
		$priKey = $this->app_private_key;
		$res    = "-----BEGIN RSA PRIVATE KEY-----\n" .
		wordwrap($priKey, 64, "\n", true) .
			"\n-----END RSA PRIVATE KEY-----";
		($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
		if ('RSA2' == $signType) {
			openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
		} else {
			openssl_sign($data, $sign, $res);
		}
		$sign = base64_encode($sign);
		return $sign;
	}

	/**
	 * 验证签名
	 **/
	public function rsaCheck($params) {
		$sign     = $params['sign'];
		$signType = $params['sign_type'];
		unset($params['sign_type']);
		unset($params['sign']);
		return $this->verify($this->getSignContent($params), $sign, $signType);
	}

	public function verify($data, $sign, $signType = 'RSA') {
		$pubKey = $this->alipay_public_key;
		$res    = "-----BEGIN PUBLIC KEY-----\n" .
		wordwrap($pubKey, 64, "\n", true) .
			"\n-----END PUBLIC KEY-----";
		($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

		//调用openssl内置方法验签，返回bool值
		if ("RSA2" == $signType) {
			$result = (bool) openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
		} else {
			$result = (bool) openssl_verify($data, base64_decode($sign), $res);
		}

		//释放资源
		if (!$this->checkEmpty($this->alipay_public_key)) {
			openssl_free_key($res);
		}

		return $result;
	}

	/**
	 * 校验$value是否非空
	 * if not set ,return true;
	 * if is null , return true;
	 **/
	protected function checkEmpty($value) {
		if (!isset($value)) {
			return true;
		}

		if ($value === null) {
			return true;
		}

		if (trim($value) === "") {
			return true;
		}

		return false;
	}

	public function getSignContent($params) {
		ksort($params);
		$stringToBeSigned = '';
		$i                = 0;
		foreach ($params as $k => $v) {
			if (false === $this->checkEmpty($v) and "@" != substr($v, 0, 1)) {
				// 转换成目标字符集
				$v = $this->characet($v, $this->charset);
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}
		unset($k, $v);
		return $stringToBeSigned;
	}

	/**
	 * 转换字符集编码
	 * @param $data
	 * @param $targetCharset
	 * @return string
	 */
	protected function characet($data, $targetCharset) {
		if (!empty($data)) {
			$fileType = $this->charset;
			if (strcasecmp($fileType, $targetCharset) != 0) {
				$data = mb_convert_encoding($data, $targetCharset, $fileType);
				//$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
			}
		}
		return $data;
	}
}
