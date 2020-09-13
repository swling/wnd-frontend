<?php
namespace Wnd\Action;

use Exception;

/**
 *删除附件
 *@since 2019.01.23
 *@param $_POST['meta_key'];
 *@param $_POST['post_parent'];
 *@param $_POST['file_id'];
 */
class Wnd_Delete_File extends Wnd_Action_Ajax_User {

	/**
	 *本操作非标准表单请求，无需解析表单数据
	 */
	protected $parse_data = false;

	public function execute(): array{
		$meta_key    = $_POST['meta_key'];
		$post_parent = (int) $_POST['post_parent'];
		$file_id     = (int) $_POST['file_id'];

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

			//删除失败
		} else {
			throw new Exception(__('删除失败', 'wnd'));
		}
	}
}
