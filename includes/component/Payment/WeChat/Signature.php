<?php
namespace Wnd\Component\Payment\WeChat;

/**
 * 签名
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay4_0.shtml
 */
class Signature {

	private $mchID;
	private $privateKey;
	private $serialNumber;

	public function __construct(string $mchID, string $serialNumber, string $privateKey) {
		$this->mchID        = $mchID;
		$this->privateKey   = $privateKey;
		$this->serialNumber = $serialNumber;
	}

	/**
	 * 构造 HTTP 认证头 Authorization
	 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay4_0.shtml#part-3
	 */
	public function getAuthStr(string $requestUrl, string $method, array $reqParams = []) {
		$schema = 'WECHATPAY2-SHA256-RSA2048';
		$token  = $this->getToken($requestUrl, $method, $reqParams);
		return $schema . ' ' . $token;
	}

	/**
	 * 构造签名信息
	 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay4_0.shtml#part-3
	 */
	private function getToken(string $requestUrl, string $method, array $reqParams = []): string{
		$body      = $reqParams ? json_encode($reqParams) : '';
		$nonce     = $this->getNonce();
		$timestamp = time();
		$message   = $this->buildMessage($method, $nonce, $timestamp, $requestUrl, $body);
		$sign      = $this->sign($message);
		$serialNo  = $this->serialNumber;
		return sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
			$this->mchID, $nonce, $timestamp, $serialNo, $sign
		);
	}

	private function getNonce(): string {
		static $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength  = strlen($characters);
		$randomString      = '';
		for ($i = 0; $i < 32; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	/**
	 * 构造签名字符串
	 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay4_0.shtml#part-1
	 */
	private function buildMessage(string $method, string $nonce, int $timestamp, string $requestUrl, string $body = '') {
		$method       = strtoupper($method);
		$urlParts     = parse_url($requestUrl);
		$canonicalUrl = ($urlParts['path'] . (!empty($urlParts['query']) ? "?{$urlParts['query']}" : ''));
		return strtoupper($method) . "\n" .
			$canonicalUrl . "\n" .
			$timestamp . "\n" .
			$nonce . "\n" .
			$body . "\n";
	}

	/**
	 * 计算签名值
	 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay4_0.shtml#part-2
	 */
	public function sign(string $message): string {
		if (!in_array('sha256WithRSAEncryption', openssl_get_md_methods(true))) {
			throw new \RuntimeException('当前PHP环境不支持SHA256withRSA');
		}
		if (!openssl_sign($message, $sign, $this->privateKey, 'sha256WithRSAEncryption')) {
			throw new \UnexpectedValueException('签名验证过程发生了错误');
		}
		return base64_encode($sign);
	}
}
