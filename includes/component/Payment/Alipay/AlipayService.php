<?php
namespace Wnd\Component\Payment\Alipay;

use Wnd\Component\Payment\Alipay\AlipayConfig;

/**
 *@since 2019.03.02
 *支付宝签名及验签
 */
class AlipayService {

	// 支付宝公钥（验签时使用）
	protected $alipay_public_key;

	// 应用私钥（签名时使用）
	protected $alipay_app_private_key;

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

		// 构造支付宝公共请求参数，实际应用参数方法请根据接口文档添加或移除部分元素
		$this->common_configs = [
			'app_id'    => $this->app_id,
			'format'    => 'JSON',
			'charset'   => $this->charset,
			'sign_type' => $this->sign_type,
			'timestamp' => date('Y-m-d H:i:s'),
			'version'   => '1.0',
		];
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
		$priKey = $this->alipay_app_private_key;
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

	protected function verify($data, $sign, $signType = 'RSA') {
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

		//释放资源：仅在读取证书文件时
		// if (!$this->checkEmpty($this->alipay_public_key)) {
		// 	openssl_free_key($res);
		// }

		return $result;
	}

	/**
	 * 校验$value是否非空
	 * if not set ,return true;
	 * if is null , return true;
	 **/
	public static function checkEmpty($value) {
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

	protected function getSignContent($params) {
		ksort($params);
		$stringToBeSigned = '';
		$i                = 0;
		foreach ($params as $k => $v) {
			if (false === static::checkEmpty($v) and "@" != substr($v, 0, 1)) {
				// 转换成目标字符集
				$v = $this->characet($v, $this->charset);
				if (0 == $i) {
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

	/**
	 * 签名并构造完整的付款请求参数
	 * @return array
	 */
	public function generatePaymentConfigs(string $method, array $biz_content): array{
		$common_configs                = $this->common_configs;
		$common_configs['notify_url']  = $this->notify_url;
		$common_configs['return_url']  = $this->return_url;
		$common_configs['method']      = $method;
		$common_configs['biz_content'] = json_encode($biz_content);
		$common_configs['sign']        = $this->generateSign($common_configs, $common_configs['sign_type']);

		return $common_configs;
	}

	/**
	 * 签名并构造完整的退款请求参数
	 * @return array
	 */
	public function generateRefundConfigs(string $method, array $biz_content): array{
		$common_configs                = $this->common_configs;
		$common_configs['method']      = $method;
		$common_configs['biz_content'] = json_encode($biz_content);
		$common_configs['sign']        = $this->generateSign($common_configs, $common_configs['sign_type']);

		return $common_configs;
	}
}
