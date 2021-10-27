<?php
namespace Wnd\Action;

use Exception;
use Wnd\Action\Wnd_Action;
use Wnd\Getway\Wnd_Object_Storage;
use Wnd\Utility\Wnd_OSS_Handler;

/**
 * 浏览器直传对象存储签名
 * - 随机重命名文件
 * - 返回完整的 URL 及 header
 * - 不写入 WP 附件数据库
 *
 * ## 安全策略 @since 0.9.50.1
 * - 本签名主要应用于临时文件上传存储，通常面向含匿名用户在内的所有用户用户开放
 * - 出于安全考虑，本签名对应的节点不可与站内常规对象存储节点相同
 */
class Wnd_Sign_OSS_Direct extends Wnd_Action {

	/**
	 * 本操作非标准表单请求，无需验证签名
	 */
	protected $verify_sign = false;

	private $oss_sp;
	private $endpoint;
	private $endpoint_internal;
	private $method;
	private $mime_type;
	private $md5;
	private $file_path_name;

	public function execute(): array{
		$oss = Wnd_Object_Storage::get_instance($this->oss_sp, $this->endpoint);
		$oss->setFilePathName($this->file_path_name);
		$headers = $oss->generateHeaders($this->method, $this->mime_type, $this->md5);

		/**
		 * - 阿里云 oss 内网地址需要替换
		 * - 腾讯云文档声称会智能解析 @link https://cloud.tencent.com/document/product/436/6224#.E5.86.85.E7.BD.91.E5.92.8C.E5.A4.96.E7.BD.91.E8.AE.BF.E9.97.AE
		 */
		if ('Aliyun' == $this->oss_sp and !$this->endpoint_internal) {
			$this->endpoint_internal = str_replace('.aliyuncs.com', '-internal.aliyuncs.com', $this->endpoint);
		} else {
			$this->endpoint_internal = $this->endpoint;
		}

		$data = [
			'url'      => $this->endpoint . '/' . $this->file_path_name,
			'internal' => $this->endpoint_internal . '/' . $this->file_path_name,
			'headers'  => $headers,
		];
		return ['status' => 1, 'data' => $data];
	}

	protected function check() {
		$ext = $this->data['extension'] ?? '';
		if (!$ext) {
			throw new Exception('Invalid file type');
		}

		$this->oss_sp            = $this->data['oss_sp'] ?? '';
		$this->endpoint          = $this->data['endpoint'] ?? '';
		$this->endpoint_internal = $this->data['endpoint_internal'] ?? '';
		$this->method            = $this->data['method'] ?? 'PUT';
		$this->mime_type         = $this->data['mime_type'] ?? '';
		$this->md5               = $this->data['md5'] ?? '';
		$this->file_path_name    = uniqid() . '.' . $ext;

		// 处于安全考虑，不写入附件的浏览器直传对象存储，不可与站内常规对象存储为同一个节点
		$oss_handler = Wnd_OSS_Handler::get_instance();
		if (!$oss_handler->is_external_endpoint($this->endpoint)) {
			throw new Exception('Not allowed to be an internal endpoint');
		}
	}
}
