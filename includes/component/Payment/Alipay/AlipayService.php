<?php
namespace Wnd\Component\Payment\Alipay;

/**
 * 支付宝签名及验签
 *
 * 签名： @link https://opendocs.alipay.com/common/02kf5q#%E8%87%AA%E8%A1%8C%E5%AE%9E%E7%8E%B0%E7%AD%BE%E5%90%8D
 * 验签： @link https://opendocs.alipay.com/common/02mse7#%E8%87%AA%E8%A1%8C%E5%AE%9E%E7%8E%B0%E9%AA%8C%E7%AD%BE
 *
 * @since 2019.03.02
 */
class AlipayService {

	// 默认配置
	public static $defaultConfig = [
		//应用ID,您的APPID。
		'app_id'              => '',

		// 支付宝根证书序列号，获取方法：AlipayCertClient::getRootCertSNFromContent($certContent); 对应证书：alipayRootCert.crt
		'alipay_root_cert_sn' => '',

		// 应用公钥证书序列号，获取方法：AlipayCertClient::getCertSNFromContent($certContent); 对应证书；appCertPublicKey_{xxx}.crt
		'app_cert_sn'         => '',

		// RSA2 商户私钥 用工具生成的应用私钥
		'app_private_key'     => '',

		// 支付宝公钥，获取方法：AlipayCertClient::getPublicKeyFromContent($cert); 对应证书：alipayCertPublicKey_RSA2.crt
		'alipay_public_key'   => '',

		//异步通知地址 *不能带参数否则校验不过 （插件执行页面地址）
		'notify_url'          => '',

		//同步跳转 *不能带参数否则校验不过 （插件执行页面地址）
		'return_url'          => '',

		//编码格式
		'charset'             => 'utf-8',

		//签名方式
		'sign_type'           => 'RSA2',

		// 版本
		'version'             => '1.0',

		//支付宝网关
		'gateway_url'         => '',
	];

	// 传参后的配置
	private $config;

	/**
	 * 公共请求参数
	 */
	protected $commonParams = [];

	/**
	 * @param array 支付宝基础配置信息
	 */
	public function __construct(array $config) {
		$this->config = array_merge(static::$defaultConfig, $config);

		// 构造支付宝公共请求参数，实际应用参数方法请根据接口文档添加或移除部分元素
		$this->commonParams = [
			'app_id'              => $this->config['app_id'],
			'alipay_root_cert_sn' => $this->config['alipay_root_cert_sn'],
			'app_cert_sn'         => $this->config['app_cert_sn'],
			'format'              => 'JSON',
			'charset'             => $this->config['charset'],
			'sign_type'           => $this->config['sign_type'],
			'timestamp'           => date('Y-m-d H:i:s'),
			'version'             => $this->config['version'],
		];
	}

	/**
	 * 签名并构造完整的付款请求参数
	 * @return array
	 */
	public function generatePayParams(string $method, array $bizContent): array{
		$commonParams                = $this->commonParams;
		$commonParams['notify_url']  = $this->config['notify_url'];
		$commonParams['return_url']  = $this->config['return_url'];
		$commonParams['method']      = $method;
		$commonParams['biz_content'] = json_encode($bizContent);
		$commonParams['sign']        = $this->generateSign($commonParams, $commonParams['sign_type']);

		return $commonParams;
	}

	/**
	 * 签名并构造完整的退款请求参数
	 * @link https://opendocs.alipay.com/apis/api_1/alipay.trade.refund
	 *
	 * @return array
	 */
	public function generateRefundParams(string $method, array $bizContent): array{
		$commonParams                = $this->commonParams;
		$commonParams['method']      = $method;
		$commonParams['biz_content'] = json_encode($bizContent);
		$commonParams['sign']        = $this->generateSign($commonParams, $commonParams['sign_type']);

		return $commonParams;
	}

	/**
	 * 读取配置参数并生成支付宝sign
	 */
	private function generateSign(array $params, string $signType = 'RSA'): string {
		return $this->sign($this->getSignContent($params), $signType);
	}

	/**
	 * 获取所有请求参数，不包括字节类型参数，如文件、字节流，剔除 sign 字段，剔除值为空的参数，
	 * 并按照第一个字符的键值 ASCII 码递增排序（字母升序排序），如果遇到相同字符则按照第二个字符的键值 ASCII 码递增排序，以此类推。
	 * @link https://opendocs.alipay.com/open/291/106118
	 */
	private function getSignContent(array $params): string{
		ksort($params);
		$stringToBeSigned = '';
		$i                = 0;
		foreach ($params as $k => $v) {
			if (false === static::checkEmpty($v) and '@' != substr($v, 0, 1)) {
				// 转换成目标字符集
				$v = $this->characet($v, $this->config['charset']);
				if (0 == $i) {
					$stringToBeSigned .= "$k" . '=' . "$v";
				} else {
					$stringToBeSigned .= '&' . "$k" . '=' . "$v";
				}
				$i++;
			}
		}
		unset($k, $v);
		return $stringToBeSigned;
	}

	/**
	 * 生成sign
	 */
	private function sign(string $data, string $signType = 'RSA'): string{
		$priKey = $this->config['app_private_key'];
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
	 */
	public function rsaCheck(array $params): bool{
		$sign     = $params['sign'] ?? '';
		$signType = $params['sign_type'] ?? '';
		unset($params['sign_type']);
		unset($params['sign']);
		return $this->verify($this->getSignContent($params), $sign, $signType);
	}

	private function verify(string $data, string $sign, string $signType = 'RSA'): bool{
		$pubKey = $this->config['alipay_public_key'];
		$res    = "-----BEGIN PUBLIC KEY-----\n" .
		wordwrap($pubKey, 64, "\n", true) .
			"\n-----END PUBLIC KEY-----";
		($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

		//调用openssl内置方法验签，返回bool值
		if ('RSA2' == $signType) {
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

	/**
	 * 转换字符集编码
	 * @param  $data
	 * @param  $targetCharset
	 * @return string
	 */
	private function characet(string $data, string $targetCharset): string {
		if (!empty($data)) {
			$fileType = $this->config['charset'];
			if (strcasecmp($fileType, $targetCharset) != 0) {
				$data = mb_convert_encoding($data, $targetCharset, $fileType);
				//$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
			}
		}
		return $data;
	}
}
