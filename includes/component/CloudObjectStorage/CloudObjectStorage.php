<?php
namespace Wnd\Component\CloudObjectStorage;

use Exception;
use Wnd\Component\Requests\Requests;

/**
 * 对象存储抽象基类
 * @since 0.9.30
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
	 * 设置文件存储路径
	 * 以 '/' 开头
	 */
	public function setFilePathName(string $filePathName) {
		$filePathName       = '/' . trim($filePathName, '/');
		$this->filePathName = $filePathName;
		$this->fileUri      = $this->endpoint . $this->filePathName;
	}

	/**
	 * PUT
	 */
	abstract public function uploadFile(string $sourceFile, int $timeout = 1800): array;

	/**
	 * GET
	 * - 公共读：$this->fileUri
	 * - 私有读：签名后的访问链接。具体实现在子类中根据服务商文档完成构造。
	 */
	abstract public function getFileUri(int $expires = 0, array $query = [], bool $internal = false): string;

	/**
	 * Delete
	 *
	 */
	abstract public function deleteFile(int $timeout = 30): array;

	/**
	 * Delete Batch
	 *
	 */
	abstract public function deleteBatch(array $files, int $timeout = 30): array;

	/**
	 * 生成签名后的完整 headers
	 * @since 0.9.35
	 */
	abstract public function generateHeaders(string $method, array $headers = [], array $query = []): array;

	/**
	 * 云平台图片缩放处理
	 */
	abstract public static function resizeImage(string $image_url, int $width, int $height): string;

	/**
	 * Curl PUT
	 */
	protected static function put(string $sourceFile, string $targetUri, array $headers, int $timeout): array {
		$request  = new Requests;
		$response = $request->request($targetUri, ['method' => 'PUT', 'headers' => $headers, 'timeout' => $timeout, 'filename' => $sourceFile]);
		return static::handleResponse($response);
	}

	/**
	 * Curl Delete
	 */
	protected static function delete(string $targetUri, array $headers, int $timeout): array {
		$request  = new Requests;
		$response = $request->request($targetUri, ['method' => 'DELETE', 'headers' => $headers, 'timeout' => $timeout]);
		return static::handleResponse($response);
	}

	/**
	 * handleResponse
	 * @since 0.9.32
	 */
	protected static function handleResponse(array $response): array {
		if (200 != $response['headers']['http_code'] and 204 != $response['headers']['http_code']) {
			throw new Exception(json_encode($response));
		}

		return $response;
	}
}
