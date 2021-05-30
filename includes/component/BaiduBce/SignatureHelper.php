<?php
namespace Wnd\Component\BaiduBce;

use Exception;
use Wnd\Component\Utility\CloudRequest;

/**
 *@link https://cloud.baidu.com/doc/Reference/s/njwvz1yfu
 *@since 0.9.30
 *百度云平台产品签名助手
 */
class SignatureHelper extends CloudRequest {

	protected function genAuthorization(): string{
		// 百度云特殊规定
		$this->timestamp = gmdate('Y-m-d\TH:i:s\Z');

		$signature = $this->genSignature();
		$authStr   = 'bce-auth-v1/' . $this->secretID . '/' . $this->timestamp . '/' . $this->expiration . '/' . $this->getsignedHeaders() . '/' . $signature;
		return $authStr;
	}

	private function genSignature(): string {
		if (empty($this->method)) {
			throw new Exception('method is null or empty');
		}
		$signingKey = $this->genSigningKey();
		$authStr    = $this->method . "\n" . $this->getCanonicalURI() . "\n" . $this->getCanonicalQueryString() . "\n" . $this->getCanonicalHeaders();
		return $this->sha256($signingKey, $authStr);
	}

	/**
	 *@link https://cloud.baidu.com/doc/Reference/s/njwvz1yfu#%E4%BB%BB%E5%8A%A1%E4%B8%89%EF%BC%9A%E7%94%9F%E6%88%90%E6%B4%BE%E7%94%9F%E5%AF%86%E9%92%A5signingkey
	 */
	private function genSigningKey(): string {
		if (empty($this->secretID)) {
			throw new Exception('access key is null or empty');
		}
		if (empty($this->secretKey)) {
			throw new Exception('secret key is null or empty');
		}
		if (empty($this->timestamp)) {
			throw new Exception('timestamp is null or empty');
		}
		if (empty($this->expiration)) {
			throw new Exception('expiration is null or empty');
		}
		$authStr = 'bce-auth-v1/' . $this->secretID . '/' . $this->timestamp . '/' . $this->expiration;
		return $this->sha256($this->secretKey, $authStr);
	}

	/**
	 *@link https://cloud.baidu.com/doc/Reference/s/njwvz1yfu#2-canonicaluri
	 */
	private function getCanonicalURI(): string {
		if (empty($this->path)) {
			throw new Exception('path is null or empty');
		}
		return $this->dataEncode($this->path, true);
	}

	/**
	 *@link https://cloud.baidu.com/doc/Reference/s/njwvz1yfu#3-canonicalquerystring
	 */
	private function getCanonicalQueryString(): string{
		parse_str($this->queryString, $queryString);
		$strArry = [];
		foreach ($queryString as $key => $value) {
			if (empty($key) || strtolower($key) == 'authorization') {
				continue;
			}
			$strArry[] = $this->dataEncode($key, false) . '=' . $this->dataEncode($value, false);
		}
		ksort($strArry);
		return join('&', $strArry);
	}

	private function getCanonicalHeaders(): string {
		return $this->parseHeaders()['CanonicalHeaders'];
	}

	private function getsignedHeaders(): string {
		return $this->parseHeaders()['signedHeaders'];
	}

	/**
	 *@link https://cloud.baidu.com/doc/Reference/s/njwvz1yfu#4-canonicalheaders
	 *解析 headers 数组生成：signedHeaders 及 CanonicalHeaders
	 */
	private function parseHeaders(): array{
		if (empty($this->headers) || !array_key_exists('host', $this->headers)) {
			throw new Exception('host not in headers');
		}

		$list_array = [];
		foreach ($this->headers as $key => $value) {
			if (empty($value)) {
				continue;
			}

			$key              = strtolower($this->dataEncode($key, false));
			$value            = $this->dataEncode(trim($value), false);
			$list_array[$key] = $key . ':' . $value;
		}

		ksort($list_array);
		$signedHeaders    = join(';', array_keys($list_array));
		$CanonicalHeaders = join("\n", array_values($list_array));

		return compact('signedHeaders', 'CanonicalHeaders');
	}

	private function sha256(string $key, string $data) {
		return hash_hmac('sha256', $data, $key);
	}

	private function dataEncode(string $data, bool $isPath): string {
		if (empty($data)) {
			return '';
		}
		$encode = mb_detect_encoding($data, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
		if ($encode != 'UTF-8') {
			$data = $code1 = mb_convert_encoding($data, 'utf-8', $encode);
		}
		$encodeStr = rawurlencode($data);
		if ($isPath) {
			$encodeStr = str_replace('%2F', '/', $encodeStr);
		}
		return $encodeStr;
	}
}
