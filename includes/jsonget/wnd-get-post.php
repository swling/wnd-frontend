<?php
namespace Wnd\JsonGet;

/**
 *@since 2020.07.21
 *获取Post Json
 *@param $_POST['post_id']  Post ID
 */
class Wnd_Get_Post extends Wnd_JsonGet {

	public static function get(int $post_id = 0): array{
		$post_id = (int) ($post_id ?: $_POST['post_id']);
		$post    = get_post($post_id, ARRAY_A);

		/**
		 *非公开post仅返回基本状态
		 */
		if ('publish' != $post['post_status'] and !current_user_can('edit_post', $post_id)) {
			return [
				'post_status' => $post['post_status'],
			];
		}

		return $post;
	}
}
