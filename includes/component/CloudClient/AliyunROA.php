<?php
namespace Wnd\Component\CloudClient;

use Exception;

/**
 * 阿里云 ROA 签名助手
 * @link https://help.aliyun.com/document_detail/315525.html
 * @link https://usercenter.console.aliyun.com/#/manage/ak ( $secretID string AccessKeyId)
 */
class AliyunROA extends CloudClient {

	/**
	 * @var string
	 */
	private static $headerSeparator = "\n";

	/**
	 * 生成 Authorization
	 *
	 */
	protected function generateAuthorization(): string{
		$this->setHeaders();

		return 'acs ' . $this->secretID . ':' . $this->genSignature();
	}

	/**
	 * 补充或修改用户传参 $args['headers']
	 * 阿里云不同产品 headers 各不相同，故此不设置统一 headers，直接以传参为准
	 */
	private function setHeaders() {
		$this->headers['Content-Type']           = $this->headers['Content-Type'] ?? 'application/json';
		$this->headers['Date']                   = gmdate('D, j M Y G:i:s T');
		$this->headers['Accept']                 = 'application/json';
		$this->headers['x-acs-signature-method'] = 'HMAC-SHA1';
		$this->headers['x-acs-signature-nonce']  = uniqid();
	}

	private function genSignature(): string{
		$stringToSign = $this->roaString();
		$sign         = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));
		return $sign;
	}

	/**
	 * @return string
	 */
	private function roaString() {
		return self::headerString($this->method, $this->headers) . self::resourceString($this->url);
	}

	/**
	 * @param string $method
	 * @param array  $headers
	 *
	 * @return string
	 */
	private static function headerString($method, array $headers) {
		$string = $method . self::$headerSeparator;
		if (isset($headers['Accept'])) {
			$string .= $headers['Accept'];
		}
		$string .= self::$headerSeparator;

		if (isset($headers['Content-MD5'])) {
			$string .= $headers['Content-MD5'];
		}
		$string .= self::$headerSeparator;

		if (isset($headers['Content-Type'])) {
			$string .= $headers['Content-Type'];
		}
		$string .= self::$headerSeparator;

		if (isset($headers['Date'])) {
			$string .= $headers['Date'];
		}
		$string .= self::$headerSeparator;

		$string .= self::acsHeaderString($headers);

		return $string;
	}

	/**
	 * 步骤一：构造规范化请求头
	 * Construct standard Header for Alibaba Cloud.
	 *
	 * @param array $headers
	 *
	 * @return string
	 */
	private static function acsHeaderString(array $headers) {
		$array = [];
		foreach ($headers as $headerKey => $headerValue) {
			$key = strtolower($headerKey);
			if (strncmp($key, 'x-acs-', 6) === 0) {
				$array[$key] = $headerValue;
			}
		}
		ksort($array);
		$string = '';
		foreach ($array as $sortMapKey => $sortMapValue) {
			$string .= $sortMapKey . ':' . $sortMapValue . self::$headerSeparator;
		}

		return $string;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	private static function resourceString(string $uri): string{
		$uri_info = parse_url($uri);
		if (isset($uri_info['query'])) {
			return $uri_info['path'] . '?' . rawurldecode($uri_info['query']);
		} else {
			return $uri_info['path'];
		}
	}

	/**
	 * 核查响应，如果出现错误，则抛出异常
	 * @link https://help.aliyun.com/document_detail/315525.html#section-x9t-xmq-5iu
	 */
	protected static function checkResponse(array $responseBody) {
		$code = $responseBody['code'] ?? '';
		if (200 != $code) {
			throw new Exception(json_encode($responseBody['msg']));
		}
	}

}
