<?php
namespace Wnd\Controller;

/**
 *删除附件
 *@since 2019.01.23
 *@param $_POST['meta_key'];
 *@param $_POST['post_parent'];
 *@param $_POST['file_id'];
 */
class Wnd_Delete_File extends Wnd_Controller {

	public static function execute() {
		$meta_key    = $_POST['meta_key'];
		$post_parent = $_POST['post_parent'];
		$file_id     = $_POST['file_id'];

		if (!$file_id) {
			return array('status' => 0, 'msg' => '文件不存在！');
		}

		if (!current_user_can('edit_post', $file_id)) {
			return array('status' => 0, 'msg' => '权限错误或文件不存在！');
		}

		// 执行删除
		if (wp_delete_attachment($file_id, true)) {
			do_action('wnd_delete_file', $file_id, $post_parent, $meta_key);
			return array('status' => 1, 'msg' => $file_id);

			//删除失败
		} else {
			return array('status' => 0, 'msg' => '权限错误！');
		}
	}
}