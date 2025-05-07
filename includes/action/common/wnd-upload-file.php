<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action;
use Wnd\WPDB\Wnd_Attachment_DB;

/**
 * @since 2019.01.20
 */
class Wnd_Upload_File extends Wnd_Action {

	/**
	 * 本操作非标准表单请求，无需验证签名
	 */
	protected $verify_sign = false;

	protected $post_parent;

	protected $meta_key;

	protected $relative_path;
	protected $attachment_id;
	protected $mime_type;

	protected function execute(): array {
		// These files need to be included as dependencies when on the front end.
		if (!is_admin()) {
			require ABSPATH . 'wp-admin/includes/file.php';
		}

		// ############################### 2025 拆分附件数据表
		$res = wp_handle_upload($_FILES['wnd_file'], ['test_form' => false], $this->get_archive_time());
		if (isset($res['error'])) {
			throw new Exception('upload_error' . $res['error']);
		}

		$this->mime_type     = $res['type'];
		$this->relative_path = $this->relative_upload_path($res['file']);
		// $title = sanitize_text_field($name);

		$this->attachment_id = $this->insert_attachment_db();

		$data = [
			'id'        => $this->attachment_id,
			'url'       => $res['url'],
			'thumbnail' => $res['url'],
		];
		return ['status' => 1, 'data' => $data];
		// ############################### 2025 拆分附件数据表
	}

	protected function parse_data() {
		$this->post_parent = (int) ($this->data['post_parent'] ?? 0);
		$this->meta_key    = $this->data['meta_key'] ?? '';
	}

	final protected function check() {
		$this->check_file();

		// 上传信息校验
		if (!$this->user_id and !$this->post_parent) {
			throw new Exception(__('User ID及Post ID不可同时为空', 'wnd'));
		}

		/**
		 * meta_key 及 post_parent同时为空时，上传文件将成为孤立的的文件，在前端上传附件应该具有明确的用途，应避免这种情况
		 * @since 2019.05.08 上传文件meta_key post_parent校验
		 */
		if (!$this->meta_key and !$this->post_parent) {
			throw new Exception(__('Meta_key与Post_parent不可同时为空', 'wnd'));
		}

		if ($this->post_parent and !get_post($this->post_parent)) {
			throw new Exception(__('post_parent无效', 'wnd'));
		}

		/**
		 * 上传权限过滤
		 * @since 2019.04.16
		 */
		$can_upload_file = apply_filters('wnd_can_upload_file', ['status' => 1, 'msg' => ''], $this->post_parent, $this->meta_key);
		if (0 === $can_upload_file['status']) {
			throw new Exception($can_upload_file['msg']);
		}
	}

	protected function check_file() {
		//$_FILES['wnd_file']需要与input name 值匹配
		if (empty($_FILES['wnd_file'])) {
			throw new Exception(__('上传文件为空', 'wnd'));
		}

		// 文件错误检测
		if ($_FILES['wnd_file']['error'] > 0) {
			throw new Exception('File Error!');
		}

		// 文件格式限制
		$extension = pathinfo($_FILES['wnd_file']['name'])['extension'] ?? '';
		if (!wnd_is_allowed_extension($extension)) {
			throw new Exception('Error: File types not allowed');
		}
	}

	/**
	 * Returns relative path to an uploaded file.
	 *
	 * The path is relative to the current upload dir.
	 *
	 * @since 2.9.0
	 * @access private
	 *
	 * @param string $path Full path to the file.
	 * @return string Relative path on success, unchanged path on failure.
	 */
	protected function relative_upload_path($path) {
		$new_path = $path;

		$uploads = wp_get_upload_dir();
		if (str_starts_with($new_path, $uploads['basedir'])) {
			$new_path = str_replace($uploads['basedir'], '', $new_path);
			$new_path = ltrim($new_path, '/');
		}

		return $new_path;
	}

	protected function insert_attachment_db(): int {
		$attachment = [
			'post_id'    => $this->post_parent,
			'user_id'    => $this->user_id,
			'meta_key'   => $this->meta_key,
			'file_path'  => $this->relative_path,
			'mime_type'  => $this->mime_type,
			// 'meta_json'   => '',
			'created_at' => time(),
		];
		return Wnd_Attachment_DB::get_instance()->insert($attachment);
	}

	/**
	 * @since 0.9.86
	 * 确定附件归档时间
	 * 如果归属到 post 则根据 post 确定，否则为当前时间
	 */
	protected function get_archive_time() {
		$time = current_time('mysql');
		if ($this->post_parent) {
			$post = get_post($this->post_parent);
			// The post date doesn't usually matter for pages, so don't backdate this upload.
			if ('page' !== $post->post_type && substr($post->post_date, 0, 4) > 0) {
				$time = $post->post_date;
			}
		}
		return $time;
	}

	final protected function complete() {
		if (!$this->attachment_id) {
			throw new Exception(__('附件ID无效', 'wnd'));
		}

		/**
		 *
		 * 根据用途做下一步处理
		 *  - 此项 Hook 非常重要，在特定文件上传场景，如付费文件及用户资料头像上传中，将以此做下一步处理
		 *  - @see Wnd_Upload_File
		 */
		do_action('wnd_upload_file', $this->attachment_id, $this->post_parent, $this->meta_key);
	}
}
