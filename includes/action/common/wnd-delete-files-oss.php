<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action;
use Wnd\Getway\Wnd_Object_Storage;
use Wnd\Utility\Wnd_OSS_Handler;

/**
 * 浏览器直接批量删除 OSS 文件，对应浏览器直传 OSS 文件
 * @see Wnd\Action\Common\Wnd_Sign_OSS_Direct
 *
 * @since 0.9.71
 */
class Wnd_Delete_Files_OSS extends Wnd_Action {

	/**
	 * 本操作非标准表单请求，无需验证签名
	 */
	protected $verify_sign = false;

	private $oss_sp;
	private $endpoint;
	private $files;

	protected function execute(): array {
		$oss = Wnd_Object_Storage::get_instance($this->oss_sp, $this->endpoint);
		$res = $oss->deleteBatch($this->files);

		$obj = simplexml_load_string($res['body']);
		return ['status' => 1, 'data' => ['obj' => $obj, 'count' => count($obj)]];
	}

	protected function parse_data() {
		$this->oss_sp   = $this->data['oss_sp'] ?? '';
		$this->endpoint = $this->data['endpoint'] ?? '';
		$this->files    = $this->data['files'] ?? '';

		// 解析出文件路径：（不含 “/” 前缀）
		foreach ($this->files as $key => $file) {
			$this->files[$key] = trim(parse_url($file)['path'], '/');
		}

		// oss文件已删除，需要删除原文件对应的 oss 链接缓存
		$cache_keys = $this->data['cache_keys'] ?? [];
		foreach ($cache_keys as $cache_key) {
			Wnd_Sign_OSS_Direct::delete_cache($cache_key);
		}
	}

	protected function check() {
		// 处于安全考虑，不写入附件的浏览器直传对象存储，不可与站内常规对象存储为同一个节点
		$oss_handler = Wnd_OSS_Handler::get_instance();
		if (!$oss_handler->is_direct_endpoint($this->endpoint)) {
			throw new Exception('Not allowed to be an internal endpoint');
		}
	}
}
