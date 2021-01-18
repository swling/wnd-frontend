<?php
namespace Wnd\Action;

use Exception;
use Wnd\Action\Wnd_Insert_Post;

class Wnd_Update_Post extends Wnd_Action {

	/**
	 *
	 *@since 初始化
	 *@param 	array 	$_POST 		表单数据
	 *@param 	int 	$post_id 	文章id
	 *@return 	array
	 *更新文章
	 */
	public function execute($post_id = 0): array{
		// 获取被编辑Post ID
		$_POST['_post_ID'] = (int) ($post_id ?: $_POST['_post_ID']);
		if ($_POST['_post_ID']) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		$action = new Wnd_Insert_Post();
		return $action->execute();
	}
}
