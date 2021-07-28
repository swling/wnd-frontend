<?php
namespace Wnd\Action;

use Exception;
use Wnd\Utility\Wnd_OSS_Handler;

/**
 * 直接上传文件到 OSS
 * - 不保留本地文件
 *
 * @since 0.9.33.7
 */
class Wnd_Sign_OSS_Upload extends Wnd_Upload_File {

	private $file_name;

	private $mime_type;

	private $local_file;

	public function execute(): array{
		$this->file_name  = uniqid('oss-') . '.' . $this->data['extension'];
		$this->mime_type  = $this->data['mime_type'] ?? '';
		$md5              = $this->data['md5'] ?? '';
		$this->local_file = $this->get_attached_file();

		$oss_handler = Wnd_OSS_Handler::get_instance();
		$oss_params  = $oss_handler->get_oss_sign_params('PUT', $this->local_file, $this->mime_type, $md5);
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
			'url'       => $oss_params['url'],
			'thumbnail' => '',
			'headers'   => $oss_params['headers'],
			'id'        => $attachment_id,
		];
		return ['status' => 1, 'data' => $data];
	}

	/**
	 * 文件核查
	 */
	protected function check() {
		parent::check();

		if (!wnd_get_config('oss_enable')) {
			throw new Exception('Object storage service is disabled');
		}

		// $allowedExts = ['.gif', '.jpeg', '.jpg', '.png'];
		// $info        = pathinfo($_FILES[static::$input_name]['name']);
		// $this->ext   = '.' . strtolower($info['extension']);

		// if ($_FILES[static::$input_name]['error'] > 0) {
		// 	throw new Exception('Error:' . $_FILES[static::$input_name]['error'] . '<br>');
		// }

		// if (!in_array($this->ext, $allowedExts)) {
		// 	throw new Exception('非法的文件格式');
		// }

		// if ($_FILES[static::$input_name]['size'] > 1024 * 1024 * 5) {
		// 	throw new Exception('文件不得大于 5 M');
		// }
	}

	/**
	 * 直传 OSS 需手动写入 WP 附件数据
	 */
	private function inset_attachment(): int{
		$attachment = [
			'post_mime_type' => $this->mime_type,
			'post_title'     => wp_basename($this->file_name, '.' . $this->data['extension']),
			'post_content'   => '',
			'post_excerpt'   => '',
		];

		// Save the data.
		$attachment_id = wp_insert_attachment($attachment, $this->local_file, $this->post_parent, true);
		if (is_wp_error($attachment_id)) {
			throw new Exception($attachment_id->get_error_message(), 1);
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
