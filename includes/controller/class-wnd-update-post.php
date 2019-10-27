<?php
namespace Wnd\Controller;

class Wnd_Update_Post extends Wnd_Controller_Ajax {

	/**
	 *
	 *@since 初始化
	 *@param 	array 	$_POST 		表单数据
	 *@param 	int 	$post_id 	文章id
	 *@return 	array
	 *更新文章
	 */
	public static function execute($post_id = 0): array{

		// 获取被编辑post
		$post_id   = $post_id ?: (int) $_POST['_post_ID'];
		$edit_post = get_post($post_id);
		if (!$edit_post) {
			return array('status' => 0, 'msg' => '获取内容ID失败！');
		}

		$_POST['_post_ID'] = $post_id;
		return Wnd_Insert_Post::execute();
	}
}
