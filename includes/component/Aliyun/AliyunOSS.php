<?php
namespace Wnd\Component\Aliyun;

use Exception;
use Wnd\Component\Utility\ObjectStorage;

/**
 *@since 0.9.29
 *@link https://www.aliyun.com/product/oss
 *阿里云对象存储
 */
class AliyunOSS extends ObjectStorage {

	/**
	 * 上传文件
	 * @link https://help.aliyun.com/document_detail/31978.html
	 */
	function uploadFile(string $sourceFile, int $timeout = 1800): array{
		//设置头部
		$mime_type = mime_content_type($sourceFile);
		$md5       = base64_encode(md5_file($sourceFile, true));
		$headers   = [
			'Date:' . static::getDate(),
			'Content-Type:' . $mime_type,
			'Content-MD5:' . $md5,
			'Authorization:' . $this->create_authorization('PUT', $mime_type, $md5),
		];

		$file = fopen($sourceFile, 'rb');
		$ch   = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串,而不直接输出
		curl_setopt($ch, CURLOPT_URL, $this->fileUri); //设置put到的url
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
			throw new Exception(json_encode($response));
		}
		return $response;
	}

	/**
	 *Delete
	 *@link https://help.aliyun.com/document_detail/31982.html
	 **/
	public function deleteFile(int $timeout = 30): array{
		//设置头部
		$headers = [
			'Date:' . static::getDate(),
			'Authorization:' . $this->create_authorization('DELETE'),
		];

		$ch = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串,而不直接输出
		curl_setopt($ch, CURLOPT_URL, $this->fileUri); //设置delete url
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
			throw new Exception($response['http_code']);
		}

		return $response;
	}

	/**
	 * 获取签名
	 * @link https://help.aliyun.com/document_detail/31951.html
	 */
	private function create_authorization(string $method, string $minType = '', $md5 = '') {
		$bucket = $this->parse_bucket();
		$date   = static::getDate();

		//生成签名：换行符必须使用双引号
		$str       = $method . "\n" . $md5 . "\n" . $minType . "\n" . $date . "\n/" . $bucket . $this->filePathName;
		$signature = base64_encode(hash_hmac('sha1', $str, $this->secretKey, true));

		return 'OSS ' . $this->secretID . ':' . $signature;
	}

	/**
	 * Date表示此次操作的时间，且必须为GMT格式，例如”Sun, 22 Nov 2015 08:16:38 GMT”。
	 * @link https://help.aliyun.com/document_detail/31955.htm
	 */
	private static function getDate(): string {
		return gmdate('D, d M Y H:i:s') . ' GMT';
	}

	/**
	 *根据 endpoint 域名解析出 bucket
	 */
	private function parse_bucket(): string{
		$parsedUrl = parse_url($this->endpoint);
		$host      = explode('.', $parsedUrl['host']);
		$subdomain = $host[0];

		return $subdomain;
	}
}
