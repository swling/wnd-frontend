<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action_User;

/**
 * 删除附件
 * @since 2019.01.23
 */
class Wnd_Delete_File extends Wnd_Action_User {

	protected $verify_sign = false;
	private $meta_key;
	private $file_id;
	private $post_parent;

	protected function execute(): array{
		if (wp_delete_attachment($this->file_id, true)) {
			do_action('wnd_delete_file', $this->file_id, $this->post_parent, $this->meta_key);
			return ['status' => 1, 'msg' => __('删除成功', 'wnd'), 'data' => $this->file_id];
		}

		throw new Exception(__('删除失败', 'wnd'));
	}

	protected function check() {
		$this->meta_key    = $this->data['meta_key'] ?? '';
		$this->file_id     = (int) $this->data['file_id'];
		$this->post_parent = get_post($this->file_id)->post_parent ?? 0;

		if (!$this->file_id) {
			throw new Exception(__('文件不存在', 'wnd'));
		}

		if (!current_user_can('edit_post', $this->file_id)) {
			throw new Exception(__('权限错误', 'wnd'));
		}
	}
}
