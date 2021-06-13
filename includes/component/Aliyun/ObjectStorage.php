<?php
namespace Wnd\Component\Aliyun;

use Wnd\Component\Utility\CloudObjectStorage;

/**
 * 阿里云对象存储
 * @link https://www.aliyun.com/product/oss
 * @since 0.9.29
 */
class ObjectStorage extends CloudObjectStorage {

	/**
	 * 上传文件
	 * @link https://help.aliyun.com/document_detail/31978.html
	 */
	public function uploadFile(string $sourceFile, int $timeout = 1800): array{
		/**
		 * 由于在前端请求中，无法设置头部参数'Date' 故此处也统一采用 'X-OSS-Date' 替换 'Date'
		 * 官方文档中并未说明 X-OSS-Date 可替代 date，但实际运行中可以
		 * @since 0.9.32
		 */
		$mime_type = mime_content_type($sourceFile);
		$md5       = base64_encode(md5_file($sourceFile, true));
		$headers   = [
			'X-OSS-Date:' . static::getDate(),
			'Content-Type:' . $mime_type,
			'Content-MD5:' . $md5,
			'Authorization:' . $this->generateAuthorization('PUT', $mime_type, $md5),
		];

		return static::curlPut($sourceFile, $this->fileUri, $headers, $timeout);
	}

	/**
	 * Delete
	 * @link https://help.aliyun.com/document_detail/31982.html
	 */
	public function deleteFile(int $timeout = 30): array{
		//设置头部
		$headers = [
			'X-OSS-Date:' . static::getDate(),
			'Authorization:' . $this->generateAuthorization('DELETE'),
		];

		return static::curlDelete($this->fileUri, $headers, $timeout);
	}

	/**
	 * 云平台图片缩放处理
	 */
	public static function resizeImage(string $image_url, int $width, int $height): string {
		return "{$image_url}?x-oss-process=image/resize,m_fill,h_{$height},w_{$width}";
	}

	/**
	 * 获取签名
	 * @link https://help.aliyun.com/document_detail/31951.html
	 */
	public function generateAuthorization(string $method, string $minType = '', $md5 = ''): string{
		$method                  = strtoupper($method);
		$date                    = static::getDate();
		$canonicalizedOSSHeaders = 'x-oss-date:' . $date;
		$canonicalizedResource   = '/' . $this->parse_bucket() . $this->filePathName;

		//生成签名：换行符必须使用双引号
		$str       = $method . "\n" . $md5 . "\n" . $minType . "\n" . $date . "\n" . $canonicalizedOSSHeaders . "\n" . $canonicalizedResource;
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
	 * 根据 endpoint 域名解析出 bucket
	 */
	private function parse_bucket(): string{
		$parsedUrl = parse_url($this->endpoint);
		$host      = explode('.', $parsedUrl['host']);
		$subdomain = $host[0];

		return $subdomain;
	}
}
