<?php
namespace Wnd\Component\Aliyun;

use Wnd\Component\Utility\CloudObjectStorage;

/**
 *@since 0.9.29
 *@link https://www.aliyun.com/product/oss
 *阿里云对象存储
 */
class ObjectStorage extends CloudObjectStorage {

	/**
	 * 上传文件
	 * @link https://help.aliyun.com/document_detail/31978.html
	 */
	public function uploadFile(string $sourceFile, int $timeout = 1800): array{
		//设置头部
		$mime_type = mime_content_type($sourceFile);
		$md5       = base64_encode(md5_file($sourceFile, true));
		$headers   = [
			'Date:' . static::getDate(),
			'Content-Type:' . $mime_type,
			'Content-MD5:' . $md5,
			'Authorization:' . $this->create_authorization('PUT', $mime_type, $md5),
		];

		return static::curlPut($sourceFile, $this->fileUri, $headers, $timeout);
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

		return static::curlDelete($this->fileUri, $headers, $timeout);
	}

	/**
	 *云平台图片缩放处理
	 */
	public static function resizeImage(string $image_url, int $width, int $height): string {
		return "{$image_url}?x-oss-process=image/resize,m_fill,h_{$height},w_{$width}";
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
