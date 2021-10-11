<?php
namespace Wnd\Component\Payment\WeChat;

use Wnd\Component\Requests\Requests;

/**
 * 验签
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay4_1.shtml
 */
class Verify {
	const KEY_LENGTH_BYTE      = 32;
	const AUTH_TAG_LENGTH_BYTE = 16;

	private $apiKey;
	private $weChatCertificates      = [];
	private $curretWeChatCertificate = '';

	public function __construct($mchID, $apiKey, $serialNumber, $privateKey) {
		if (strlen($apiKey) != static::KEY_LENGTH_BYTE) {
			throw new \InvalidArgumentException('无效的ApiV3Key，长度应为32个字节');
		}
		$this->apiKey    = $apiKey;
		$this->signature = new Signature($mchID, $serialNumber, $privateKey);
	}

	/**
	 * 通过外部设定微信平台证书
	 * - 该方法的主要作用是用于外部缓存平台证书，避免每次验签都请求远程 api 获取证书
	 *
	 * ### 缓存方法：
	 * - 应用程序使用 $this->getRemoteWechatCertificates() 获取平台证书信息，并以适当的方法缓存
	 * - 验签时，调用本方法指定缓存的平台证书，如序列化匹配，则本次验签将不会请求 API
	 *
	 * ## 证书新旧更替
	 * - 新旧更换期间，新证书可能尚未缓存，在缓存失效更新之前这段时间，验签将持续请求 api，因缓存时间不宜过长
	 * - 平台证书需要保持定期更新官方文档建议间隔时间小于12小时
	 *
	 * @see https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay5_1.shtml#part-2
	 */
	public function setWechatCertificates(array $weChatCertificates) {
		$this->weChatCertificates = $weChatCertificates;
	}

	/**
	 * 验证签名
	 */
	public function validate(): bool{
		$serialNo  = $this->getHeader('Wechatpay-Serial');
		$sign      = $this->getHeader('Wechatpay-Signature');
		$timestamp = $this->getHeader('Wechatpay-Timestamp');
		$nonce     = $this->getHeader('Wechatpay-Nonce');
		if (!isset($serialNo, $sign, $timestamp, $nonce)) {
			return false;
		}

		$body    = file_get_contents('php://input');
		$message = "$timestamp\n$nonce\n$body\n";

		// 获取本次验签对应序列化的证书
		$this->curretWeChatCertificate = $this->getWechatCertificate($serialNo);
		if (!$this->curretWeChatCertificate) {
			return false;
		}

		return $this->verify($message, $sign);
	}

	private function getHeader($key = '') {
		$headers = getallheaders();
		if ($key) {
			return $headers[$key] ?? '';
		}
		return $headers;
	}

	/**
	 * 验签
	 *
	 */
	private function verify($message, $signature): bool{
		$publicKey = openssl_get_publickey($this->curretWeChatCertificate);
		if (!$publicKey) {
			return false;
		}
		if (!in_array('sha256WithRSAEncryption', openssl_get_md_methods(true))) {
			return false;
		}

		$signature = base64_decode($signature);
		return openssl_verify($message, $signature, $publicKey, 'sha256WithRSAEncryption');
	}

	/**
	 * 解密报文
	 *
	 */
	public function notify(): array{
		$postStr = file_get_contents('php://input');
		if (!$postStr) {
			return [];
		}

		$postData = json_decode($postStr, true);
		if (!isset($postData['resource'])) {
			return [];
		}

		$data = $this->decryptToString(
			$postData['resource']['ciphertext'],
			$postData['resource']['associated_data'],
			$postData['resource']['nonce']
		);
		$data = json_decode($data, true);
		return is_array($data) ? $data : [];
	}

	/**
	 * 获取指定序列化的微信平台证书
	 * @author Swling
	 *
	 * @since 2021.10.04
	 */
	private function getWechatCertificate(string $serialNo): string {
		// 已存在证书
		if (isset($this->weChatCertificates[$serialNo])) {
			return $this->weChatCertificates[$serialNo]['certificate'];
		}

		// 远程请求证书
		return $this->getRemoteWechatCertificates()[$serialNo]['certificate'] ?? '';
	}

	/**
	 * 通过api获取平台证书
	 * @author Swling
	 *
	 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay5_1.shtml
	 * @since 2021.10.04
	 *
	 * @return array 以序列化为键名，过期时间、证书内容组成内容的二维数组
	 */
	public function getRemoteWechatCertificates(): array{
		// 请求API获取证书
		$reqUrl    = 'https://api.mch.weixin.qq.com/v3/certificates';
		$reqParams = [];
		$headers   = [
			'Authorization' => $this->signature->getAuthStr($reqUrl, 'GET', $reqParams),
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
			'User-Agent'    => $_SERVER['HTTP_USER_AGENT'],
		];
		$request  = new Requests;
		$response = $request->request(
			$reqUrl,
			[
				'method'  => 'GET',
				'headers' => $headers,
				'timeout' => 10,
			]
		);
		$data = json_decode($response['body'], true)['data'];

		/**
		 * 通常情况下，平台证书只会返回一个，但在新旧平台证书更迭时期，会返回两个证书
		 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay5_0.shtml
		 */
		foreach ($data as $certificate_data) {
			$serial_no           = $certificate_data['serial_no'];
			$expire_time         = $certificate_data['expire_time'];
			$encrypt_certificate = $certificate_data['encrypt_certificate'] ?? '';
			if (!$encrypt_certificate) {
				continue;
			}

			// 解密证书文本内容
			$ciphertext     = $encrypt_certificate['ciphertext'];
			$associatedData = $encrypt_certificate['associated_data'];
			$nonceStr       = $encrypt_certificate['nonce'];
			$certificate    = $this->decryptToString($ciphertext, $associatedData, $nonceStr);

			// 以序列化为键名，过期时间、证书内容组成内容的二维数组
			$this->weChatCertificates[$serial_no] = [
				'expire_time' => $expire_time,
				'certificate' => $certificate,
			];
		}

		return $this->weChatCertificates;
	}

	/**
	 * 解密数据
	 * Decrypt AEAD_AES_256_GCM ciphertext
	 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay4_2.shtml
	 *
	 * @param  string      $associatedData AES GCM additional authentication data
	 * @param  string      $nonceStr       AES GCM nonce
	 * @param  string      $ciphertext     AES GCM cipher text
	 * @return string|bool Decrypted string on success or FALSE on failure
	 */
	private function decryptToString($ciphertext, $associatedData, $nonceStr) {
		$ciphertext = \base64_decode($ciphertext);
		if (strlen($ciphertext) <= static::AUTH_TAG_LENGTH_BYTE) {
			return false;
		}

		// ext-sodium (default installed on >= PHP 7.2)
		if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') && \sodium_crypto_aead_aes256gcm_is_available()) {
			return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->apiKey);
		}

		// ext-libsodium (need install libsodium-php 1.x via pecl)
		if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') && \Sodium\crypto_aead_aes256gcm_is_available()) {
			return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->apiKey);
		}

		// openssl (PHP >= 7.1 support AEAD)
		if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
			$ctext   = substr($ciphertext, 0, static::AUTH_TAG_LENGTH_BYTE);
			$authTag = substr($ciphertext, static::AUTH_TAG_LENGTH_BYTE);
			return \openssl_decrypt($ctext, 'aes-256-gcm', $this->apiKey, \OPENSSL_RAW_DATA, $nonceStr, $authTag, $associatedData);
		}

		throw new \RuntimeException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
	}
}
