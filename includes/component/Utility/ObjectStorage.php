<?php
namespace Wnd\Component\Utility;

/**
 *@since 0.9.30
 *
 *对象存储抽象基类
 */
abstract class ObjectStorage {

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
}
