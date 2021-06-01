<?php
namespace Wnd\Component\Qcloud;

use Exception;
use Wnd\Component\Utility\ObjectStorage;

/**
 *@since 0.9.29
 *@link https://cloud.tencent.com/document/product/436/7751
 *
 *腾讯云对象存储
 */
class QcloudCos extends ObjectStorage {

	/**
	 *PUT
	 *@link https://cloud.tencent.com/document/product/436/7749
	 */
	public function uploadFile(string $sourceFile, int $timeout = 1800): array{
		$file                     = fopen($sourceFile, 'rb');
		$headers                  = [];
		$headers['Content-MD5']   = base64_encode(md5_file($sourceFile, true));
		$headers['Authorization'] = $this->create_authorization('put', 3600, $headers);

		$ch = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串,而不直接输出
		curl_setopt($ch, CURLOPT_URL, $this->fileUri); //设置put到的url
		curl_setopt($ch, CURLOPT_HTTPHEADER, static::array_to_curl_headers($headers));
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证对等证书
		// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //不检查服务器SSL证书

		curl_setopt($ch, CURLOPT_PUT, true); //设置为PUT请求
		curl_setopt($ch, CURLOPT_INFILE, $file); //设置资源句柄
		curl_setopt($ch, CURLOPT_INFILESIZE, filesize($sourceFile));

		// curl_setopt($ch, CURLOPT_HEADER, true); // 开启header信息以供调试
		// curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		curl_exec($ch);
		$response = curl_getinfo($ch);

		fclose($file);
		curl_close($ch);

		if (200 != $response['http_code']) {
			throw new Exception($response['http_code']);
		}

		return $response;
	}

	/**
	 *Delete
	 *@link https://cloud.tencent.com/document/product/436/7743
	 **/
	public function deleteFile(int $timeout = 30): array{
		$headers                  = [];
		$headers['Authorization'] = $this->create_authorization('delete', 3600, $headers);

		$ch = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串,而不直接输出
		curl_setopt($ch, CURLOPT_URL, $this->fileUri); //设置delete url
		curl_setopt($ch, CURLOPT_HTTPHEADER, static::array_to_curl_headers($headers));
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证对等证书
		// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //不检查服务器SSL证书

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		// curl_setopt($ch, CURLOPT_HEADER, true); // 开启header信息以供调试
		// curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		curl_exec($ch);
		$response = curl_getinfo($ch);
		curl_close($ch);

		if (204 != $response['http_code']) {
			throw new \Exception($response['http_code']);
		}

		return $response;
	}

	/**
	 *将数组键值对转为 curl headers 数组
	 **/
	private static function array_to_curl_headers(array $headers): array{
		$result = [];
		foreach ($headers as $key => $value) {
			$result[] = $key . ':' . $value;
		}

		return $result;
	}

	/**
	 * 计算签名
	 * @link https://cloud.tencent.com/document/product/436/7778
	 */
	private function create_authorization(string $method, int $expires, array $headers, array $http_query = []): string{
		// 步骤1：生成 KeyTime
		$key_time = time() . ';' . (time() + $expires); //unix_timestamp;unix_timestamp

		// 步骤2：生成 SignKey
		$sign_key = hash_hmac('sha1', $key_time, $this->secretKey);

		// 步骤3：生成 UrlParamList 和 HttpParameters
		$url_param_list_array = static::build_list_and_parameters($http_query);
		$url_param_list       = $url_param_list_array['list'];
		$http_parameters      = $url_param_list_array['parameters'];

		// 步骤4：生成 HeaderList 和 HttpHeaders
		$header_list_array = static::build_list_and_parameters($headers);
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
	 *生成 UrlParamList 和 HttpParameters
	 */
	private static function build_list_and_parameters(array $data): array{
		$list_array = [];
		foreach ($data as $key => $value) {
			$key              = strtolower(urlencode($key));
			$value            = urlencode($value);
			$list_array[$key] = $key . '=' . $value;
		}

		ksort($list_array);
		$list       = join(';', array_keys($list_array));
		$parameters = join('&', array_values($list_array));

		return compact('list', 'parameters');
	}
}
