<?php
namespace Wnd\Component\CloudObjectStorage;

/**
 * 腾讯云对象存储
 * @link https://cloud.tencent.com/document/product/436/7751
 * @since 0.9.29
 */
class Qcloud extends CloudObjectStorage {

	/**
	 * PUT
	 * @link https://cloud.tencent.com/document/product/436/7749
	 */
	public function uploadFile(string $sourceFile, int $timeout = 1800): array{
		$md5         = base64_encode(md5_file($sourceFile, true));
		$contentType = mime_content_type($sourceFile);
		$headers     = $this->generateHeaders('PUT', $contentType, $md5);

		return static::put($sourceFile, $this->fileUri, $headers, $timeout);
	}

	/**
	 * Delete
	 * @link https://cloud.tencent.com/document/product/436/7743
	 */
	public function deleteFile(int $timeout = 30): array{
		$headers = $this->generateHeaders('DELETE');
		return static::delete($this->fileUri, $headers, $timeout);
	}

	/**
	 * 获取签名后的完整 headers
	 * @since 0.9.35
	 */
	public function generateHeaders(string $method, string $contentType = '', string $md5 = ''): array{
		$method  = strtoupper($method);
		$headers = [];
		if ('PUT' == $method or 'POST' == $method) {
			$headers['Content-Type'] = $contentType;
			$headers['Content-MD5']  = $md5;
		}

		$headers['Authorization'] = $this->generateAuthorization($method, 3600, $headers);

		return $headers;
	}

	/**
	 * 云平台图片缩放处理
	 */
	public static function resizeImage(string $image_url, int $width, int $height): string {
		return $image_url;
	}

	/**
	 * 计算签名
	 * @link https://cloud.tencent.com/document/product/436/7778
	 */
	private function generateAuthorization(string $method, int $expires, array $headers, array $http_query = []): string{
		// 步骤1：生成 KeyTime
		$key_time = time() . ';' . (time() + $expires); //unix_timestamp;unix_timestamp

		// 步骤2：生成 SignKey
		$sign_key = hash_hmac('sha1', $key_time, $this->secretKey);

		// 步骤3：生成 UrlParamList 和 HttpParameters
		$url_param_list_array = static::parseArrayToSign($http_query);
		$url_param_list       = $url_param_list_array['list'];
		$http_parameters      = $url_param_list_array['parameters'];

		// 步骤4：生成 HeaderList 和 HttpHeaders
		$header_list_array = static::parseArrayToSign($headers);
		$header_list       = $header_list_array['list'];
		$http_headers      = $header_list_array['parameters'];

		// exit($http_headers);

		// 步骤5：生成 HttpString
		$uri_path_name = urldecode($this->filePathName);
		$httpString    = strtolower($method) . "\n$uri_path_name\n$http_parameters\n$http_headers\n";

		// 步骤6：生成 StringToSign
		$sha1edHttpString = sha1($httpString);
		$stringToSign     = "sha1\n$key_time\n$sha1edHttpString\n";

		// 步骤7：生成 Signature
		$signature = hash_hmac('sha1', $stringToSign, $sign_key);

		// 步骤8：生成签名
		$authorization = "q-sign-algorithm=sha1&q-ak=$this->secretID&q-sign-time=$key_time&q-key-time=$key_time&q-header-list=$header_list&q-url-param-list=$url_param_list&q-signature=$signature";
		return $authorization;
	}

	/**
	 * 生成 UrlParamList 和 HttpParameters
	 */
	private static function parseArrayToSign(array $data): array{
		$list_array = [];
		foreach ($data as $key => $value) {
			$key              = strtolower(urlencode($key));
			$value            = urlencode($value);
			$list_array[$key] = $key . '=' . $value;
		}

		ksort($list_array);
		$list       = implode(';', array_keys($list_array));
		$parameters = implode('&', array_values($list_array));

		return compact('list', 'parameters');
	}
}
