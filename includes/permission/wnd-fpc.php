<?php
namespace Wnd\Permission;

use Exception;

/**
 * 文件管理权限：File permission control
 * @since 0.9.36
 */
class Wnd_FPC {

	/**
	 * 文件上传检测
	 *
	 */
	public function check_file_upload(int $post_parent, string $meta_key) {
		if ($meta_key != 'gallery' or !$post_parent) {
			return true;
		}

		// 限制产品相册上传数量
		$old_images            = wnd_get_post_meta($post_parent, 'gallery');
		$old_images_count      = is_array($old_images) ? count($old_images) : 0;
		$current_upload_count  = count($_FILES['wnd_file']['name']);
		$gallery_picture_limit = (int) wndt_get_config('gallery_picture_limit');

		if ($old_images_count + $current_upload_count > $gallery_picture_limit) {
			throw new Exception('最多上传' . $gallery_picture_limit . '张图片');
		}
	}
}
