<?php
namespace Wnd\Component\CloudObjectStorage;

use Wnd\Component\Requests\Requests;

/**
 * 阿里云对象存储
 * @link https://www.aliyun.com/product/oss
 * @since 0.9.29
 */
class Aliyun extends CloudObjectStorage {

	/**
	 * 上传文件
	 * @link https://help.aliyun.com/document_detail/31978.html
	 */
	public function uploadFile(string $sourceFile, int $timeout = 1800): array {
		$headers                 = [];
		$headers['Content-Type'] = mime_content_type($sourceFile);
		$headers['Content-MD5']  = md5_file($sourceFile);
		$headers                 = $this->generateHeaders('PUT', $headers);

		return static::put($sourceFile, $this->fileUri, $headers, $timeout);
	}

	/**
	 * 获取文件 URI
	 * @link https://help.aliyun.com/document_detail/31952.html
	 */
	public function getFileUri(int $expires = 0, array $query = [], bool $internal = false): string {
		if (!$expires) {
			if (!$query) {
				return $this->fileUri;
			}

			$queryStr = urldecode(http_build_query($query));
			return $this->fileUri . '?' . $queryStr;
		}

		$query['x-oss-expires'] = $expires;
		return $this->getPrivateFile($this->fileUri, $query, $internal);
	}

	/**
	 * Delete
	 * @link https://help.aliyun.com/document_detail/31982.html
	 */
	public function deleteFile(int $timeout = 30): array {
		$headers = $this->generateHeaders('DELETE');

		return static::delete($this->fileUri, $headers, $timeout);
	}

	/**
	 * 批量删除
	 * @link https://help.aliyun.com/zh/oss/developer-reference/deletemultipleobjects#section-ztg-wzw-wdb
	 *
	 */
	public function deleteBatch(array $files, int $timeout = 30): array {
		// xml
		$requestBody = '<?xml version="1.0" encoding="UTF-8"?>';
		$requestBody .= '<Delete>';
		$requestBody .= '<Quiet>false</Quiet>';
		foreach ($files as $file) {
			$requestBody .= "<Object><Key>{$file}</Key></Object>";
		}
		$requestBody .= '</Delete>';

		// 签名及请求地址
		$this->setFilePathName('/');
		$headers                   = [];
		$headers['Content-Length'] = strlen($requestBody);
		$headers['Content-Type']   = 'application/xml';
		$headers['Content-MD5']    = md5($requestBody);
		$headers                   = $this->generateHeaders('POST', $headers, ['delete' => '']);

		// 发起请求
		$targetUri = $this->endpoint . '/?delete';
		$request   = new Requests();
		return $request->request($targetUri, ['method' => 'POST', 'headers' => $headers, 'timeout' => $timeout, 'body' => $requestBody]);
	}

	/**
	 * 获取签名后的完整 headers
	 * - 本方法中 md5 参数设定为为32位16进制字符串，而非二进制数据。
	 * - 之所以如此设置，是为了方便外部调用，如前端 OSS 直传时可利用 js 计算 MD5 值，进而调用本方法生成请求 headers
	 * @since 0.9.35
	 *
	 * @see 本方法中统一处理：将 method 大写；header 小写
	 */
	public function generateHeaders(string $method, array $headers = [], $query = []): array {
		$method  = strtoupper($method);
		$headers = array_change_key_case($headers, CASE_LOWER);

		// bucket、region、object
		$info = $this->extractOSSInfo($this->fileUri);
		extract($info);

		$defaultHeaders = [
			'host'                 => "{$bucket}.oss-{$region}.aliyuncs.com",
			'x-oss-date'           => gmdate('Ymd\THis\Z'),
			'x-oss-content-sha256' => 'UNSIGNED-PAYLOAD',
		];
		$headers = array_merge($defaultHeaders, $headers);
		if (isset($headers['content-md5'])) {
			$headers['content-md5'] = base64_encode(hex2bin($headers['content-md5']));
		}

		$resourcePath             = "/{$bucket}/{$object}";
		$authHeader               = $this->generateV4Signature($method, $region, $resourcePath, $headers, $query);
		$headers['authorization'] = $authHeader['authorization'];
		return $headers;
	}

	/**
	 * 云平台图片缩放处理
	 */
	public static function resizeImage(string $image_url, int $width, int $height): string {
		return "{$image_url}?x-oss-process=image/resize,m_fill,h_{$height},w_{$width}";
	}

	// ################################### V4
	/**
	 * GET 私有储存文件签名
	 * @link https://help.aliyun.com/zh/oss/developer-reference/add-signatures-to-urls?spm=a2c4g.11186623.0.i63#91b4edd42ez37
	 *
	 */
	private function getPrivateFile(string $url, array $query = [], bool $internal = false): string {
		// bucket、region、object
		$info = $this->extractOSSInfo($url);
		extract($info);

		$url          = $internal ? str_replace('.aliyuncs.com', '-internal.aliyuncs.com', $url) : $url;
		$host         = parse_url($url, PHP_URL_HOST);
		$date         = gmdate('Ymd');
		$oss_date     = gmdate('Ymd\THis\Z');
		$method       = 'GET';
		$resourcePath = "/{$bucket}/{$object}";
		$headers      = ['host' => $host];

		$defaultQuery = [
			'x-oss-date'               => $oss_date,
			'x-oss-additional-headers' => 'host',
			'x-oss-signature-version'  => 'OSS4-HMAC-SHA256',
			'x-oss-credential'         => "{$this->secretID}/{$date}/{$region}/oss/aliyun_v4_request",
			'x-oss-expires'            => 600,
		];
		$query = array_merge($defaultQuery, $query);

		$authHeader               = $this->generateV4Signature($method, $region, $resourcePath, $headers, $query);
		$query['x-oss-signature'] = $authHeader['signature'];
		$queryString              = http_build_query($query);

		return $url . '?' . $queryString;
	}

	/**
	 * OSS V4 签名
	 * @link https://help.aliyun.com/zh/oss/developer-reference/recommend-to-use-signature-version-4?spm=a2c4g.11186623.0.0.6f0d46d4Wv8dgf
	 */
	private function generateV4Signature(string $method, string $region, string $resourcePath, array $headers, array $query): array {
		// Pre
		$headers = array_change_key_case($headers, CASE_LOWER);
		$method  = strtoupper($method);
		ksort($headers, SORT_NATURAL | SORT_FLAG_CASE);
		ksort($query, SORT_NATURAL | SORT_FLAG_CASE);

		// Step 1: Create the Canonical Request
		$canonicalURI         = $resourcePath;
		$canonicalQueryString = $this->getCanonicalQueryString($query);
		$AdditionalHeaders    = isset($headers['content-length']) ? 'content-length;host' : 'host';
		$payloadHash          = 'UNSIGNED-PAYLOAD';
		$canonicalHeaders     = '';
		foreach ($headers as $key => $value) {
			$canonicalHeaders .= strtolower($key) . ':' . trim($value) . "\n";
		}

		$canonicalRequest = $method . "\n" . $canonicalURI . "\n" . $canonicalQueryString . "\n" . $canonicalHeaders . "\n" . $AdditionalHeaders . "\n" . $payloadHash;

		// Step 2: Create the String to Sign
		$datetime     = $headers['x-oss-date'] ?? $query['x-oss-date'];
		$date         = substr($datetime, 0, 8);
		$algorithm    = 'OSS4-HMAC-SHA256';
		$scope        = "{$date}/{$region}/oss/aliyun_v4_request";
		$stringToSign = $algorithm . "\n" . $datetime . "\n" . $scope . "\n" . hash('sha256', $canonicalRequest, false);

		// Step 3: Calculate the Signature
		$signature = $this->calcSignature($this->secretKey, $date, $region, $stringToSign);

		// Step 4: Construct the Authorization Header
		$authorization = $algorithm . ' ' . 'Credential=' . $this->secretID . '/' . $scope . ',AdditionalHeaders=' . $AdditionalHeaders . ',Signature=' . $signature;

		return ['authorization' => $authorization, 'signature' => $signature];
	}

	private function getCanonicalQueryString($query) {
		//Canonical Query
		$querySigned = [];
		foreach ($query as $key => $value) {
			$querySigned[rawurlencode($key)] = rawurlencode($value);
		}
		ksort($querySigned);
		$sortedQueryList = [];
		foreach ($querySigned as $key => $value) {
			if (strlen($value) > 0) {
				$sortedQueryList[] = $key . '=' . $value;
			} else {
				$sortedQueryList[] = $key;
			}
		}
		$canonicalQuery = implode('&', $sortedQueryList);
		return $canonicalQuery;
	}

	private function calcSignature(string $secret, string $date, string $region, string $stringToSign): string {
		$h1Key = hash_hmac('sha256', $date, 'aliyun_v4' . $secret, true);
		$h2Key = hash_hmac('sha256', $region, $h1Key, true);
		$h3Key = hash_hmac('sha256', 'oss', $h2Key, true);
		$h4Key = hash_hmac('sha256', 'aliyun_v4_request', $h3Key, true);
		return bin2hex(hash_hmac('sha256', $stringToSign, $h4Key, true));
	}

	private function extractOSSInfo(string $url): mixed {
		// 正则表达式匹配阿里云 OSS URL
		$pattern = '/https:\/\/([^\.]+)\.oss-([^\.]+)\.aliyuncs\.com\/(.+)/';
		preg_match($pattern, $url, $matches);

		if (empty($matches)) {
			// 正则表达式匹配阿里云 OSS URL
			$pattern = '/https:\/\/([^\.]+)\.oss-([^\.]+)\.aliyuncs\.com/';
			preg_match($pattern, $url, $matches);
			return [
				'bucket' => $matches[1],
				'region' => $matches[2],
				'object' => '',
			];
		}
		return [
			'bucket' => $matches[1],
			'region' => $matches[2],
			'object' => $matches[3],
		];
	}

}
