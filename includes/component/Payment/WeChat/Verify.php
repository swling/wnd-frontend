<?php
namespace Wnd\Component\Payment\WeChat;

use Wnd\Component\Requests\Requests;

/**
 * 验签
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay4_1.shtml
 */
class Verify {
	private $apiKey;
	private $wechatcertificate;

	public function __construct($mchID, $apiKey, $serialNumber, $privateKey) {
		$this->apiKey    = $apiKey;
		$this->signature = new Signature($mchID, $serialNumber, $privateKey);
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

		$_serialNo = $this->parseSerialNo();
		if ($serialNo !== $_serialNo) {
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
	 * 解析平台证书序列号
	 *
	 */
	private function parseSerialNo(): string{
		$info = openssl_x509_parse($this->getWechatCertificates());
		return $info['serialNumberHex'] ?? '';
	}

	/**
	 * 验签
	 *
	 */
	private function verify($message, $signature): bool{
		$publicKey = openssl_get_publickey($this->getWechatCertificates());
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
	 * 通过api获取平台证书
	 * @author Swling
	 *
	 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/wechatpay/wechatpay5_1.shtml
	 * @since 2021.10.04
	 */
	private function getWechatCertificates(): string {
		if ($this->wechatcertificate) {
			return $this->wechatcertificate;
		}

		$reqUrl    = 'https://api.mch.weixin.qq.com/v3/certificates';
		$reqParams = [];

		$headers = [
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

		$certificate = json_decode($response['body'], true)['data'][0]['encrypt_certificate'] ?? '';
		if (!$certificate) {
			return '';
		}

		$ciphertext              = $certificate['ciphertext'];
		$associatedData          = $certificate['associated_data'];
		$nonceStr                = $certificate['nonce'];
		$this->wechatcertificate = $this->decryptToString($ciphertext, $associatedData, $nonceStr);

		return $this->wechatcertificate;
	}

	/**
	 * 解密数据
	 * Decrypt AEAD_AES_256_GCM ciphertext
	 *
	 * @param  string      $associatedData AES GCM additional authentication data
	 * @param  string      $nonceStr       AES GCM nonce
	 * @param  string      $ciphertext     AES GCM cipher text
	 * @return string|bool Decrypted string on success or FALSE on failure
	 */
	private function decryptToString($ciphertext, $associatedData, $nonceStr) {
		$ciphertext = \base64_decode($ciphertext);
		if (strlen($ciphertext) <= 16) {
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
			$ctext   = substr($ciphertext, 0, -16);
			$authTag = substr($ciphertext, -16);
			return \openssl_decrypt($ctext, 'aes-256-gcm', $this->apiKey, \OPENSSL_RAW_DATA, $nonceStr,
				$authTag, $associatedData);
		}

		throw new \RuntimeException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
	}
}
