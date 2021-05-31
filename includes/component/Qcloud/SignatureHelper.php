<?php
namespace Wnd\Component\Qcloud;

use Wnd\Component\Utility\CloudRequest;

/**
 *@link https://cloud.tencent.com/document/product/1278/46715
 *@since 0.9.30
 *腾讯云云平台产品签名助手 API 3.0 V3.0
 */
class SignatureHelper extends CloudRequest {

	private $algorithm = "TC3-HMAC-SHA256";
	private $service   = "";

	protected function genAuthorization(): string{
		$this->setHeaders();

		$this->service   = $this->getService();
		$signature       = $this->genSignature();
		$date            = gmdate("Y-m-d", $this->timestamp);
		$credentialScope = $date . "/" . $this->service . "/tc3_request";
		$signedHeaders   = $this->parseHeaders()['signedHeaders'];
		$authorization   = $this->algorithm . " Credential=" . $this->secretID . "/" . $credentialScope . ", SignedHeaders=" . $signedHeaders . ", Signature=" . $signature;

		return $authorization;
	}

	/**
	 *补充或修改用户传参 $args['headers']
	 */
	private function setHeaders() {
		$this->headers["Content-Type"]   = $this->headers["Content-Type"] ?? 'application/json; charset=utf-8';
		$this->headers["X-TC-Timestamp"] = $this->timestamp;
	}

	/**
	 *根据请求节点域名解析出 Service，即二级域名名称。
	 *如：$host = "cvm.tencentcloudapi.com" 则 $service = "cvm"
	 */
	private function getService(): string{
		$parsedUrl = parse_url($this->url);
		$host      = explode('.', $parsedUrl['host']);
		$subdomain = $host[0];

		return $subdomain;
	}

	private function genSignature(): string{
		$date          = gmdate("Y-m-d", $this->timestamp);
		$secretDate    = hash_hmac("SHA256", $date, "TC3" . $this->secretKey, true);
		$secretService = hash_hmac("SHA256", $this->service, $secretDate, true);
		$secretSigning = hash_hmac("SHA256", "tc3_request", $secretService, true);
		$signature     = hash_hmac("SHA256", $this->buildStringToSign(), $secretSigning);
		return $signature;
	}

	private function buildStringToSign(): string{
		$date                   = gmdate("Y-m-d", $this->timestamp);
		$credentialScope        = $date . "/" . $this->service . "/tc3_request";
		$hashedCanonicalRequest = hash("SHA256", $this->buildCanonicalRequestString());
		$stringToSign           = $this->algorithm . "\n" . $this->timestamp . "\n" . $credentialScope . "\n" . $hashedCanonicalRequest;
		return $stringToSign;
	}

	private function buildCanonicalRequestString(): string{
		$httpRequestMethod    = $this->method;
		$canonicalUri         = "/";
		$canonicalQueryString = $this->queryString;
		$canonicalHeaders     = $this->parseHeaders()['CanonicalHeaders'];
		$signedHeaders        = $this->parseHeaders()['signedHeaders'];
		$payload              = $this->body;
		$hashedRequestPayload = hash("SHA256", $payload);
		$canonicalRequest     = $httpRequestMethod . "\n" . $canonicalUri . "\n" . $canonicalQueryString . "\n"
			. $canonicalHeaders . "\n" . $signedHeaders . "\n" . $hashedRequestPayload;

		return $canonicalRequest;
	}

	/**
	 *@link https://cloud.baidu.com/doc/Reference/s/njwvz1yfu#4-canonicalheaders
	 *解析 headers 数组生成：signedHeaders 及 CanonicalHeaders
	 */
	private function parseHeaders(): array{
		$list_array = [];
		foreach ($this->headers as $key => $value) {
			if (empty($value)) {
				continue;
			}

			if (0 === stripos($key, 'x-tc-')) {
				continue;
			}

			$key              = strtolower($key);
			$list_array[$key] = $key . ':' . $value;
		}

		ksort($list_array);
		$signedHeaders    = join(';', array_keys($list_array));
		$CanonicalHeaders = join("\n", array_values($list_array)) . "\n";

		return compact('signedHeaders', 'CanonicalHeaders');
	}
}
