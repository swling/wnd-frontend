<?php
namespace Wnd\Component\CloudClient;

use Exception;

/**
 * 签名助手 2017/11/19
 * Class SignatureHelper
 * @link https://www.alibabacloud.com/help/zh/doc-detail/28761.htm
 * @link https://usercenter.console.aliyun.com/#/manage/ak ( $secretID string AccessKeyId)
 */
class Aliyun extends CloudClient {

	/**
	 * 阿里云产品签名鉴权包含在请求 body 而非 headers 中，故无需生成 Authorization
	 *
	 */
	protected function generateAuthorization(): string{
		$this->setHeaders();

		return '';
	}

	/**
	 * 补充或修改用户传参 $args['headers']
	 */
	private function setHeaders() {
		$this->headers = [];
	}

	/**
	 * 阿里云产品签名鉴权包含在请求 body
	 * 因此，为保持类的接口统一，在请求执行方法中完成签名及请求参数拼接，并调用父类方法发起请求
	 */
	protected function excuteRequest(): array{
		$this->body = $this->generateRequestBody();
		return parent::excuteRequest();
	}

	/**
	 * 构造请求数据
	 * @link https://www.alibabacloud.com/help/zh/doc-detail/28761.htm#d7e57
	 */
	private function generateRequestBody(): string{
		$apiParams = array_merge([
			'SignatureMethod'  => 'HMAC-SHA1',
			'SignatureNonce'   => uniqid(mt_rand(0, 0xffff), true),
			'SignatureVersion' => '1.0',
			'AccessKeyId'      => $this->secretID,
			'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
			'Format'           => 'JSON',
		], $this->body);
		ksort($apiParams);

		$sortedQueryStringTmp = '';
		foreach ($apiParams as $key => $value) {
			$sortedQueryStringTmp .= '&' . $this->encode($key) . '=' . $this->encode($value);
		}

		$stringToSign = $this->method . '&%2F&' . $this->encode(substr($sortedQueryStringTmp, 1));
		$sign         = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey . '&', true));
		$signature    = $this->encode($sign);

		return "Signature={$signature}{$sortedQueryStringTmp}";
	}

	private function encode($str) {
		$res = urlencode($str);
		$res = preg_replace('/\+/', '%20', $res);
		$res = preg_replace('/\*/', '%2A', $res);
		$res = preg_replace('/%7E/', '~', $res);
		return $res;
	}

	/**
	 * 核查响应，如果出现错误，则抛出异常
	 * @link https://help.aliyun.com/document_detail/102364.html#h2-url-3
	 */
	protected static function checkResponse(array $responseBody) {
		$code = $responseBody['Code'] ?? 'OK';
		if ('OK' != $code) {
			throw new Exception($responseBody['Message']);
		}
	}
}
