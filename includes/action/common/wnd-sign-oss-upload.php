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

	private $file_name;
	private $mime_type;
	private $extension;
	private $md5;
	private $local_file;
	private $is_private;

	protected function execute(): array{
		$oss_handler = Wnd_OSS_Handler::get_instance();
		$oss_request = $oss_handler->sign_oss_request('PUT', $this->local_file, $this->mime_type, $this->md5, $this->is_private);
		$oss_handler->remove_local_storage_hook(); // 移除本地文件处理钩子

		// 写入 attachment post
		$attachment_id = $this->inset_attachment();

		/**
		 *
		 * 根据用途做下一步处理
		 *  - 此项 Hook 非常重要，在特定文件上传场景，如付费文件及用户资料头像上传中，将以此做下一步处理
		 *  - @see Wnd_Upload_File
		 */
		do_action('wnd_upload_file', $attachment_id, $this->post_parent, $this->meta_key);

		$data = [
			'url'        => $oss_request['url'],
			'signed_url' => $oss_request['signed_url'], // 若为私有存储返回包含签名的链接，否则与 url 一致
			'thumbnail'  => '',
			'headers'    => $oss_request['headers'],
			'id'         => $attachment_id,
		];
		return ['status' => 1, 'data' => $data];
	}

	protected function parse_data() {
		$this->extension  = $this->data['extension'];
		$this->file_name  = uniqid('oss-') . '.' . $this->extension;
		$this->mime_type  = $this->data['mime_type'] ?? '';
		$this->md5        = $this->data['md5'] ?? '';
		$this->local_file = $this->get_attached_file();
		$this->is_private = (bool) ($this->data['is_paid'] ?? false);
	}

	/**
	 * 文件核查
	 */
	protected function check() {
		parent::check();

		if (!wnd_is_allowed_extension($this->extension)) {
			throw new Exception('File types not allowed');
		}

		if (!wnd_get_config('enable_oss')) {
			throw new Exception('Object storage service is disabled');
		}
	}

	/**
	 * 直传 OSS 需手动写入 WP 附件数据
	 *
	 * 新增 Attachment Post 字段：post_content_filtered 保存附件对应在 parent post 中的 meta key
	 * - @since 0.9.39
	 * - used by plugins to cache a version of post_content typically passed through the ‘the_content’ filter.Not used by WordPress core itself.
	 * - meta key 写入 parent post meta 是在附件上传完成之后执行，因此无法用于附件上传过程中，判断是否应该写入私有 OSS 节点
	 * - 保存 meta key 至此，从而使得 Utility\Wnd_OSS_Handler 可以识别判断是否需要上传至私有 OSS 节点
	 * - 不保存 meta key 至 attachment post 的 post meta 是为了减少一行数据记录
	 * - @see Utility\Wnd_OSS_Handler::is_private_storage()
	 */
	private function inset_attachment(): int{
		$attachment = [
			'post_mime_type'        => $this->mime_type,
			'post_title'            => wp_basename($this->file_name, '.' . $this->data['extension']),
			'post_content'          => '',
			'post_excerpt'          => '',
			'post_content_filtered' => $this->meta_key,
		];

		// Save the data.
		$attachment_id = wp_insert_attachment($attachment, $this->local_file, $this->post_parent, true);
		if (is_wp_error($attachment_id)) {
			throw new Exception($attachment_id->get_error_message());
		}

		return $attachment_id;
	}

	/**
	 * 直传 OSS 需要手动设置附件 Post Meta，用以记录文件路径
	 * 直传 OSS 并未在本地存储文件，故亦无缩略图等相关数据，故此 wp_update_attachment_metadata 可忽略
	 */
	private function get_attached_file(): string{
		$time = current_time('mysql');
		$post = get_post($this->post_parent);

		if ($post) {
			// The post date doesn't usually matter for pages, so don't backdate this upload.
			if ('page' !== $post->post_type && substr($post->post_date, 0, 4) > 0) {
				$time = $post->post_date;
			}
		}

		$uploads = wp_upload_dir($time);
		$file    = $uploads['path'] . '/' . $this->file_name;
		return $file;
	}
}
