<?php
namespace Wnd\Component\CloudObjectStorage;

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
	public function uploadFile(string $sourceFile, int $timeout = 1800): array{
		/**
		 * 由于在前端请求中，无法设置头部参数'Date' 故此处也统一采用 'X-OSS-Date' 替换 'Date'
		 * 官方文档中并未说明 X-OSS-Date 可替代 date，但实际运行中可以
		 * @since 0.9.32
		 */
		$contentType = mime_content_type($sourceFile);
		$md5         = md5_file($sourceFile);
		$headers     = $this->generateHeaders('PUT', $contentType, $md5);

		return static::put($sourceFile, $this->fileUri, $headers, $timeout);
	}

	/**
	 * 获取文件 URI
	 * @link https://help.aliyun.com/document_detail/31952.html
	 */
	public function getFileUri(int $expires = 0): string {
		if (!$expires) {
			return $this->fileUri;
		}

		// 签名
		$content_type          = '';
		$md5                   = '';
		$method                = 'GET';
		$canonicalizedResource = '/' . $this->parseBucket() . $this->filePathName;
		$expires               = time() + $expires;

		//生成签名：换行符必须使用双引号
		$str       = $method . "\n" . $md5 . "\n" . $content_type . "\n" . $expires . "\n" . $canonicalizedResource;
		$signature = base64_encode(hash_hmac('sha1', $str, $this->secretKey, true));

		$oss_file_url = $this->fileUri . '?OSSAccessKeyId=' . rawurlencode($this->secretID) . '&Expires=' . $expires . '&Signature=' . rawurlencode($signature);
		return $oss_file_url;
	}

	/**
	 * Delete
	 * @link https://help.aliyun.com/document_detail/31982.html
	 */
	public function deleteFile(int $timeout = 30): array{
		$headers = $this->generateHeaders('DELETE');

		return static::delete($this->fileUri, $headers, $timeout);
	}

	/**
	 * 获取签名后的完整 headers
	 * - 本方法中 md5 参数设定为为32位16进制字符串，而非二进制数据。
	 * - 之所以如此设置，是为了方便外部调用，如前端 OSS 直传时可利用 js 计算 MD5 值，进而调用本方法生成请求 headers
	 * @since 0.9.35
	 */
	public function generateHeaders(string $method, string $contentType = '', string $md5 = ''): array{
		$method     = strtoupper($method);
		$md5_base64 = base64_encode(hex2bin($md5));
		$headers    = [
			'X-OSS-Date'    => static::getDate(),
			'Authorization' => $this->generateAuthorization($method, $contentType, $md5_base64),
		];

		if ('PUT' == $method or 'POST' == $method) {
			$headers['Content-Type'] = $contentType;
			$headers['Content-MD5']  = $md5_base64;
		}

		return $headers;
	}

	/**
	 * 云平台图片缩放处理
	 */
	public static function resizeImage(string $image_url, int $width, int $height): string {
		return "{$image_url}?x-oss-process=image/resize,m_fill,h_{$height},w_{$width}";
	}

	/**
	 * 获取签名
	 * 文档有误：$canonicalizedOSSHeaders . "\n" . $canonicalizedResource 之间必须加入换行符
	 * @link https://help.aliyun.com/document_detail/31951.html
	 */
	private function generateAuthorization(string $method, string $contentType = '', $md5 = ''): string{
		$method                  = strtoupper($method);
		$date                    = static::getDate();
		$canonicalizedOSSHeaders = 'x-oss-date:' . $date;
		$canonicalizedResource   = '/' . $this->parseBucket() . $this->filePathName;

		//生成签名：换行符必须使用双引号
		$str       = $method . "\n" . $md5 . "\n" . $contentType . "\n" . $date . "\n" . $canonicalizedOSSHeaders . "\n" . $canonicalizedResource;
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
	private function parseBucket(): string{
		$parsedUrl = parse_url($this->endpoint);
		$host      = explode('.', $parsedUrl['host']);
		$subdomain = $host[0];

		return $subdomain;
	}
}
