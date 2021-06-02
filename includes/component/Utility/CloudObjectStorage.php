<?php
namespace Wnd\Component\Utility;

use Exception;

/**
 *@since 0.9.30
 *
 *对象存储抽象基类
 */
abstract class CloudObjectStorage {

	protected $secretID     = ''; //"云 API 密钥 SecretId";
	protected $secretKey    = ''; //"云 API 密钥 SecretKey";
	protected $endpoint     = ''; // COS 节点
	protected $filePathName = ''; // 文件在节点中的相对存储路径（签名需要）
	protected $fileUri      = ''; // 文件的完整 URI： $this->endpoint . $this->filePathName

	// 初始化
	public function __construct(string $secretID, string $secretKey, string $endpoint) {
		$this->secretID  = $secretID;
		$this->secretKey = $secretKey;
		$this->endpoint  = $endpoint;
	}

	/**
	 *设置文件存储路径
	 *以 '/' 开头
	 */
	public function setFilePathName(string $filePathName) {
		$filePathName       = '/' . trim($filePathName, '/');
		$this->filePathName = $filePathName;
		$this->fileUri      = $this->endpoint . $this->filePathName;
	}

	/**
	 *PUT
	 */
	abstract public function uploadFile(string $sourceFile, int $timeout = 1800): array;

	/**
	 *Delete
	 **/
	abstract public function deleteFile(int $timeout = 30): array;

	/**
	 *云平台图片缩放处理
	 */
	abstract public static function resizeImage(string $image_url, int $width, int $height): string;

	/**
	 *Curl PUT
	 */
	protected static function curlPut(string $sourceFile, string $targetUri, array $headers, int $timeout): array{
		$file = fopen($sourceFile, 'rb');
		$ch   = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串,而不直接输出
		curl_setopt($ch, CURLOPT_URL, $targetUri); //设置put到的url
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
	 *Curl Delete
	 */
	protected static function curlDelete(string $targetUri, array $headers, int $timeout): array{
		$ch = curl_init(); //初始化curl
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串,而不直接输出
		curl_setopt($ch, CURLOPT_URL, $targetUri); //设置delete url
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
	 *获取文件 URI
	 */
	public function getFileUri(): string {
		return $this->fileUri;
	}
}
