<?php
namespace Wnd\Action;

use Exception;

/**
 * 删除附件
 * @since 2019.01.23
 */
class Wnd_Delete_File extends Wnd_Action_User {

	protected $verify_sign = false;

	public function execute(): array{
		$meta_key    = $this->data['meta_key'] ?? '';
		$file_id     = (int) $this->data['file_id'];
		$post_parent = get_post($file_id)->post_parent ?? 0;

		if (!$file_id) {
			throw new Exception(__('文件不存在', 'wnd'));
		}

		if (!current_user_can('edit_post', $file_id)) {
			throw new Exception(__('权限错误', 'wnd'));
		}

		// 执行删除
		if (wp_delete_attachment($file_id, true)) {
			do_action('wnd_delete_file', $file_id, $post_parent, $meta_key);
			return ['status' => 1, 'msg' => $file_id];

		} else {
			throw new Exception(__('删除失败', 'wnd'));
		}
	}
}
