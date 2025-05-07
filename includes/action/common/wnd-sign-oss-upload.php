<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Utility\Wnd_OSS_Handler;

/**
 * 直接上传文件到 OSS
 * - 文件信息将写入 WP 附件数据库
 * - 文件无需上传至服务器，将签名信息返回给前端，前端直接将文件发送至 OSS
 *
 * @since 0.9.33.7
 */
class Wnd_Sign_OSS_Upload extends Wnd_Upload_File {

	private $md5;
	private $local_file;
	private $is_private;

	protected function execute(): array {
		$oss_handler = Wnd_OSS_Handler::get_instance();
		$oss_request = $oss_handler->sign_oss_request('PUT', $this->local_file, $this->mime_type, $this->md5, $this->is_private);
		$oss_handler->remove_local_storage_hook(); // 移除本地文件处理钩子

		// 写入 attachment post
		$this->attachment_id = $this->insert_attachment_db();

		$data = [
			'put_url'    => $oss_request['url'],
			'signed_url' => $oss_request['signed_url'], // 若为私有存储返回包含签名的链接，否则为空
			'url'        => wnd_get_attachment_url($this->attachment_id), // WP 附件链接（若无 CDN 重写等特殊 filter，则通常与 put_url 一致）
			'thumbnail'  => '',
			'headers'    => $oss_request['headers'],
			'id'         => $this->attachment_id,
		];
		return ['status' => 1, 'data' => $data];
	}

	protected function parse_data() {
		parent::parse_data();

		// OSS 属性
		$extension        = $this->data['extension'];
		$file_name        = uniqid('oss-') . '.' . $extension;
		$this->md5        = $this->data['md5'] ?? '';
		$this->local_file = $this->get_attached_file($file_name);
		$this->is_private = (bool) ($this->data['is_paid'] ?? false);

		// 数据表属性
		$this->mime_type     = $this->data['mime_type'] ?? '';
		$this->relative_path = $this->relative_upload_path($this->local_file);
	}

	/**
	 * 文件核查
	 */
	protected function check_file() {
		if (!wnd_get_config('enable_oss')) {
			throw new Exception('Object storage service is disabled');
		}
	}

	/**
	 * 直传 OSS 需要手动设置附件文件路径
	 */
	private function get_attached_file($file_name): string {
		$time    = $this->get_archive_time();
		$uploads = wp_upload_dir($time);
		$file    = $uploads['path'] . '/' . $file_name;
		return $file;
	}

}
