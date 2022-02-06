<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action;

/**
 * ajax文件上传
 * 	[
 * 		[
 * 			'status' => 1,
 * 			'data' => ['url' => $url, 'thumbnail' => $thumbnail ?? 0, 'id' => $file_id],
 * 			'msg' => '上传成功',
 * 		],
 * 	];
 *
 * @since 2019.01.20
 */
class Wnd_Upload_File extends Wnd_Action {

	/**
	 * 本操作非标准表单请求，无需验证签名
	 */
	protected $verify_sign = false;

	protected $post_parent;

	protected $meta_key;

	protected function execute(): array{
		//$_FILES['wnd_file']需要与input name 值匹配
		if (empty($_FILES['wnd_file'])) {
			throw new Exception(__('上传文件为空', 'wnd'));
		}

		$thumbnail_height = (int) ($this->data['thumbnail_height'] ?? 0);
		$thumbnail_width  = (int) ($this->data['thumbnail_width'] ?? 0);

		// These files need to be included as dependencies when on the front end.
		if (!is_admin()) {
			require ABSPATH . 'wp-admin/includes/image.php';
			require ABSPATH . 'wp-admin/includes/file.php';
			require ABSPATH . 'wp-admin/includes/media.php';
		}

		/**
		 * 遍历文件上传
		 * @since 2019.05.06 改写
		 */
		$return_array = []; // 定义图片信息返回数组
		$files        = $_FILES['wnd_file']; //暂存原始上传信息，后续将重写$_FILES全局变量以适配WordPress上传方式

		foreach ($files['name'] as $key => $value) {
			// 将多文件上传数据遍历循环后，重写为适配 media_handle_upload 的单文件模式
			$file = [
				'name'     => $files['name'][$key],
				'type'     => $files['type'][$key],
				'tmp_name' => $files['tmp_name'][$key],
				'error'    => $files['error'][$key],
				'size'     => $files['size'][$key],
			];
			$_FILES = ['temp_key' => $file];

			// 单文件错误检测
			if ($_FILES['temp_key']['error'] > 0) {
				$return_array[] = ['status' => 0, 'msg' => 'Error: ' . $_FILES['temp_key']['error']];
				continue;
			}

			// 文件格式限制
			$extension          = pathinfo($_FILES['temp_key']['name'])['extension'] ?? '';
			$allowed_extensions = array_keys(get_allowed_mime_types());
			if (!in_array($extension, $allowed_extensions)) {
				$return_array[] = ['status' => 0, 'msg' => 'Error: File types not allowed'];
				continue;
			}

			/**
			 * 上传文件并附属到对应的post parent 默认为 0 即孤立文件
			 *
			 * 新增 Attachment Post 字段：post_content_filtered 保存附件对应在 parent post 中的 meta key
			 * - @since 0.9.39
			 * - used by plugins to cache a version of post_content typically passed through the ‘the_content’ filter.Not used by WordPress core itself.
			 * - meta key 写入 parent post meta 是在附件上传完成之后执行，因此无法用于附件上传过程中，判断是否应该写入私有 OSS 节点
			 * - 保存 meta key 至此，从而使得 Utility\Wnd_OSS_Handler 可以识别判断是否需要上传至私有 OSS 节点
			 * - 不保存 meta key 至 attachment post 的 post meta 是为了减少一行数据记录
			 * - @see Utility\Wnd_OSS_Handler::is_private_storage()
			 *
			 */
			$file_id = media_handle_upload('temp_key', $this->post_parent, ['post_content_filtered' => $this->meta_key]);

			// 上传失败
			if (is_wp_error($file_id)) {
				$return_array[] = ['status' => 0, 'msg' => $file_id->get_error_message()];
				continue;
			}

			$url = wp_get_attachment_url($file_id);

			// 判断是否为图片
			if (strrpos($file['type'], 'image') !== false) {
				// 返回缩略图
				$thumbnail = wnd_get_thumbnail_url($url, $thumbnail_width, $thumbnail_height);
			}

			// 将当前上传的图片信息写入数组
			$temp_array = [
				'status' => 1,
				'data'   => [
					'url'       => $url,
					'thumbnail' => $thumbnail ?? 0,
					'id'        => $file_id,
					'post'      => get_post($file_id),
				],
				'msg'    => __('上传成功', 'wnd'),
			];
			$return_array[] = $temp_array;

			/**
			 * @since 2019.02.13 当存在meta key时，表明上传文件为特定用途存储，仅允许上传单个文件
			 * @since 2019.05.05 当meta key == gallery 表示为上传图集相册 允许上传多个文件
			 */
			if ('gallery' != $this->meta_key) {
				//处理完成根据用途做下一步处理
				do_action('wnd_upload_file', $file_id, $this->post_parent, $this->meta_key);
				break;
			}
		}
		unset($key, $value);

		/**
		 * @since 2019.05.05 当meta key == gallery 表示为上传图集相册 允许上传多个文件
		 */
		if ('gallery' == $this->meta_key) {
			do_action('wnd_upload_gallery', $return_array, $this->post_parent);
		}

		// 返回上传信息二维数组合集
		return $return_array;
	}

	protected function check() {
		$this->post_parent = (int) ($this->data['post_parent'] ?? 0);
		$this->meta_key    = $this->data['meta_key'] ?? '';

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

		if ($this->meta_key and !wp_verify_nonce($this->data['meta_key_nonce'], $this->meta_key)) {
			throw new Exception(__('meta_key不合法', 'wnd'));
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
}
